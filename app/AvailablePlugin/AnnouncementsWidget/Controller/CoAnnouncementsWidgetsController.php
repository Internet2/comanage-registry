<?php
/**
 * COmanage Registry CO Announcements Widgets Controller
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

class CoAnnouncementsWidgetsController extends SDWController {
  // Class name, used by Cake
  public $name = "CoAnnouncementsWidgets";
  
  public $uses = array(
    "AnnouncementsWidget.CoAnnouncementsWidget",
    "AnnouncementsWidget.CoAnnouncement",
    "AnnouncementsWidget.CoAnnouncementChannel"
  );
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Auth component is configured
   *
   * @since  COmanage Registry v3.2.0
   * @throws UnauthorizedException (REST)
   */

  function beforeFilter() {
    parent::beforeFilter();

    if($this->action == 'display'
       && !empty($this->request->params['pass'][0])) {
      // If the announcement channel does not have a reader group specified,
      // the channel is public.
      
      $channelId = $this->CoAnnouncementsWidget->field('co_announcement_channel_id',
                                                       array('CoAnnouncementsWidget.id' => $this->request->params['pass'][0]));
      
      if($channelId) {
        $readerGroupId = $this->CoAnnouncementChannel->field('reader_co_group_id',
                                                             array('CoAnnouncementChannel.id' => $channelId));
        
        if(!$readerGroupId) {
          // No reader group, so channel is public (including outside the CO)
          $this->Auth->allow('display');
        }
      }
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v3.2.0
   */
 
  function beforeRender() {
    if(!$this->request->is('restful')) {
      // Pull the list of available channels
      $args = array();
      $args['conditions']['CoAnnouncementChannel.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['CoAnnouncementChannel.status'] = SuspendableStatusEnum::Active;
      $args['order'] = array('CoAnnouncementChannel.name ASC');
      
      // We need to manually load ChangelogBehavior because CoAnnouncementsWidget is not changelog enabled
      $this->CoAnnouncementsWidget->CoAnnouncementChannel->Behaviors->load('Changelog');
      $this->set('vv_co_announcement_channels', $this->CoAnnouncementsWidget->CoAnnouncementChannel->find("list", $args));
    }
    
    parent::beforeRender();
  }
  
  /**
   * Render the widget according to the requested user and current configuration.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $id CO Notifications Widget ID
   */
  
  public function display($id) {
    $cfg = $this->CoAnnouncementsWidget->getConfig();
    
    $channel = $cfg['CoAnnouncementsWidget']['co_announcement_channel_id'];
    
    // There's some overlap here with CoAnnouncementsController::paginationConditions,
    // but it's not clear how to consolidate.
    
    $args = array();
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoAnnouncement.valid_from IS NULL',
        'CoAnnouncement.valid_from < ' => date('Y-m-d H:i:s', time())
      )
    );
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoAnnouncement.valid_through IS NULL',
        'CoAnnouncement.valid_through > ' => date('Y-m-d H:i:s', time())
      )
    );
    if($channel) {
      $args['conditions']['CoAnnouncement.co_announcement_channel_id'] = $channel;
      $args['conditions']['CoAnnouncementChannel.status'] = SuspendableStatusEnum::Active;
    } else {
      $roles = $this->Role->calculateCMRoles();
      
      // If not an admin, user the user's groups to filter the rendered announcements
      if(!$roles['cmadmin'] && !$roles['coadmin']) {
        // Pull the user's group memberships from the session
        $cos = $this->Session->read('Auth.User.cos');
        $coGroupIds = Hash::extract($cos, '{s}[co_id='.$this->cur_co['Co']['id'].'].co_person.CoGroupMember.{n}.co_group_id');
        
        $args['conditions']['OR'] = array(
          array('CoAnnouncementChannel.reader_co_group_id' => $coGroupIds),
          array('CoAnnouncementChannel.reader_co_group_id' => null)
        );
      }
    }
    $args['order'] = array('CoAnnouncement.created DESC');
    // For a large number of announcements, containable might not be the most
    // optimal approach
    $args['contain'] = array(
      'CoAnnouncementChannel', 
      'PosterCoPerson' => 'PrimaryName'
    );
    
    $this->set('vv_widget_announcements', $this->CoAnnouncement->find('all', $args));
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
    
    // Delete an existing CO Announcements Widget?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Announcements Widget?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Announcements Widget?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
