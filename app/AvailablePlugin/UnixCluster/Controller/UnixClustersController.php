<?php
/**
 * COmanage Registry Unix Clusters Controller
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

App::uses("SCController", "Controller");

class UnixClustersController extends SCController {
  // Class name, used by Cake
  public $name = "UnixClusters";
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v3.3.0
   */

  function beforeRender() {
    parent::beforeRender();

    if(!$this->request->is('restful')) {
      $this->set('vv_identifier_types', $this->UnixCluster->Cluster->Co->CoPerson->Identifier->types($this->cur_co['Co']['id'], 'type'));
      
      if($this->action != 'add' && !empty($this->request->params['pass'][0])) {
        // Pull the list of available Cluster groups
        $this->set('vv_available_groups', $this->UnixCluster->UnixClusterGroup->availableUnixGroups($this->request->params['pass'][0]));
      } else {
        // On add, we won't have any available groups yet since it wouldn't have
        // been possible to define any yet
        $this->set('vv_available_groups', array());
      }
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
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Delete an existing Unix Cluster?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit an existing Unix Cluster?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View all existing Unix Clusters?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Unix Cluster?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
