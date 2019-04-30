<?php
/**
 * COmanage Registry API Users Controller
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
 * @since         COmanage Registry v0.8.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class ApiUsersController extends StandardController {
  // Class name, used by Cake
  public $name = "ApiUsers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'ApiUser.username' => 'asc'
    )
  );
  
  public $uses = array('ApiUser', 'Identifier');

  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - precondition:
   * - postcondition: Auth component is configured
   * - postcondition:
   *
   * @since  COmanage Registry v3.3.0
   * @throws UnauthorizedException (REST)
   */

  function beforeFilter() {
    parent::beforeFilter();

    if(!empty($this->viewVars['vv_tz'])) {
      // Set the current timezone, primarily for beforeSave
      $this->ApiUser->setTimeZone($this->viewVars['vv_tz']);
    }
  }
  
  /**
   * Generate an API Key.
   *
   * @since  COmanage Registry v3.3.0
   * @param  int $id API User ID
   */
  
  public function generate($id) {
    // We don't autogenerate after add because we'd have to interfere with performRedirect.
    
    try {
      $args = array();
      $args['conditions']['ApiUser.id'] = $id;
      $args['contain'] = false;
      
      $this->set('vv_api_user', $this->ApiUser->find('first', $args));
      $this->set('vv_api_key', $this->ApiUser->generateKey($id));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.8.4
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new API User?
    $p['add'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Delete an existing API User?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing API User?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Generate a new API Key?
    $p['generate'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all existing API User?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View an existing API User?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
