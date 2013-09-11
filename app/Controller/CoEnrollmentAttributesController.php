<?php
/**
 * COmanage Registry CO Enrollment Attributes Controller
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
      'CoEnrollmentAttribute.attribute' => 'asc'
    )
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
    if(!empty($this->request->data) &&
       (!isset($this->request->data['CoEnrollmentAttribute']['ordr'])
        || $this->request->data['CoEnrollmentAttribute']['ordr'] == '')) {
      $args['fields'][] = "MAX(ordr) as m";
      $args['conditions']['CoEnrollmentAttribute.co_enrollment_flow_id'] = Sanitize::paranoid($this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id']);
      $args['order'][] = "m";
      
      $o = $this->CoEnrollmentAttribute->find('first', $args);
      $n = 1;
      
      if(!empty($o)) {
        $n = $o[0]['m'] + 1;
      }
      
      if(!empty($o))
        $this->request->data['CoEnrollmentAttribute']['ordr'] = $n;
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
    
    // Strictly speaking, this controller doesn't require a CO except to redirect/render views.
    // Figure out the CO ID associated with the current enrollment flow. We'll specifically
    // not set $this->cur_co since it will break things like pagination setup.
    
    $coefid = -1;
    
    if(isset($this->request->params['named']['coef']))
      $coefid = $this->request->params['named']['coef'];
    elseif(isset($this->request->data))
      $coefid = $this->request->data['CoEnrollmentAttribute']['co_enrollment_flow_id'];
    
    $this->CoEnrollmentAttribute->CoEnrollmentFlow->id = $coefid;
    
    $this->set('vv_coefid', Sanitize::html($coefid));
    
    $coid = $this->CoEnrollmentAttribute->CoEnrollmentFlow->field('co_id');
    
    if(!empty($coid))
    {
      $this->set('vv_coid', $coid);
      
      // Assemble the set of available attributes for the view to render
      
      $this->set('vv_available_attributes', $this->CoEnrollmentAttribute->availableAttributes($coid));
      
      // And pull details of extended attributes so views can determine types
      
      $args = array();
      $args['conditions']['co_id'] = $coid;
      $args['fields'] = array('CoExtendedAttribute.name', 'CoExtendedAttribute.type');
      $args['contain'] = false;
      
      $this->set('vv_ext_attr_types',
                 $this->CoEnrollmentAttribute->CoEnrollmentFlow->Co->CoExtendedAttribute->find('list', $args));
      
      // Assemble the list of available COUs
      
      $this->set('vv_cous', $this->CoEnrollmentAttribute->CoEnrollmentFlow->Co->Cou->allCous($coid));
      
      // Assemble the list of available affiliations
      
      $this->set('vv_affiliations', $cm_texts[ $cm_lang ]['en.affil']);
      
      // Assemble the list of available Sponsors
      
      $this->set('vv_sponsors', $this->CoEnrollmentAttribute->CoEnrollmentFlow->Co->CoPerson->sponsorList($coid));
      
      // Assemble the list of available groups. Note we currently allow any group to be
      // specified (ie: whether or not it's open). The idea is that an Enrollment Flow
      // is defined by an admin, who can correctly select a group. However, it's plausible
      // that we should offer options to filter to open groups, or to a subset of groups
      // as selected by the administrator (especially for scenarios where the value is
      // modifiable).
      
      $args = array();
      $args['conditions']['co_id'] = $coid;
      $args['fields'] = array('CoGroup.id', 'CoGroup.name');
      $args['contain'] = false;
      
      $this->set('vv_groups', $this->CoEnrollmentAttribute->CoEnrollmentFlow->Co->CoGroup->find('list', $args));
    }
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
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null) {
    // Perform a quick check to see if the attribute can no longer have a default attribute.
    // Currently, only types 'o', 'r', and 'x' can.
    
    if(!empty($curdata['CoEnrollmentAttributeDefault'][0]['id'])) {
      // There is an existing default
      
      $attrinfo = explode(':', $reqdata['CoEnrollmentAttribute']['attribute']);
      
      if($attrinfo[0] != 'o' && $attrinfo[0] != 'r' && $attrinfo[0] != 'x') {
        // Ignore return code
        $this->CoEnrollmentAttribute->CoEnrollmentAttributeDefault->delete($curdata['CoEnrollmentAttributeDefault'][0]['id'],
                                                                           false);
      }
    }
    
    return true;      
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
   * Modify order of Enrollment Attributes; essentially like the index page plus an AJAX call
   *
   * @since  COmanage Registry v0.8.2
   */
  
  function order() {
    // Show more for ordering
    $this->paginate['limit'] = 200;
    
    parent::index();
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.3
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    // Only retrieve attributes in the current enrollment flow
    
    return(array(
      'CoEnrollmentAttribute.co_enrollment_flow_id' => $this->request->params['named']['coef']
    ));
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
      $coefid = Sanitize::html($this->request->params['named']['coef']);
    
    $this->redirect(array('controller' => 'co_enrollment_attributes',
                          'action' => 'index',
                          'coef' => $coefid));
  }

  /**
   * Save changes to the ordering made via drag/drop; called via AJAX.
   * - postcondition: Database modified
   *
   * @since  COmanage Registry v0.8.2
   */

  public function reorder() {
    foreach ($this->data['CoEnrollmentAttributeId'] as $key => $value) {
      $this->CoEnrollmentAttribute->id = $value;
      $this->CoEnrollmentAttribute->saveField("ordr",$key + 1);
    }
    
    exit();
  }
}
