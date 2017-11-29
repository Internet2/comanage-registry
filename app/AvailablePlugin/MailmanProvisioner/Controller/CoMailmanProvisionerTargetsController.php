<?php
/**
 * COmanage Registry CO Mailman3 Provisioner Targets Controller
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

App::uses("SPTController", "Controller");

class CoMailmanProvisionerTargetsController extends SPTController {
  // Class name, used by Cake
  public $name = "CoMailmanProvisionerTargets";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'serverurl' => 'asc'
    )
  );
  
  public $uses = array('MailmanProvisioner.CoMailmanProvisionerTarget',
                       'EmailAddress');
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   * - postcondition: Set $sponsors
   *
   * @since  COmanage Registry v3.1.0
   */

  public function beforeRender() {
    parent::beforeRender();
    
    $this->set('vv_available_types', $this->EmailAddress->types($this->cur_co['Co']['id'], 'type'));
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */

  function checkWriteDependencies($reqdata, $curdata = null) {
    // Make sure we can connect to the specified server

    try {
      $this->CoMailmanProvisionerTarget->verifyMailmanServer($reqdata['CoMailmanProvisionerTarget']['serverurl'],
                                                             $reqdata['CoMailmanProvisionerTarget']['adminuser'],
                                                             $reqdata['CoMailmanProvisionerTarget']['password']);
    }
    catch(RuntimeException $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
      return false;
    }

    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.1.0
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
