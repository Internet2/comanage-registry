<?php
/**
 * COmanage Registry CO Enrollment Attributes Controller
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");
  
class CoEnrollmentAttributesController extends StandardController {
  // Class name, used by Cake
  public $name = "CoEnrollmentAttributes";
  
  // Use the javascript helper for the Views (for drag/drop in particular)
  public $helpers = array('Js');

  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoEnrollmentAttribute.ordr' => 'asc'
    )
  );
  
  public $uses = array('CoEnrollmentAttribute',
                       'AttributeEnumeration',
                       'CmpEnrollmentConfiguration',
                       'CoPersonRole');
  
  // We don't directly require a CO, but indirectly we do.
  public $requires_co = true;
  
  public $edit_contains = array(
    'CoEnrollmentAttributeDefault'
  );
  
  public $view_contains = array(
    'CoEnrollmentAttributeDefault'
  );
  
  /**
   * Add an Enrollment Attribute.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - postcondition: On success, new Object created
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: $<object>_id or $invalid_fields set (REST)
   *
   * @since  COmanage Registry v0.3
   */
  
  function add() {
    if(!empty($this->request->data)) {
      $this->clearUnassociatedRequestData();
      
      if(!isset($this->request->data['CoEnrollmentAttribute']['ordr'])
         || $this->request->data['CoEnrollmentAttribute']['ordr'] == '') {
        $args['fields'][] = "MAX(ordr) as m";
        $args['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = filter_var($this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id'],
          FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK);
        $args['order'][] = "m";
        $args['contain'] = false;
        
        $o = $this->CoEnrollmentAttribute->find('first', $args);
        $n = 1;
        
        if(!empty($o)) {
          $n = $o[0]['m'] + 1;
        }
        
        if(!empty($o))
          $this->request->data['CoEnrollmentAttribute']['ordr'] = $n;
      }
    }
    
    parent::add();
  }

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Auth component is configured 
   *
   * @since  COmanage Registry v0.3
   */
  
  function beforeFilter() {
    global $cm_lang, $cm_texts;
    
    parent::beforeFilter();
    
    // Sub optimally, we need to unlock add and edit so that the javascript form manipulation
    // magic works. XXX It would be good to be more specific, and just call unlockField()
    // on specific fields, but some initial testing does not make it obvious which
    // fields need to be unlocked.
    // Reorder was also unlocked so that the AJAX calls could get through for drag/drop reordering.
    $this->Security->unlockedActions = array('add', 'edit', 'reorder');
  }

  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v0.9.3
   */
  
  function beforeRender() {
    parent::beforeRender();
    
    if(!$this->request->is('restful')) {
      // Strictly speaking, this controller doesn't require a CO except to redirect/render views.
      // Figure out the CO ID associated with the current enrollment flow. We'll specifically
      // not set $this->cur_co since it will break things like pagination setup.
      
      $coefid = null;
      
      if($this->action == 'add' || $this->action == 'index' || $this->action == 'order') {
        // Accept coefid from the url or the form
        
        if(!empty($this->request->params['named']['coef'])) {
          $coefid = filter_var($this->request->params['named']['coef'],FILTER_SANITIZE_SPECIAL_CHARS);
        } elseif(!empty($this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id'])) {
          $coefid = $this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id'];
        }
      } elseif(!empty($this->request->params['pass'][0])) {
        // Map the enrollment flow from the requested object
        
        $coefid = $this->CoEnrollmentAttribute->field('co_enrollment_flow_id',
                                                      array('id' => $this->request->params['pass'][0]));
      }
      
      if($coefid) {
        $this->CoEnrollmentAttribute->CoEnrollmentFlow->id = $coefid;
        
        $this->set('vv_coefid', filter_var($coefid,FILTER_SANITIZE_SPECIAL_CHARS));
        
        $coid = $this->CoEnrollmentAttribute->CoEnrollmentFlow->field('co_id');
        
        if(!empty($coid)) {
          $this->set('vv_coid', $coid);
          
          // Override page title
          
          // ->id was set in beforeFilter();
          $efname = $this->CoEnrollmentAttribute->CoEnrollmentFlow->field('name');
          
          $this->set('title_for_layout', $this->viewVars['title_for_layout'] . " (" . $efname . ")");
          $this->set('vv_ef_name', $efname);
          $this->set('vv_ef_id', $this->CoEnrollmentAttribute->CoEnrollmentFlow->id);
          
          // Determine attribute enumerations
          
          $supportedAttrEnums = array(
            'CoPersonRole.o',
            'CoPersonRole.ou',
            'CoPersonRole.title',
            'OrgIdentity.o',
            'OrgIdentity.ou',
            'OrgIdentity.title'
          );
          
          $enums = array();
          
          foreach($supportedAttrEnums as $attr) {
            $cfg = $this->AttributeEnumeration->enumerations($this->viewVars['vv_coid'], $attr);
            
            // XXX CO-2012 for now we don't support "allow_other"
            if($cfg) {
              // We need to rekey from general format (eg) "OrgIdentity.o" to
              // Enrollment Attribute format (eg) "o:o"
              $a = explode('.', $attr, 2);
              $code = "";

              switch($a[0]) {
                case 'CoPersonRole':
                  $code = 'r';
                  break;
                case 'OrgIdentity':
                  $code = 'o';
                  break;
                default:
                  throw new LogicException(_txt('er.notimpl'));
                  break;
              }

              $enums[ $code.":".$a[1] ] = $cfg['dictionary'];
            }
          }
          
          $this->set('vv_enums', $enums);
          
          // Assemble the set of available attributes for the view to render
          
          $availableAttributes = $this->CoEnrollmentAttribute->availableAttributes($coid);
          $availableAttributesNested = array();
          foreach($availableAttributes as $mdl_name => &$attr_mdl) {
            foreach($attr_mdl as $attribute_map => $attribute_fn) {
              if(substr_count($attribute_map, ':') > 1) {
                $attribute_fn = substr($attribute_fn, 0, -1);
                $attribute_fn = array_map('trim', explode('(', $attribute_fn));
                $availableAttributesNested[$mdl_name][$attribute_fn[0]][$attribute_map] = $attribute_fn[1];
              } else {
                $availableAttributesNested[$mdl_name][$attribute_map] = $attribute_fn;
              }
            }
          }
          $this->set('vv_available_attributes_nested', $availableAttributesNested);

          // Flatten available attributes array

          $flattenedArray = Hash::flatten($availableAttributes,'.');
          $attributeArray = array();
          foreach($flattenedArray as $key => $value) {
            list($attrMdl, $code) = explode('.', $key);
            if(strpos($value, ')') !== false) {
              list($attribute, $attrType) = explode('(', $value);
              $attrType = substr($attrType, 0, -1);
            } else {
              $attrType = '';
              $attribute = $value;
            }
            $attributeArray[$code]['attrName'] = trim($attribute);
            $attributeArray[$code]['attrType'] = trim($attrType);
            $attributeArray[$code]['attrMdl'] = trim($attrMdl);
          }
          unset($flattenedArray);
          $this->set('vv_attributes_properties', $attributeArray);
          
          // By specifying actions here we limit the number of queries for /index
          if($this->action == 'add' || $this->action == 'edit') {
            // And pull details of extended attributes so views can determine types
            
            $args = array();
            $args['conditions']['co_id'] = $coid;
            $args['fields'] = array('CoExtendedAttribute.name', 'CoExtendedAttribute.type');
            $args['contain'] = false;
            
            $this->set('vv_ext_attr_types',
                       $this->CoEnrollmentAttribute->CoEnrollmentFlow->Co->CoExtendedAttribute->find('list', $args));

            // Assemble the list of available affiliations
            
            $this->set('vv_affiliations', $this->CoPersonRole->types($coid, 'affiliation'));

            // (Dis)allow empty COUs

            $this->set('vv_allow_empty_cou', $this->CoEnrollmentAttribute
                                                  ->CoEnrollmentFlow
                                                  ->Co
                                                  ->CoSetting->emptyCouEnabled($this->cur_co['Co']['id']));

            $mode = $this->CoEnrollmentAttribute->CoEnrollmentFlow->Co->CoSetting->getSponsorEligibility($this->cur_co['Co']['id']);

            $this->set('vv_sponsor_mode', $mode);
            
            if($mode != SponsorEligibilityEnum::None) {
              // Generate list of sponsors if it's small enough, otherwise we need to use
              // the people picker
              try {
                $this->set('vv_sponsors', $this->CoEnrollmentAttribute->CoEnrollmentFlow->Co->CoPerson->sponsorList($this->cur_co['Co']['id']));
              }
              catch(OverflowException $e) {
                // Switch to people picker, which will happen due to absence of vv_sponsors
              }
              
              // We do need to manually lookup the current Sponsor, since the people picker
              // won't render it by default (and if the sponsor is no longer eligible,
              // the dropdown won't either).
              if(!empty($this->viewVars['co_enrollment_attributes'][0]['CoEnrollmentAttribute']['attribute'])
                 && $this->viewVars['co_enrollment_attributes'][0]['CoEnrollmentAttribute']['attribute'] == 'r:sponsor_co_person_id'
                 && !empty($this->viewVars['co_enrollment_attributes'][0]['CoEnrollmentAttributeDefault'][0]['value'])) {
                // The default value is a CO Person ID
                $args = array();
                $args['conditions']['CoPerson.id'] = $this->viewVars['co_enrollment_attributes'][0]['CoEnrollmentAttributeDefault'][0]['value'];
                $args['contain'] = array('PrimaryName');
                
                $this->set('vv_sponsor', $this->CoEnrollmentAttribute->CoEnrollmentFlow->Co->CoPerson->find('first', $args));
              }
            }

            // Assemble the list of available groups. Note we currently allow any group to be
            // specified (ie: whether or not it's open). The idea is that an Enrollment Flow
            // is defined by an admin, who can correctly select a group. However, it's plausible
            // that we should offer options to filter to open groups, or to a subset of groups
            // as selected by the administrator (especially for scenarios where the value is
            // modifiable).
            
            $args = array();
            $args['conditions']['co_id'] = $coid;
            $args['fields'] = array('CoGroup.id', 'CoGroup.name');
            $args['order'] = array('CoGroup.name asc');
            $args['contain'] = false;
            
            $this->set('vv_groups', $this->CoEnrollmentAttribute->CoEnrollmentFlow->Co->CoGroup->find('list', $args));
            
            if($this->CmpEnrollmentConfiguration->orgIdentitiesFromCOEF()
               && $this->CmpEnrollmentConfiguration->enrollmentAttributesFromEnv()) {
              $this->set('vv_attributes_from_env', true);
            }
          }
        }
      }
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.9
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    // If an enrollment flow is specified, use it to get to the CO ID
    
    $coef = null;
    
    if(!empty($this->params->named['coef'])) {
      $coef = $this->params->named['coef'];
    } elseif(!empty($this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id'])) {
      $coef = $this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id'];
    }
    
    if($coef) {
      // Map CO Enrollment Flow to CO
      
      $coId = $this->CoEnrollmentAttribute->CoEnrollmentFlow->field('co_id',
                                                                    array('id' => $coef));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_enrollment_flows.1'), $coef)));
      }
    }
    
    // Or try the default behavior
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Perform any followups following a write operation.  Note that if this
   * method fails, it must return a warning or REST response, but that the
   * overall transaction is still considered a success (add/edit is not
   * rolled back).
   *
   * @since  COmanage Registry v0.8.1
   * @param  Array Request data
   * @param  Array Current data
   * @param  Array Original request data (unmodified by callbacks)
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
    // Perform a quick check to see if the attribute can no longer have a default attribute.
    // Currently, only types 'g', 'o', 'r', and 'x' can.
    
    if(!empty($curdata['CoEnrollmentAttributeDefault'][0]['id'])) {
      // There is an existing default
      
      $attrinfo = explode(':', $reqdata['CoEnrollmentAttribute']['attribute']);
      
      // This list is also in clearUnassociatedRequestData()
      if($attrinfo[0] != 'g' && $attrinfo[0] != 'o' && $attrinfo[0] != 'r' && $attrinfo[0] != 'x') {
        // Ignore return code
        try {
          $this->CoEnrollmentAttribute->CoEnrollmentAttributeDefault->delete($curdata['CoEnrollmentAttributeDefault'][0]['id'],
                                                                           false);
        } catch (RuntimeException $e) {
          // ignore already-deleted errors and other such results
        }
      }
    }
    
    return true;      
  }
  
  /**
   * Clear unnecessary data from a form submission.
   * - postcondition: $this->request->data updated
   *
   * @since  COmanage Registry v2.0.0
   */
  
  protected function clearUnassociatedRequestData() {
    // Because of the mechanics of how the the form is set up, we may
    // get potentially unrelated data (values used for form rendering),
    // AttributeDefaults even when the object doesn't support attribute
    // defaults, etc. We clear out unrelated objects to avoid problems
    // with checkWriteFollowups trying to delete already deleted (ie: empty)
    // records.
    
    // It's easier to rebuild than to remove keys we don't want
    $requestData = array();
    
    // Always copy the core attribute
    $requestData['CoEnrollmentAttribute'] = $this->request->data['CoEnrollmentAttribute'];
    
    // If the attribute is of the right type, copy the attribute default
    $attrinfo = explode(':', $this->request->data['CoEnrollmentAttribute']['attribute']);
    
    // This list is also in checkWriteFollowups?
    if($attrinfo[0] == 'g' || $attrinfo[0] == 'o' || $attrinfo[0] == 'r' || $attrinfo[0] == 'x') {
      $requestData['CoEnrollmentAttributeDefault'] = $this->request->data['CoEnrollmentAttributeDefault'];
    }
    
    $this->request->data = $requestData;
  }
  
  /**
   * Update an Enrollment Attribute.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - precondition: <id> must exist
   * - postcondition: On GET, $<object>s set (HTML)
   * - postcondition: On POST success, object updated
   * - postcondition: On POST, session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: On POST error, $invalid_fields set (REST)
   *
   * @since  COmanage Registry v2.0.0
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */
  
  function edit($id) {
    if(!empty($this->request->data)) {
      $this->clearUnassociatedRequestData();
    }
    
    parent::edit($id);
  }
    
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Enrollment Attribute?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Enrollment Attribute?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Enrollment Attribute?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Determine which COUs a person can manage.
    if($roles['cmadmin'] || $roles['coadmin']) {
      // Note that here we get id => name while in CoPeopleController we just
      // get a list of names. This is to generate the pop-up on the edit form.
      $p['cous'] = $this->CoPersonRole->Cou->allCous($this->cur_co['Co']['id']);
    } elseif(!empty($roles['admincous'])) {
      $p['cous'] = $roles['admincous'];
    } else {
      $p['cous'] = array();
    }

    // Edit an existing CO Enrollment Attribute's order?
    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Enrollment Attributes?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Modify ordering for display via AJAX 
    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Enrollment Attributes?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.3
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    // Only retrieve attributes in the current enrollment flow
    
    $ret = array();
    
    $ret['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = $this->request->params['named']['coef'];
    
    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.3
   */
  
  function performRedirect() {
    // Append the enrollment flow ID to the redirect
    
    if(isset($this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id']))
      $coefid = $this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id'];
    elseif(isset($this->request->params['named']['coef']))
      $coefid = filter_var($this->request->params['named']['coef'],FILTER_SANITIZE_SPECIAL_CHARS);
    
    $this->redirect(array('controller' => 'co_enrollment_attributes',
                          'action' => 'index',
                          'coef' => $coefid));
  }
}
