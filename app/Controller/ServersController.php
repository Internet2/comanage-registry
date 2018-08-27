<?php
/**
 * COmanage Registry Servers Controller
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class ServersController extends StandardController {
  // Class name, used by Cake
  public $name = "Servers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 50,
    'order' => array(
      'Server.description' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $view_contains = array(
    'HttpServer',
    'LdapServer',
    'Oauth2Server',
    'SqlServer'
  );
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v3.2.0
   */
  
  public function beforeRender() {
    parent::beforeRender();
    
    $this->set('vv_server_type_models', $this->Server->serverTypeModels);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.2.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Server?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Delete an existing Server?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit an existing Server?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all Servers?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View this Server?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.2.0
   */
  
  function performRedirect() {
    // On add, redirect to the edit view for the appropriate server type.
    
    if($this->action == 'add' && !empty($this->Server->data)) {
      $smodel = $this->Server->serverTypeModels[ $this->Server->data['Server']['server_type'] ];
      
      $target = array(
        'controller' => Inflector::tableize($smodel),
        'action'     => 'edit',
        $this->Server->data[$smodel]['id']
      );
      
      $this->redirect($target);
    } else {
      parent::performRedirect();
    }
  }
}
