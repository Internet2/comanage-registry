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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SDWController", "Controller");

class CoEmailAddressWidgetsController extends SDWController {
  // Class name, used by Cake
  public $name = "CoEmailAddressWidgets";
  
  public $uses = array(
    'EmailAddressWidget.CoEmailAddressWidget',
    'EmailAddress',
    'CO',
    'CoMessageTemplate'
  );
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  public function beforeRender() {
    parent::beforeRender();
  
    // Pass the config
    $cfg = $this->CoEmailAddressWidget->getConfig();
    $this->set('vv_config', $cfg);
  }
  
  /**
   * Render the widget according to the requested user and current configuration.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Integer $id CO Services Widget ID
   */
  
  public function display($id) {
    // We need only the CoPerson ID - with that we can look up the Email Addresses via 
    // ajax against the API in the web client.
    $coPersonId = $this->reqCoPersonId;
    $this->set('vv_co_person_id', $coPersonId);
    $this->set('vv_co_id', $this->cur_co['Co']['id']);

    // Gather the available email address types
    $availableTypes = $this->EmailAddress->types($this->cur_co['Co']['id'], 'type');
    $this->set('vv_available_types', $availableTypes);
  }
  
  public function edit($id) {
    parent::edit($id);
    
    // Gather the available email address types for the config form
    $this->set('vv_available_types', $this->EmailAddress->types($this->cur_co['Co']['id'], 'type'));
  
    // Gather message templates for the config form
    $args = array();
    $args['conditions']['status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;
    $this->set('vv_message_templates', $this->CoMessageTemplate->find('list',$args));
    
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

    // Delete an existing CO Email Widget?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Email Widget?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Email Widget?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}