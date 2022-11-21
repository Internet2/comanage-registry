<?php
/**
 * COmanage Registry CO Recovery Widgets Controller
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SDWController", "Controller");

class CoRecoveryWidgetsController extends SDWController {
  // Class name, used by Cake
  public $name = "CoRecoveryWidgets";
  
  public $uses = array(
    'RecoveryWidget.CoRecoveryWidget',
    'Authenticator',
    'CoMessageTemplate',
    'Identifier'
  );
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   * - postcondition: Set $sponsors
   *
   * @since  COmanage Registry v4.1.0
   */

  public function beforeRender() {
    parent::beforeRender();
    
    // Determine the set of available Authenticators that support self service
    $this->set('vv_available_authenticators', $this->Authenticator->getSelfServiceEnabled($this->cur_co['Co']['id']));

    // Determine the set of available Identifier Types
    $this->set('vv_available_types', $this->Identifier->types($this->cur_co['Co']['id'], 'type'));
    
    // Provide a list of message templates
    $args = array();
    $args['conditions']['co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['status'] = SuspendableStatusEnum::Active;
    $args['conditions']['context'] = array(
      MessageTemplateEnum::Authenticator,
      MessageTemplateEnum::Plugin
    );
    $args['order'] = array('CoMessageTemplate.description ASC');
    
    $this->set('vv_message_templates', $this->CoMessageTemplate->find('list', $args));
  }
  
  /**
   * Render the widget according to the requested user and current configuration.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Integer $id CO Recovery Widget ID
   */
  
  public function display($id) {
    $cfg = $this->CoRecoveryWidget->getConfig();
    
    // If Authenticator Reset is enabled, pass the URL to the direct credential change URL.
    // Note for now we only support PasswordAuthenticator, but in the future we might need
    // to figure out the plugin and it's action.

    if(!empty($cfg['CoRecoveryWidget']['authenticator_id'])) {
      $resetUrl = array(
        'plugin'          => 'password_authenticator',
        'controller'      => 'passwords',
        'action'          => 'manage',
        'authenticatorid' => $cfg['CoRecoveryWidget']['authenticator_id']
      );

      $this->set('vv_authenticator_change_url', $resetUrl);
    }

    // Pass the config so we know which div to overwrite
    $this->set('vv_config', $cfg);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();

    // Determine what operations this user can perform
    
    // Construct the permission set for this user, which will also be passed to the view.
    // Ask the parent to calculate the display permission, based on the configuration.
    // Note that the display permission is set at the Dashboard, not Dashboard Widget level.
    $p = $this->calculateParentPermissions($roles);

    // Delete an existing CO Recovery Widget?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Recovery Widget?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Recovery Widget?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
