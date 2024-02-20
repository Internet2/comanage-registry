<?php
/**
 * COmanage Registry Env Source Controller
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

App::uses("SOISController", "Controller");

class EnvSourcesController extends SOISController {
  // Class name, used by Cake
  public $name = "EnvSources";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      // We don't really need pagination...
      'env_name_given' => 'asc'
    )
  );
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v3.1.0
   */
  
  public function beforeRender() {
    parent::beforeRender();

    $this->set('vv_available_attributes', $this->EnvSource->availableAttributes());

    $OrgIdentity = ClassRegistry::init('OrgIdentity');
    $mTypesAffiliation = $OrgIdentity->types($this->viewVars["cur_co"]["Co"]["id"], 'affiliation');
    $this->set('vv_affiliation_types', $mTypesAffiliation);

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
