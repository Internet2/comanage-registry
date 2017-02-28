<?php
/**
 * COmanage Registry API Users Controller
 *
 * Copyright (C) 2013-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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

  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.8.4
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    if(!isset($curdata['ApiUser']['username'])
       || $curdata['ApiUser']['username'] != $reqdata['ApiUser']['username']) {
      // Make sure identifier doesn't conflict with an existing identifier
      
      $args = array();
      $args['conditions']['Identifier.identifier'] = $reqdata['ApiUser']['username'];
      $args['conditions']['Identifier.login'] = true;
      $args['conditions']['Identifier.status'] = StatusEnum::Active;
      $args['contain'] = false;
      
      if($this->Identifier->find('count', $args)) {
        $this->Flash->set(_txt('er.ia.exists',
                               array(filter_var($reqdata['ApiUser']['username'],FILTER_SANITIZE_SPECIAL_CHARS))),
                          array('key' => 'error'));
        
        return false;
      }
      
      // Or with an existing API user
      
      $args = array();
      $args['conditions']['ApiUser.username'] = $reqdata['ApiUser']['username'];
      $args['contain'] = false;
      
      if($this->ApiUser->find('count', $args)) {
        $this->Flash->set(_txt('er.ia.exists',
                               array(filter_var($reqdata['ApiUser']['username'],FILTER_SANITIZE_SPECIAL_CHARS))),
                          array('key' => 'error'));
        
        return false;
      }
    }
    
    return true;
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
    $p['add'] = $roles['cmadmin'];
    
    // Delete an existing API User?
    $p['delete'] = $roles['cmadmin'];
    
    // Edit an existing API User?
    $p['edit'] = $roles['cmadmin'];
    
    // View all existing API User?
    $p['index'] = $roles['cmadmin'];
    
    // View an existing API User?
    $p['view'] = $roles['cmadmin'];
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
