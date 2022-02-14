<?php
/**
 * COmanage Registry CO TAndC Agreements Controller
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoTAndCAgreementsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoTAndCAgreements";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoTermsAndConditions.agreement_time' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $requires_person = true;

  /**
   * Add a Standard Object.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - postcondition: On success, new Object created
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: $<object>_id or $invalid_fields set (REST)
   *
   * @since  COmanage Registry v2.0.0
   */
  
  public function add() {
    // This will record an agreement. Based heavily on StandardContoller::add.
    
    if(!$this->request->is('restful')) {
      throw new RuntimeException(_txt('er.notimpl'));
    }
    
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    $modelid = $this->modelKey . "_id";
    $modelpl = Inflector::tableize($req);
    
    $data = array();
    
    // Validate. We generally want validation to occur, but the API
    // doesn't specify agreement time or identifier -- we'll automatically
    // determine those. We temporarily alter validation rules so
    // validation succeeds.
    
    foreach(array('agreement_time', 'identifier') as $f) {
      $xfield = $model->validator()->getField($f);
      
      if($xfield) {
        $xfield->getRule('content')->required = false;
        $xfield->getRule('content')->allowEmpty = true;
      }
    }
    
    try {
      $this->Api->checkRestPost($this->cur_co["Co"]["id"]);
      $data[$req] = $this->Api->getData();
    }
    catch(InvalidArgumentException $e) {
      // See if we have invalid fields
      $invalidFields = $this->Api->getInvalidFields();
      
      if($invalidFields) {
        // Pass them to the view
        $this->set('invalid_fields', $invalidFields);
      }
      
      $this->Api->restResultHeader($e->getCode(), $e->getMessage());
      return;
    }
    
    switch($this->checkPersonID("calculate", $data)) {
      case -1:
        $this->Api->restResultHeader(403, "Person Does Not Exist");
        return;
        break;
      case 0:
        $this->Api->restResultHeader(403, "No Person Specified");
        return;
        break;
      default:
        break;
    }
    
    $err = "";

    try {
      $model->record($data['CoTAndCAgreement']['co_terms_and_conditions_id'],
                     $data['CoTAndCAgreement']['co_person_id'],
                     $this->Session->read('Auth.User.co_person_id'),
                     $this->Session->read('Auth.User.username'));
      
      $this->Api->restResultHeader(201, "Added");
      $this->set($modelid, $model->id);
    }
    catch(Exception $e) {
      $err = filter_var($e->getMessage(),FILTER_SANITIZE_SPECIAL_CHARS);
      
      $fs = $model->invalidFields();
      
      if(!empty($fs)) {
        $this->Api->restResultHeader(400, "Invalid Fields");
        $this->set('invalid_fields', $fs);
      } else {
        $this->Api->restResultHeader(500, "Other Error");
      }
    }
  }
  
  /**
   * Obtain all Standard Objects (of the model's type).
   * - postcondition: $<object>s set on success (REST or HTML), using pagination (HTML only)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v2.0.0
   */
  
  public function index() {
    if(!$this->request->is('restful')) {
      throw new RuntimeException(_txt('er.notimpl'));
    }
    
    parent::index();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.8.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // This controller will typically only be called via the REST API
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO T&C Agreement?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO T&C Agreement?
    $p['delete'] = false; // Nobody is permitted to remove agreements
    
    // Edit an existing CO T&C Agreement?
    $p['edit'] = false; // // Nobody is permitted to remove agreements
    
    // View all existing CO T&C Agreements?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO T&C Agreement?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * Retrieve a Standard Object.
   * - precondition: <id> must exist
   * - postcondition: $<object>s set (with one member)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v2.0.0
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */
  
  public function view($id) {
    if(!$this->request->is('restful')) {
      throw new RuntimeException(_txt('er.notimpl'));
    }
    
    parent::view($id);
  }
}
