<?php
/**
 * COmanage Registry CO Homedir Provisioner Targets Controller
 *
 * Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoHomedirProvisionerTargetsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoHomedirProvisionerTargets";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'logfile' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // XXX docblock
  // XXX move to some sort of parent controller?
  
  function add() {
    if(!$this->restful) {
      // In case we error out, we need to re-propagate the co provisioning target ID
      // to the form. XXX this needs to be done for all ProvisionerTargets.
      
      if(isset($this->request->params['named']['ptid'])) {
        $this->set('co_provisioning_target_id', $this->request->params['named']['ptid']);
      } elseif(isset($this->request->data['CoHomedirProvisionerTarget']['co_provisioning_target_id'])) {
        $this->set('co_provisioning_target_id', $this->request->data['CoHomedirProvisionerTarget']['co_provisioning_target_id']);
      }
    }
    
    parent::add();
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.9
   */
  // XXX move to some sort of parent controller?
  
  function performRedirect() {
    // We generally want to return to CoProvisioningTargetController
    
    $target = array();
    $target['plugin'] = null;
    $target['controller'] = "co_provisioning_targets";
    $target['action'] = 'index';
    $target['co'] = $this->cur_co['Co']['id'];
    
    $this->redirect($target);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.9
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Delete an existing CO Provisioning Target?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Provisioning Target?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Provisioning Targets?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Provisioning Target?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
