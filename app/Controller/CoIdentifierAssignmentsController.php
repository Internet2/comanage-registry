<?php
/**
 * COmanage Registry CO Identifier Assignments Controller
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
  
class CoIdentifierAssignmentsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoIdentifierAssignments";
  
  // When using additional models, we must also specify our own
//  public $uses = array('CoIdentifierAssignment', 'Identifier');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoIdentifierAssignment.type_name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Redirect may be issued
   *
   * @since  COmanage Registry v0.6
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Identifier supports Extended Types. Figure out what types are defined
    // in order for the views to render properly.
    
    $this->set('identifier_types', $this->Co->CoPerson->Identifier->types($this->cur_co['Co']['id'], 'type'));
    
    // Dynamically adjust validation rules to include the current CO ID for dynamic types.
    
    $vrule = $this->CoIdentifierAssignment->validate['identifier_type']['content']['rule'];
    $vrule[1]['coid'] = $this->cur_co['Co']['id'];
    
    $this->CoIdentifierAssignment->validator()->getField('identifier_type')->getRule('content')->rule = $vrule;
  }
  
  /**
   * Obtain all CO Identifier Assignments
   *
   * @since  COmanage Registry v0.9.2
   */

  public function index() {
    parent::index();
    
    if(!$this->request->is('restful')) {
      // Pull the list of CO Person IDs to faciliate "Autogenerate Identifiers for All".
      // We currently pull active records only, for alignment with Petition behavior.
      
      $args = array();
      $args['conditions']['CoPerson.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['CoPerson.status'] = StatusEnum::Active;
      $args['fields'] = array('CoPerson.id', 'CoPerson.status');
      $args['order'] = array('CoPerson.id' => 'asc');
      $args['contain'] = false;
      
      $this->set('vv_co_people', $this->CoIdentifierAssignment->Co->CoPerson->find('list', $args));
    }
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
    
    // Add a new CO Identifier Assignment?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Assign identifiers to all CO People?
    $p['assignall'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Identifier Assignment?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Identifier Assignment?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Identifier Assignments?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Identifier Assignment?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
