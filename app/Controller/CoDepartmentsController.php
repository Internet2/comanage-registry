<?php
/**
 * COmanage Registry CO Departments Controller
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoDepartmentsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoDepartments";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoDepartment.name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $delete_contains = array(
  );

  public $edit_contains = array(
    'Address',
    'EmailAddress',
    'Identifier',
    'TelephoneNumber',
    'Url'
  );
  
  public $view_contains = array(
    'Address',
    'EmailAddress',
    'Identifier',
    'TelephoneNumber',
    'Url'
  );
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v3.1.0
   */

  function beforeRender() {
    if(!$this->request->is('restful')) {
      // Pull the list of available groups
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['CoGroup.status'] = SuspendableStatusEnum::Active;
      $args['order'] = array('CoGroup.name ASC');

      $this->set('vv_co_groups', $this->Co->CoGroup->find("list", $args));

      // and COUs
      $args = array();
      $args['conditions']['Cou.co_id'] = $this->cur_co['Co']['id'];
      $args['order'] = array('Cou.name ASC');

      $this->set('vv_cous', $this->CoDepartment->Cou->find("list", $args));
      
      // Mappings for extended types
      $this->set('vv_addresses_types', $this->CoDepartment->Address->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_email_addresses_types', $this->CoDepartment->EmailAddress->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_identifiers_types', $this->CoDepartment->Identifier->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_telephone_numbers_types', $this->CoDepartment->TelephoneNumber->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_urls_types', $this->CoDepartment->Url->types($this->cur_co['Co']['id'], 'type'));
    }

    parent::beforeRender();
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.2
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();             // What was authenticated
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Department?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Department?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Department?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Department?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    
    // View an existing CO Department?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
