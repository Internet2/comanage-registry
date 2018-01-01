<?php
/**
 * COmanage Registry CO Notifications Controller
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
 * @since         COmanage Registry v0.8.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoNotificationsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoNotifications";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoNotifications.created' => 'desc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // For rendering views, we need more information than just the various ID numbers
  // stored in a petition.
  public $view_contains = array(
    'SubjectCoPerson' => 'PrimaryName',
    'ActorCoPerson' => 'PrimaryName',
    'RecipientCoPerson' => 'PrimaryName',
    'RecipientCoGroup',
    'ResolverCoPerson' => 'PrimaryName'
  );

  /**
   * View a specific notification.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id CO Notification ID
   */
  public function view($id) {
    parent::view($id);
    $this->set('title_for_layout', _txt('ct.co_notifications.1'));
  }

  /**
   * Acknowledge the specified notification.
   * - postcondition: CO Invitation status set to 'Acknowledged'
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer $id CO Notification ID
   */
  
  public function acknowledge($id) {
    try {
      $this->CoNotification->acknowledge($id, $this->Session->read('Auth.User.co_person_id'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->Flash->set(_txt('rs.nt.ackd'), array('key' => 'success'));
    
    // Not really clear where to redirect to
    $this->redirect("/");
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.8.4
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    $copersontype = null;
    
    if(!empty($this->request->params['pass'][0])) {
      // Determine CO via Subject CO Person ID or Subject CO Group ID
      
      $args = array();
      $args['conditions']['CoNotification.id'] = $this->request->params['pass'][0];
      $args['contain'][] = 'SubjectCoPerson';
      $args['contain'][] = 'SubjectCoGroup';
      
      $coNote = $this->CoNotification->find('first', $args);
      
      if(!empty($coNote['SubjectCoPerson']['co_id'])) {
        return $coNote['SubjectCoPerson']['co_id'];
      } elseif(!empty($coNote['SubjectCoGroup']['co_id'])) {
        return $coNote['SubjectCoGroup']['co_id'];
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_notifications.1'),
                                                      filter_var($this->request->params['pass'][0],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    } elseif(!empty($this->request->params['named']['actorcopersonid'])) {
      $copersontype = 'actorcopersonid';
    } elseif(!empty($this->request->params['named']['recipientcopersonid'])) {
      $copersontype = 'recipientcopersonid';
    } elseif(!empty($this->request->params['named']['resolvercopersonid'])) {
      $copersontype = 'resolvercopersonid';
    } elseif(!empty($this->request->params['named']['subjectcopersonid'])) {
      $copersontype = 'subjectcopersonid';
    }
    
    if($copersontype) {
      // Determine CO via specified CO Person ID
      $coId = $this->CoNotification->ActorCoPerson->field('co_id',
                                                          array('id' => $this->request->params['named'][$copersontype]));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_people.1'),
                                                      filter_var($this->request->params['named'][$copersontype],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }
    
    return null;
  }
  
  /**
   * Cancel the specified notification.
   * - postcondition: CO Invitation status set to 'Canceled'
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer $id CO Notification ID
   */
  
  public function cancel($id) {
    try {
      $this->CoNotification->cancel($id, $this->Session->read('Auth.User.co_person_id'));
      $this->Flash->set(_txt('rs.nt.cxld'), array('key' => 'success'));    
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    // Not really clear where to redirect to
    $this->redirect("/");
  }
  
  /**
   * Obtain all Standard Objects (of the model's type).
   * - postcondition: $<object>s set on success (REST or HTML), using pagination (HTML only)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.9
   */
  
  public function index() {
    parent::index();
    
    if(!$this->request->is('restful')) {
      // Attempt to generate a more descriptive page title. If we fail at any point
      // we'll automatically revert back to the default title.
      
      if(!empty($this->cur_co_person_id)) {
        // Look up the subject's name
        
        $args = array();
        $args['conditions']['SubjectCoPerson.id'] = $this->cur_co_person_id;
        $args['contain'][] = 'PrimaryName';
        
        $cop = $this->CoNotification->SubjectCoPerson->find('first', $args);
        
        if(!empty($cop)) {
          global $cm_texts, $cm_lang;
          
          $this->set('title_for_layout', _txt('fd.not.for', array(generateCn($cop['PrimaryName']),
                                                                  $this->cur_request_type_txt,
                                                                  $this->cur_request_filter_txt)));
          
          $this->set('vv_request_type', $this->cur_request_type_key);
          $this->set('vv_co_person_id', $this->cur_co_person_id);
          $this->set('vv_notification_statuses', $cm_texts[ $cm_lang ]['en.status.not']);
        }
      }
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.8.4
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Acknowledge (but not necessary resolve!) this notification?
    $p['acknowledge'] = ($roles['cmadmin']
                         || $roles['coadmin']
                         || (!empty($this->request->params['pass'][0])
                             && $this->Role->isNotificationRecipient($this->request->params['pass'][0],
                                                                     $roles['copersonid'])));
    
    // Cancel this notification?
    $p['cancel'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || (!empty($this->request->params['pass'][0])
                        && $this->Role->isNotificationSender($this->request->params['pass'][0],
                                                             $roles['copersonid'])));
    
    // View all notifications (or a subset)?
    $p['index'] = ($roles['cmadmin']
                   || $roles['coadmin']
                   // Anyone can see their own notifications. We have to be careful to
                   // check the CO Person ID in the same order as paginationConditions
                   // so that someone can't view someone else's notifications.
                   // (paginationConditions will use the first named parameter specified.)
                   || (isset($this->request->params['named']['actorcopersonid'])
                       && $this->request->params['named']['actorcopersonid'] == $roles['copersonid'])
                   || (isset($this->request->params['named']['recipientcopersonid'])
                       && $this->request->params['named']['recipientcopersonid'] == $roles['copersonid'])
                   || (isset($this->request->params['named']['resolvercopersonid'])
                       && $this->request->params['named']['resolvercopersonid'] == $roles['copersonid'])
                   || (isset($this->request->params['named']['subjectcopersonid'])
                       && $this->request->params['named']['subjectcopersonid'] == $roles['copersonid']));
    
    // View this notification?
    $p['view'] = ($roles['cmadmin']
                  || $roles['coadmin']
                  || (!empty($this->request->params['pass'][0])
                      && $this->Role->isNotificationParticipant($this->request->params['pass'][0],
                                                                $roles['copersonid'])));
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.8.5
   * @return Array An array suitable for use in $this->paginate
   * @throws InvalidArgumentException
   */
  
  function paginationConditions() {
    // Only retrieve notifications for the requested subject
    
    $ret = array();
    
    if(!empty($this->request->query['status'])) {
      // Status is expected to be the corresponding short code. (Or omitted, for unresolved,
      // or "all" for all.) An unknown status code should generate some noise, but nothing more.
      
      $status = filter_var($this->request->query['status'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK);
      
      if($status == 'all') {
        $this->cur_request_filter_txt = _txt('fd.all');
      } else {
        $this->cur_request_filter_txt = _txt('en.status.not', null, $status);
        $ret['conditions']['CoNotification.status'] = $status;
      }
    } else {
      // Default is to show notifications in pending status
      $this->cur_request_filter_txt = _txt('fd.unresolved');
      $ret['conditions']['CoNotification.status'] = array(NotificationStatusEnum::PendingAcknowledgment,
                                                          NotificationStatusEnum::PendingResolution);
    }
    
    // Keep this order in sync with isAuthorized (index check), above
    if(isset($this->request->params['named']['actorcopersonid'])) {
      // Track the CO Person ID we use for rendering later
      $this->cur_co_person_id = $this->request->params['named']['actorcopersonid'];
      $this->cur_request_type_txt = _txt('fd.actor');
      $this->cur_request_type_key = 'actorcopersonid';
      
      $ret['conditions']['CoNotification.actor_co_person_id'] = $this->request->params['named']['actorcopersonid'];
    } elseif(isset($this->request->params['named']['recipientcopersonid'])) {
      $this->cur_co_person_id = $this->request->params['named']['recipientcopersonid'];
      $this->cur_request_type_txt = _txt('fd.recipient');
      $this->cur_request_type_key = 'recipientcopersonid';
      
      // We also need to add in any active groups that the requested person is a member of
      
      $args = array();
      $args['joins'][0]['table'] = 'co_group_members';
      $args['joins'][0]['alias'] = 'CoGroupMember';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'RecipientCoGroup.id=CoGroupMember.co_group_id';
      $args['conditions']['CoGroupMember.co_person_id'] = $this->request->params['named']['recipientcopersonid'];
      $args['conditions']['CoGroupMember.member'] = true;
      // Only pull currently valid group memberships
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_from IS NULL',
          'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_through IS NULL',
          'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['conditions']['RecipientCoGroup.status'] = StatusEnum::Active;
      $args['fields'] = array('RecipientCoGroup.id', 'RecipientCoGroup.id');
      $args['contain'] = false;
      
      $groups = $this->CoNotification->RecipientCoGroup->find('list', $args);
      
      if(!empty($groups)) {
        $ret['conditions']['OR']['CoNotification.recipient_co_group_id'] = $groups;
        $ret['conditions']['OR']['CoNotification.recipient_co_person_id'] = $this->request->params['named']['recipientcopersonid'];
      } else {
        $ret['conditions']['CoNotification.recipient_co_person_id'] = $this->request->params['named']['recipientcopersonid'];
      }
    } elseif(isset($this->request->params['named']['resolvercopersonid'])) {
      $this->cur_co_person_id = $this->request->params['named']['resolvercopersonid'];
      $this->cur_request_type_txt = _txt('fd.resolver');
      $this->cur_request_type_key = 'resolvercopersonid';
      
      $ret['conditions']['CoNotification.resolver_co_person_id'] = $this->request->params['named']['resolvercopersonid'];
    } elseif(isset($this->request->params['named']['subjectcopersonid'])) {
      $this->cur_co_person_id = $this->request->params['named']['subjectcopersonid'];
      $this->cur_request_type_txt = _txt('fd.subject');
      $this->cur_request_type_key = 'subjectcopersonid';
      
      $ret['conditions']['CoNotification.subject_co_person_id'] = $this->request->params['named']['subjectcopersonid'];
    } elseif(isset($this->request->params['named']['subjectcogroupid'])) {
      $this->cur_co_group_id = $this->request->params['named']['subjectcogroupid'];
      $this->cur_request_type_txt = _txt('fd.subject');
      $this->cur_request_type_key = 'subjectcogroupid';
      
      $ret['conditions']['CoNotification.subject_co_group_id'] = $this->request->params['named']['subjectcogroupid'];
    } else {
      throw new InvalidArgumentException(_txt('er.notprov'));
    }
    
    return $ret;
  }
}
