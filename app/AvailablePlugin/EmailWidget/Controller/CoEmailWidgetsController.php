<?php
/**
 * COmanage Registry CO Email Widgets Controller
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

class CoEmailWidgetsController extends SDWController {
  // Class name, used by Cake
  public $name = "CoEmailWidgets";
  
  public $uses = array(
    'EmailWidget.CoEmailWidget',
    'EmailAddress',
    'CO',
    'CoMessageTemplate'
  );
  
  /**
   * Render the widget according to the requested user and current configuration.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Integer $id CO Services Widget ID
   */
  
  public function display($id) {
    $cfg = $this->CoEmailWidget->getConfig();
    
    // Pass the config so we know which div to overwrite
    $this->set('vv_config', $cfg);
  
    // We need only the CoPerson ID - with that we can look up the Email Addresses via 
    // ajax against the API in the web client.
    $coPersonId = $this->reqCoPersonId;
    $this->set('vv_co_person_id', $coPersonId);
    $this->set('vv_co_id', $this->cur_co['Co']['id']);

    // Gather the available email address types
    $availableTypes = $this->EmailAddress->types($this->cur_co['Co']['id'], 'type');
    
  /* XXX probably need to work with the self-service permissions below
    if(!empty($this->viewVars['permissions']['selfsvc'])
      && !$this->Role->isCoOrCouAdmin($coPersonId,
        $this->cur_co['Co']['id'])) {
        // For models supporting self service permissions, adjust the available types
        // in accordance with the configuration (but not if self is an admin)
      
        foreach(array_keys($availableTypes) as $k) {
          // We use edit for the permission even if we're adding or viewing because
          // add has different semantics for calculatePermission (whether or not the person
          // can add a new item).
          if(!$this->Co->CoPermission->calculatePermission($this->cur_co['Co']['id'],
            'EmailAddress',
            'edit',
            $k)) {
            unset($availableTypes[$k]);
          }
        }
      }
    */
      $this->set('vv_available_types', $availableTypes);
    /*}*/
  }
  
  public function edit($id) {
    parent::edit($id);
    
    // Pass the config
    $cfg = $this->CoEmailWidget->getConfig();
    $this->set('vv_config', $cfg);
    
    // Gather the available email address types for the config form
    $availableTypes = $this->EmailAddress->types($this->cur_co['Co']['id'], 'type');
    $this->set('vv_available_types', $availableTypes);
  
    // Gather message templates for the config form
    $args = array();
    $args['conditions']['status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;
    $messageTemplates = $this->CoMessageTemplate->find('list',$args);
    $this->set('vv_message_templates', $messageTemplates);
    
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
