<?php
/**
 * COmanage Registry CO Email Address Widgets Controller
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
 * @since         COmanage Registry v4.2.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SDWController", "Controller");

class CoPasswordWidgetsController extends SDWController {
  // Class name, used by Cake
  public $name = "CoPasswordWidgets";
  
  public $uses = array(
    'PasswordWidget.CoPasswordWidget',
    'Authenticator',
    'PasswordAuthenticator',
    'CO'
  );

  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.2.1
   */

  public function beforeRender() {
    // Gather the available PasswordAuthenticator authenticators for the config form
    $args = array();
    $args['conditions']['co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['plugin'] = 'PasswordAuthenticator';
    $this->set('vv_available_authenticators', $this->Authenticator->find('list', $args));
  
    // Pass the config
    $cfg = $this->CoPasswordWidget->getConfig();
    $this->set('vv_config', $cfg);

    parent::beforeRender();
  }
  
  /**
   * Render the widget according to the requested user and current configuration.
   *
   * @since  COmanage Registry v4.2.1
   * @param  Integer $id CO Services Widget ID
   */
  
  public function display($id) {
    // We need the CoPerson ID - with that we can look up the Passwords via 
    // ajax against the API in the web client.
    $this->set('vv_co_person_id', $this->reqCoPersonId);
    $this->set('vv_co_id', $this->cur_co['Co']['id']);

    // Get the password information
    $args = array();
    $args['conditions']['authenticator_id'] = $this->CoPasswordWidget->getConfig()['CoPasswordWidget']['authenticator_id'];
    $pwAuthenticator = $this->PasswordAuthenticator->find('first', $args);
    $this->set('vv_pw_authenticator', $pwAuthenticator);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.2.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();

    // Determine what operations this user can perform
    
    // Construct the permission set for this user, which will also be passed to the view.
    // Ask the parent to calculate the display permission, based on the configuration.
    // Note that the display permission is set at the Dashboard, not Dashboard Widget level.
    $p = $this->calculateParentPermissions($roles);

    // Delete an existing CO Password Widget?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Password Widget?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Password Widget?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
