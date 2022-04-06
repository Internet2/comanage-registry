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
    'CO'
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
  
    // Gather the email addresses for the current CoPerson for the first 
    // read-only display. After the first load, updates and reloads will
    // be performed via ajax.
    $coPersonId = $this->Session->read('Auth.User.co_person_id');
    $args = [];
    $args['conditions'] = array('EmailAddress.co_person_id' => $coPersonId);
    $args['contain'] = false;
    $emailAddresses = $this->EmailAddress->find('all',$args);
    $this->set('vv_email_addresses', $emailAddresses);
    $this->set('vv_co_person_id', array($coPersonId));
  
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
    
    // Self-service permission is true for all EmailAddress types
    $p['selfsvc']['EmailAddress']['*'] = true;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}