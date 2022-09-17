<?php
/**
 * COmanage Registry API Source Controller
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

App::uses("SOISController", "Controller");

class ApiSourcesController extends SOISController {
  // Class name, used by Cake
  public $name = "ApiSources";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'sor_label' => 'asc'
    )
  );
  
  public $edit_contains = array(
    'OrgIdentitySource'
  );

  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v3.3.0
   */
  
  function beforeRender() {
    parent::beforeRender();
    
    // Pull the list of available API Users
    $this->set('vv_api_users', $this->ApiSource->OrgIdentitySource->Co->ApiUser->availableApiUsers($this->cur_co['Co']['id']));
    
    $sorLabel = "";
    
    if(!empty($this->viewVars['api_sources'][0]['OrgIdentitySource']['sor_label'])) {
      $sorLabel = "/" . $this->viewVars['api_sources'][0]['OrgIdentitySource']['sor_label'];
    }
    
    $this->set('vv_api_endpoint', Router::url('/', true) . 'api_source/' . $this->cur_co['Co']['id'] . '/v1/sorPeople' . $sorLabel);
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
    
    $coadmin = false;
    
    if($roles['coadmin'] && !$this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      // CO Admins can only manage org identity sources if org identities are NOT pooled
      $coadmin = true;
    }
    
    // Delete an existing Source?
    $p['delete'] = $roles['cmadmin'] || $coadmin;
    
    // Edit an existing Source?
    $p['edit'] = $roles['cmadmin'] || $coadmin;
    
    // View all existing Sources?
    $p['index'] = $roles['cmadmin'] || $coadmin;
    
    // View an existing Source?
    $p['view'] = $roles['cmadmin'] || $coadmin;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
