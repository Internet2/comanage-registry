<?php
/**
 * COmanage Registry CO Notifications Widgets Controller
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

class CoNotificationsWidgetsController extends SDWController {
  // Class name, used by Cake
  public $name = "CoNotificationsWidgets";
  
  public $uses = array("NotificationsWidget.CoNotificationsWidget", "CoNotification");
  
  /**
   * Render the widget according to the requested user and current configuration.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $id CO Notifications Widget ID
   */
  
  public function display($id) {
    $cfg = $this->CoNotificationsWidget->getConfig();
    
    $maxWidgets = $cfg['CoNotificationsWidget']['max_notifications'];
    
    if(!$maxWidgets) {
      $maxWidgets = 10;
    }
    
    // Pull the number of notifications configured for the current user. Note AppController
    // pulls notifications for the default layout, but we don't seem to tickle that code
    // from here (so vv_my_notifications is not set).
    
    $this->set('vv_widget_notifications', array_slice($this->CoNotification->pending($this->reqCoPersonId), 0, $maxWidgets));
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

    // Delete an existing CO Notifications Widget?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Notifications Widget?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View all existing CO Notifications Widget?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Notifications Widget?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
