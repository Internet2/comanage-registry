<?php
/**
 * COmanage Registry CO Self Service Email Widgets Controller
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

App::uses("SDWController", "Controller");
//App::import('Controller', 'CoPeopleController');

class CoSelfServiceEmailWidgetsController extends SDWController {
  // Class name, used by Cake
  public $name = "CoSelfServiceEmailWidgets";
  
  public $uses = array(
    'SelfServiceEmailWidget.CoSelfServiceEmailWidget',
    'CoPerson'
  );
  
  /**
   * Render the widget according to the requested user and current configuration.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $id CO Services Widget ID
   */
  
  public function display($id) {
    $cfg = $this->CoSelfServiceEmailWidget->getConfig();
    
    // Pass the config so we know which div to overwrite
    $this->set('vv_config', $cfg);
  
    // Gather the email addresses for the current CoPerson
    $coPersonId = $this->Session->read('Auth.User.co_person_id');
    
    
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

    // Determine what operations this user can perform
    
    // Construct the permission set for this user, which will also be passed to the view.
    // Ask the parent to calculate the display permission, based on the configuration.
    // Note that the display permission is set at the Dashboard, not Dashboard Widget level.
    $p = $this->calculateParentPermissions($roles);

    // Delete an existing CO Self Service Email Widget?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Self Service Email Widget?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Self Service Email Widget?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Self-service permission is true for all EmailAddress types
    $p['selfsvc']['EmailAddress']['*'] = true;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
