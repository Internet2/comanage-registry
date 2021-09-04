<?php
/**
 * COmanage Registry CO SQL Provisioner Targets Controller
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

App::uses("SPTController", "Controller");

class CoSqlProvisionerTargetsController extends SPTController {
  // Class name, used by Cake
  public $name = "CoSqlProvisionerTargets";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'server_id' => 'asc'
    )
  );
  
  /**
   * Reapply the Target Database Schema.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $id CoSqlProvisionerTarget ID
   */
  
  public function reapply($id) {
    try {
      $serverId = $this->CoSqlProvisionerTarget->field('server_id', array('CoSqlProvisionerTarget.id' => $id));
      
      if(!$serverId) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_sql_provisioner_targets.1'), $id)));
      }
      
      $this->CoSqlProvisionerTarget->applySchema($serverId);
      
      $this->Flash->set(_txt('pl.sqlprovisioner.reapply.ok'), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
  
  /**
   * Resync all Reference Data, including CO Groups.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $id CoSqlProvisionerTarget ID
   */
  
  public function resync($id) {
    try {
      $this->CoSqlProvisionerTarget->syncAllReferenceData($this->cur_co['Co']['id'], true);
      
      $this->Flash->set(_txt('pl.sqlprovisioner.resync.ok'), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->performRedirect();
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
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Delete an existing CO SQL Provisioning Target?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO SQL Provisioning Target?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO SQL Provisioning Targets?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Reapply the target schema?
    $p['reapply'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Resync the reference data?
    $p['resync'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO SQL Provisioning Target?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
