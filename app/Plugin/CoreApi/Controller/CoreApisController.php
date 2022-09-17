<?php
/**
 * COmanage Registry Core APIs Controller
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoreApisController extends StandardController {
  // Class name, used by Cake
  public $name = "CoreApis";
    
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'api' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $view_contains = array('ApiUser');
  
  public $edit_contains = array('ApiUser');
  
  public $delete_contains = array();
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: requires_co possibly set
   *
   * @since  COmanage Registry v4.0.0
   */

  function beforeFilter() {
    parent::beforeFilter();

    // Get a pointer to our model name
    $req = $this->modelClass;
    $model = $this->$req;

    // Dynamically adjust validation rules to include the current CO ID for dynamic types.
    // This is a common case for provisioner configuration, but this could plausibly go
    // in StandardController.

    foreach($model->validate as $attr => $acfg) {
      if(isset($acfg['content']['rule'][0])
         && $acfg['content']['rule'][0] == 'validateExtendedType') {
        // Inject the current CO so validateExtendedType() works correctly

        $vrule = $acfg['content']['rule'];
        $vrule[1]['coid'] = $this->cur_co['Co']['id'];

        $model->validator()->getField($attr)->getRule('content')->rule = $vrule;
      }
    }
  }
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */

  function beforeRender() {
    parent::beforeRender();

    // Pull the list of available API Users
    $this->set('vv_api_users', $this->CoreApi->Co->ApiUser->availableApiUsers($this->cur_co['Co']['id']));
    
    // and identifier types
    $this->set('vv_identifier_types', $this->CoreApi->Co->CoPerson->Identifier->types($this->cur_co['Co']['id'], 'type'));
    
    $this->set('vv_api_endpoint', Router::url('/', true) . 'api/co/' . $this->cur_co['Co']['id'] . '/core/v1');
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Core API?
    $p['add'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Delete a Core API?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit a Core API?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all Core APIs?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View an existing Core API?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
