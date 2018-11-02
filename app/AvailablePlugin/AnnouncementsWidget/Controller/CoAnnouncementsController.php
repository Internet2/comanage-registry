<?php
/**
 * COmanage Registry CO Announcement Channels Controller
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

class CoAnnouncementsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoAnnouncements";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoAnnouncement.created' => 'desc'
    )
  );
  
  // We don't directly require a CO, but indirectly we do.
  public $requires_co = true;
  
  public $edit_contains = array(
    'CoAnnouncementChannel',
    'PosterCoPerson' => array('PrimaryName')
  );
  
  public $view_contains = array(
    'CoAnnouncementChannel',
    'PosterCoPerson' => array('PrimaryName')
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
    
    // Unclear why this isn't loaded automatically
    $this->loadModel('AnnouncementsWidget.CoAnnouncementChannel');
    
    if(!empty($this->viewVars['vv_tz'])) {
      // Set the current timezone, primarily for beforeSave
      $this->CoAnnouncement->setTimeZone($this->viewVars['vv_tz']);
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
      
      if(!$this->Role->isCoOrCouAdmin($this->Session->read('Auth.User.co_person_id'),
                                      $this->cur_co['Co']['id'])) {
        // Pull the user's group memberships from the session
        $cos = $this->Session->read('Auth.User.cos');
        $coGroupIds = Hash::extract($cos, '{s}[co_id='.$this->cur_co['Co']['id'].'].co_person.CoGroupMember.{n}.co_group_id');
        
        // Filter the available channels based on the user's groups
        $args['conditions']['CoAnnouncementChannel.author_co_group_id'] = $coGroupIds;
      }
      
      $this->set('vv_co_announcement_channels', $this->CoAnnouncement->CoAnnouncementChannel->find("list", $args));
    }
    
    parent::beforeRender();
  }

  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v3.2.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    // Get the CO ID from the Announcement Channel

    if(!empty($this->request->params['pass'][0])) {
      $annId = $this->request->params['pass'][0];
      
      // XXX Can other calculateImpliedCoId()'s do this too? (CO-959))
      return $this->CoAnnouncement->findCoForRecord($annId);
    }

    // Or try the default behavior
    return parent::calculateImpliedCoId();
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
    $roles = $this->Role->calculateCMRoles();             // What was authenticated
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    $canAdd = false;  // Use a separate flag here to facilitate index permissions
    $author = false;
    $reader = false;
    
    // Pull the user's group memberships from the session
    $cos = $this->Session->read('Auth.User.cos');
    $memberGroupIds = Hash::extract($cos, '{s}[co_id='.$this->cur_co['Co']['id'].'].co_person.CoGroupMember.{n}.co_group_id');
    
    if(!empty($this->request->params['pass'][0])) {
      // Pull the authorized groups for this record's announcement channel
      $args = array();
      $args['conditions']['CoAnnouncement.id'] = $this->request->params['pass'][0];
      $args['contain'][] = 'CoAnnouncementChannel';

      $ann = $this->CoAnnouncement->find('first', $args);
      
      if(!empty($ann['CoAnnouncementChannel']['id'])) {
        $author = in_array($ann['CoAnnouncementChannel']['author_co_group_id'], $memberGroupIds);
        $reader = in_array($ann['CoAnnouncementChannel']['reader_co_group_id'], $memberGroupIds);
      }
    } else {
      // For add purposes, a person has author position if they are in any author
      // group. beforeRender() will reduce the set of available Channels.
      $args = array();
      $args['conditions']['CoAnnouncementChannel.co_id'] = $this->cur_co['Co']['id'];
      $args['fields'] = array('DISTINCT CoAnnouncementChannel.author_co_group_id');
      $args['contain'] = false;
      
      $authorGroupIds = Hash::extract($this->CoAnnouncement->CoAnnouncementChannel->find('all', $args),
                                      '{n}.CoAnnouncementChannel.author_co_group_id');
      
      $canAdd = !empty(array_intersect($authorGroupIds, $memberGroupIds));
    }
    
    // Add a new CO Announcement?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $canAdd);
    
    // Delete an existing CO Announcement?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin'] || $author);
    
    // Edit an existing CO Announcement?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin'] || $author);
    
    // View all existing CO Announcement?
    // Any CO Person can get to the index, but the announcements they can see
    // will be determined dynamically
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    
    // View an existing CO Announcement?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $author || $reader);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v3.2.0
   * @return Array An array suitable for use in $this->paginate
   */

  function paginationConditions() {
    $ret = array();
    
    $roles = $this->Role->calculateCMRoles();
    
    // If not an admin, user the user's groups to filter the rendered announcements
    if(!$roles['cmadmin'] && !$roles['coadmin']) {
      // Pull the user's group memberships from the session
      $cos = $this->Session->read('Auth.User.cos');
      $coGroupIds = Hash::extract($cos, '{s}[co_id='.$this->cur_co['Co']['id'].'].co_person.CoGroupMember.{n}.co_group_id');
      
      // There's some overlap here with CoAnnouncementsWidgetsController::display,
      // but it's not clear how to consolidate.
      
      /* Explicit join not required
      $ret['joins'][0]['table'] = 'co_announcement_channels';
      $ret['joins'][0]['alias'] = 'CoAnnouncementChannel';
      $ret['joins'][0]['type'] = 'INNER';
      $ret['joins'][0]['conditions'][0] = 'CoAnnouncement.co_announcement_channel_id=CoAnnouncementChannel.id';
      */
      $ret['conditions']['OR'] = array(
        array('CoAnnouncementChannel.author_co_group_id' => $coGroupIds),
        array('CoAnnouncementChannel.reader_co_group_id' => $coGroupIds),
        array('CoAnnouncementChannel.reader_co_group_id' => NULL)
      );
      $ret['conditions']['CoAnnouncementChannel.status'] = SuspendableStatusEnum::Active;
      
      if(!empty($this->request->params['named']['filter'])
         && $this->request->params['named']['filter'] == 'active') {
        // Only show active announcements (ie: those within the validity window)
        $ret['conditions']['AND'][] = array(
          'OR' => array(
            'CoAnnouncement.valid_from IS NULL',
            'CoAnnouncement.valid_from < ' => date('Y-m-d H:i:s', time())
          )
        );
        $ret['conditions']['AND'][] = array(
          'OR' => array(
            'CoAnnouncement.valid_through IS NULL',
            'CoAnnouncement.valid_through > ' => date('Y-m-d H:i:s', time())
          )
        );
      }
      
      // The index view needs the group memberships to know which actions to offer
      $this->set('vv_groups', $coGroupIds);
    }
    
    return $ret;
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v3.2.0
   * @return Integer The CO ID if found, or -1 if not
   */

  public function parseCOID($data = null) {
    // The default behavior for accepting CO ID with add doesn't work because
    // AppController::parseCOID wants $this->Co to exist, and AppController::calculateImpliedCoId
    // assumes there's a foreign key to examine (co_person_id, etc). In this case,
    // neither is true, but the behavior is closer to parseCOID so that's what
    // we override.
    
    if($this->action == 'add') {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }
    
    return parent::parseCOID($data);
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.2.0
   */

  public function performRedirect() {
    // Redirect back to the default index sort
    
    $this->redirect(array(
      'action'    => 'index',
      'sort'      => 'CoAnnouncement.created',
      'direction' => 'desc',
      'filter'    => 'active',
      'co'        => $this->cur_co['Co']['id']
    ));
  }
}
