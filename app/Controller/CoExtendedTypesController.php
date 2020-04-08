<?php
/**
 * COmanage Registry CO Extended Types Controller
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
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoExtendedTypesController extends StandardController {
  // Class name, used by Cake
  public $name = "CoExtendedTypes";
  
  // When using additional models, we must also specify our own
  public $uses = array('CoExtendedType',
                       'Address',
                       'CoPersonRole',
                       'EmailAddress',
                       'Identifier',
                       'Name',
                       'TelephoneNumber',
                       'Url',
                       'CoEnrollmentAttribute',
                       'CoExpirationPolicy',
                       'CoIdentifierAssignment',
                       'CoDepartment',
                       'CoSelfServicePermission');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Insert the default types for an Extended Type for the current CO.
   *
   * @since  COmanage Registry v0.9.2
   */
  
  public function addDefaults() {
    if(!empty($this->request->query['attr'])) {
      try {
        $this->CoExtendedType->addDefault($this->cur_co['Co']['id'],
                                          filter_var($this->request->query['attr'],FILTER_SANITIZE_SPECIAL_CHARS));
        
        $this->Flash->set(_txt('rs.types.defaults'), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    } else {
      $this->Flash->set(_txt('er.notprov'), array('key' => 'error'));
    }
    
    // redirect back to index page
    $this->performRedirect();
  }
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v0.9.2
   */
  
  function beforeRender() {
    parent::beforeRender();
    
    // Provide a list of supported attributes for the attribute select menu
    $this->set('vv_supported_attrs', $this->CoExtendedType->supportedAttrs());
    
    // Provide a hint to the view if this attribute is suspendable or not
    if($this->action == 'edit') {
      $inUse = false;
      
      if(!empty($this->CoExtendedType->data['CoExtendedType']['status'])
         && $this->CoExtendedType->data['CoExtendedType']['status'] == SuspendableStatusEnum::Active) {
        try {
          $this->typeInUse($this->CoExtendedType->data['CoExtendedType']['attribute'],
                           $this->CoExtendedType->data['CoExtendedType']['name']);
        }
        catch(Exception $e) {
          // Type is in use
          
          $inUse = true;
        }
      }
      
      $this->set('vv_type_in_use', $inUse);
    } elseif($this->action == 'add') {
      // No need to perform the database query
      
      $this->set('vv_type_in_use', false);
    }
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.6
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    // Don't allow delete if there are any attributes still using this type.
    // The attribute is provided in Model.field format, split it up.
    
    if(!empty($curdata['CoExtendedType']['attribute'])
       && !empty($curdata['CoExtendedType']['name'])) {
      try {
        $this->typeInUse($curdata['CoExtendedType']['attribute'],
                         $curdata['CoExtendedType']['name']);
      }
      catch(Exception $e) {
        // Type is in use
        
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(403, "Type In Use");
        } else {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
        }
        
        return false;
      }
    }
    
    return true;
  }
  
  /**
   * Perform any dependency checks requiTred prior to a write (add/edit) operation.
   *
   * @since  COmanage Registry v0.6
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    if($this->request->is('restful')) {
      // Make sure the specified attribute is valid now, since we use it before we'll
      // get to a save
      
      $this->CoExtendedType->set($reqdata);
      
      if(!$this->CoExtendedType->validates(array('fieldList' => array('attribute')))) {
        $this->Api->restResultHeader(400, "Invalid Fields");
        $this->set('invalid_fields', $this->CoExtendedType->invalidFields());
        
        return false;
      }
    }
    
    if(!isset($curdata)
       || ($curdata['CoExtendedType']['name'] != $reqdata['CoExtendedType']['name'])) {
      // Make sure the name doesn't exist for the attribute for this CO
      $args['conditions']['CoExtendedType.name'] = $reqdata['CoExtendedType']['name'];
      $args['conditions']['CoExtendedType.co_id'] = $reqdata['CoExtendedType']['co_id'];
      $args['conditions']['CoExtendedType.attribute'] = $reqdata['CoExtendedType']['attribute'];
      
      $x = $this->CoExtendedType->find('count', $args);
      
      if($x > 0) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(403, "Name In Use");
        } else {
          $this->Flash->set(_txt('er.et.exists', array($reqdata['CoExtendedType']['name'])), array('key' => 'error'));
        }
        
        return false;
      }
    }
    
    if(!empty($curdata['CoExtendedType']['status'])
       && $reqdata['CoExtendedType']['status'] == SuspendableStatusEnum::Suspended
       && $curdata['CoExtendedType']['status'] == SuspendableStatusEnum::Active) {
      // Transitioning from active to suspend. It might be preferable to allow
      // existing values to remain and just stop assigning new values, but we can't
      // do this if the type is in use because some things will break, due to the use
      // of CoExtendedType->active(). Specifically, rendering of existing values will
      // fail, and mapping to eduPersonAffiliation will fail.
      
      try {
        $this->typeInUse($curdata['CoExtendedType']['attribute'],
                         $curdata['CoExtendedType']['name']);
      }
      catch(Exception $e) {
        // Type is in use
        
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(403, "Type In Use");
        } else {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
        }
        
        return false;
      }
    }
    
    if($curdata['CoExtendedType']['attribute'] == 'Name.type'
       && $curdata['CoExtendedType']['name'] == NameEnum::Official
       && $reqdata['CoExtendedType']['name'] != NameEnum::Official) {
      // NameEnum::official cannot be renamed (CO-955)
      
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "Type In Use");
      } else {
        $this->Flash->set(_txt('er.nm.official.et'), array('key' => 'error'));
      }
      
      return false;
    }
    
    return true;
  }
  
  /**
   * Obtain all Extended Types of the currently specified attribute.
   *
   * @since  COmanage Registry v0.6
   */

  public function index() {
    if(!$this->request->is('restful')) {
      if(empty($this->request->query['attr'])) {
        // Make sure an attribute is selected. We'll arbitrarily pick identifier,
        // since it was the first Extended Type.
        
        $this->redirect(array('action' => 'index',
                              'co' => $this->cur_co['Co']['id'],
                              '?' => array(
                                'attr' => 'Identifier.type'
                              )));
      }
    }
    
    parent::index();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.6
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Extended Type?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Add/restore default Types?
    $p['addDefaults'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Extended Type?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Extended Type?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Extended Type?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Extended Type?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.6
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    // Only retrieve types for the current attribute and for the current CO
    
    $ret = array();
    
    if(isset($this->cur_co)) {
      $ret['conditions']['CoExtendedType.co_id'] = $this->cur_co['Co']['id'];
    }
    
    if(!empty($this->request->query['attr'])) {
      $ret['conditions']['CoExtendedType.attribute'] = filter_var($this->request->query['attr'],FILTER_SANITIZE_SPECIAL_CHARS);
    }
    
    return $ret;
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v0.9.2
   * @return Integer The CO ID if found, or -1 if not
   */
  
  public function parseCOID($data = null) {
    if($this->action == 'addDefaults') {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }
    
    return parent::parseCOID();
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.6
   */
  
  function performRedirect() {
    // Make sure the attribute is included in the URL
    
    if(!empty($this->request->query['attr'])) {
      $attr = $this->request->query['attr'];
    } elseif(!empty($this->request->params['named']['attr'])) {
      $attr = $this->request->params['named']['attr'];
    } elseif(!empty($this->request->data['CoExtendedType']['attribute'])) {
      $attr = $this->request->data['CoExtendedType']['attribute'];
    } else {
      $attr = "unknown";
    }
    
    $this->redirect(array('action' => 'index',
                          'co' => $this->cur_co['Co']['id'],
                          '?' => array(
                            'attr' => filter_var($attr,FILTER_SANITIZE_SPECIAL_CHARS)
                          )));
  }
  
  /**
   * Perform checks to see if a given Extended Type is in use.
   *
   * @since  COmanage Registry v0.9.2
   * @param  String $attribute Attribute name, of Model.field forw
   * @param  String $name      Extended Type name
   * @return Boolean False if the type is not in use
   * @throws OverflowException
   */
  
  protected function typeInUse($attribute, $typeName) {
    // It would be easier if extended types were referenced by foreign keys
    // (co_extended_type_id instead of directly referencing the type, eg "official"),
    // but for historical reason (types were previously hardcoded enums) they aren't.
    // CO-956.
    
    // Before we do anything, Name::official cannot be deleted (CO-955).
    
    if($attribute == 'Name.type' && $typeName == NameEnum::Official) {
      throw new OverflowException(_txt('er.nm.official.et'));
    }
    
    // Check with the relevant model
    
    $attr = explode('.', $attribute);
    $model = $attr[0];
    
    if($this->$model->typeInUse($attribute,
                                $typeName,
                                $this->cur_co['Co']['id'])) {
      $supportedAttributes = $this->CoExtendedType->supportedAttrs();
      throw new OverflowException(_txt('er.et.inuse-a', array($typeName, $supportedAttributes[$attribute])));
    }
    
    // Next make sure the attribute isn't in use in an Enrollment Attribute
    
    if($this->CoEnrollmentAttribute->typeInUse($attribute,
                                               $typeName,
                                               $this->cur_co['Co']['id'])) {
      throw new OverflowException(_txt('er.et.inuse.ef', array($typeName)));
    }
    
    // We don't, however, check CoPetitionAttributes because those are historical
    // and not direct/active references. As such, they can continue to exist as
    // strings.
    
    // Make sure the attribute isn't in use by a Self Service Permission
    
    if($this->CoSelfServicePermission->typeInUse($attribute,
                                                 $typeName,
                                                 $this->cur_co['Co']['id'])) {
      throw new OverflowException(_txt('er.et.inuse-a', array($typeName, _txt('ct.co_self_service_permissions.1'))));
    }
    
    // Or by any Identifier Assignments (if attribute == Identifier.type)
    if($this->CoIdentifierAssignment->typeInUse($attribute,
                                                $typeName,
                                                $this->cur_co['Co']['id'])) {
      throw new OverflowException(_txt('er.et.inuse-a', array($typeName, _txt('ct.co_identifier_assignments.1'))));
    }
    
    // Or by any Expiration Policy (if attribute == affiliation)
    if($this->CoExpirationPolicy->typeInUse($attribute,
                                            $typeName,
                                            $this->cur_co['Co']['id'])) {
      throw new OverflowException(_txt('er.et.inuse-a', array($typeName, _txt('ct.co_expiration_policies.1'))));
    }
    
    return false;
  }
}
