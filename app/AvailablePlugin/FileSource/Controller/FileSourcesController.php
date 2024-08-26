<?php
/**
 * COmanage Registry File Source Controller
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

App::uses("SOISController", "Controller");
App::uses("FileSourceBackendCSV", "FileSource.Model");

class FileSourcesController extends SOISController {
  // Class name, used by Cake
  public $name = "FileSources";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'filepath' => 'asc'
    )
  );
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    // Make sure we can read the specified file
    
    if(!is_readable($reqdata['FileSource']['filepath'])) {
      $this->Flash->set(_txt('er.filesource.read', array($reqdata['FileSource']['filepath'])),
                        array('key' => 'error')); 
      return false;
    }

    // Make sure the file header is correct
    $FSBackend = new FileSourceBackendCSV($reqdata['FileSource']);
    $FSBackend->setCoId($curdata["OrgIdentitySource"]["co_id"]);
    try {
      $FSBackend->readFieldConfig();
    } catch (Exception $e) {
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
   * @since  COmanage Registry v0.8
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    $coadmin = false;
    
    if($roles['coadmin'] && !$this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      // CO Admins can only manage org identity sources if org identities are NOT pooled
      $coadmin = true;
    }
    
    // Delete an existing CO Provisioning Target?
    $p['delete'] = $roles['cmadmin'] || $coadmin;
    
    // Edit an existing CO Provisioning Target?
    $p['edit'] = $roles['cmadmin'] || $coadmin;
    
    // View all existing CO Provisioning Targets?
    $p['index'] = $roles['cmadmin'] || $coadmin;
    
    // View an existing CO Provisioning Target?
    $p['view'] = $roles['cmadmin'] || $coadmin;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
