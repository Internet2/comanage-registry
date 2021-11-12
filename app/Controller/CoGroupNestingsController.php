<?php
/**
 * COmanage Registry CO Group Nestings Controller
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoGroupNestingsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoGroupNestings";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoGroupNesting.co_group_id' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  // Edit and view need Name for rendering view
  public $edit_contains = array(
    'CoGroup',
    'ParentCoGroup'
  );

  public $view_contains = array(
    'CoGroup',
    'ParentCoGroup'
  );
  
  // We need to track the group ID under certain circumstances to enable performRedirect
  private $gid = null;
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v3.3.0
   */

  function beforeRender() {
    parent::beforeRender();
    
    // Pull the parent group
    
    $args = array();
    $args['conditions']['CoGroup.id'] = $this->request->params['named']['cogroup'];
    $args['contain'] = false;
    
    $this->set('vv_parent_group', $this->CoGroupNesting->CoGroup->find('first', $args));
    
    // Pull the list of available groups
    
    $args = array();
    $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
    // While beforeSave will enforce all logic, we at least filter the current group
    $args['conditions']['CoGroup.id <>'] = $this->request->params['named']['cogroup'];
    $args['order'] = 'CoGroup.name ASC';
    $args['contain'] = false;
    
    $this->set('vv_available_groups', $this->CoGroupNesting->CoGroup->find('list', $args));

    // Provide the ID of the current group if available
    if(isset($this->gid)) {
      $this->set('vv_current_group', $this->gid);
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();             // What was authenticated
    
    // Initially only CO(U) Admins can create nested groups. This is largely
    // because the authz logic gets complicated when considering non-overlapping
    // owner groups. eg: If Group A has owners 1 and 2, and Group B has owners
    // 2 and 3, can 1 or 3 manage a nested group relationship between A and B?
    
    // If we figure out the rules for more complex authz, see
    // CoGroupMembersController::isAuthorized for potential logic to start from.
    
    // Store the (parent) group ID in the controller object since performRedirect may need it
    
    if($this->action == 'add') {
      $this->gid = filter_var($this->request->params['named']['cogroup'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK);
    } elseif(($this->action == 'delete' || $this->action == 'edit' || $this->action == 'view')
             && isset($this->request->params['pass'][0])) {
      $this->gid = $this->CoGroupNesting->field('target_co_group_id', array('CoGroupNesting.id' => $this->request->params['pass'][0]));
    }
    
    // Is this specified group read only?
    $readOnly = ($this->gid ? $this->CoGroupNesting->CoGroup->readOnly($this->gid) : false);
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();

    // Get the groups listing
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    
    // Add a new nested group?
    $p['add'] = !$readOnly && ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
    
    // Delete a nested group?
    $p['delete'] = !$readOnly && ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.3.0
   */
  
  function performRedirect() {
    // Figure out where to redirect back to based on how we were called
    
    if(isset($this->gid)) {
      $params = array('controller' => 'co_groups',
                      'action'     => 'nest',
                      $this->gid
                     );
    } else {
      // A perhaps not ideal default, but we shouldn't get here
      $params = array('controller' => 'co_groups',
                      'action'     => 'index',
                      'co'         => $this->cur_co['Co']['id']
                     );
    }
    
    $this->redirect($params);
  }
}
