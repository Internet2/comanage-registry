<?php
/**
 * COmanage Registry CO Notification Model
 *
 * Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoNotification extends AppModel {
  // Define class name for cake
  public $name = "CoNotification";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "SubjectCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'subject_co_person_id'
    ),
    "ActorCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'actor_co_person_id'
    ),
    "RecipientCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'recipient_co_person_id'
    ),
    "RecipientCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'recipient_co_group_id'
    ),
    "ResolverCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'resolver_co_person_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "comment";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'subject_co_person_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'actor_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'recipient_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'recipient_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'resolver_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'action' => array(
      'rule' => array('maxLength', 4),
      'required' => false,
      'allowEmpty' => true
    ),
    'comment' => array(
      'rule' => array('maxLength', 160),
      'required' => false,
      'allowEmpty' => true
    ),
    'source_url' => array(
      'rule' => array('maxLength', 160),
      'required' => false,
      'allowEmpty' => true
    ),
    'source_controller' => array(
      'rule' => array('maxLength', 80),
      'required' => false,
      'allowEmpty' => true
    ),
    'source_action' => array(
      'rule' => array('maxLength', 80),
      'required' => false,
      'allowEmpty' => true
    ),
    'source_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(NotificationStatusEnum::Acknowledged,
                                      NotificationStatusEnum::Canceled,
                                      NotificationStatusEnum::Deleted,
                                      NotificationStatusEnum::PendingAcknowledgment,
                                      NotificationStatusEnum::PendingResolution,
                                      NotificationStatusEnum::Resolved)),
      'required' => true
    ),
    'notification_time' => array(
      'rule' => 'notEmpty'
    ),
    'resolution_time' => array(
      'rule' => 'notEmpty'
    )
  );
  
  /**
   * Acknowledge an outstanding notification
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer  $id         CO Notification ID
   * @param  Integer  $coPersonId CO Person ID of person ackowledging the notification
   * @return Boolean  True if notification is acknowledged
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function acknowledge($id,
                              $coPersonId) {
    return $this->processResolution($id, $coPersonId, NotificationStatusEnum::Acknowledged);
  }
  
  /**
   * Cancel an outstanding notification
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer  $id         CO Notification ID
   * @param  Integer  $coPersonId CO Person ID of person canceling the notification
   * @return Boolean  True if notification is canceled
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function cancel($id,
                         $coPersonId) {
    return $this->processResolution($id, $coPersonId, NotificationStatusEnum::Canceled);
  }
  
  /**
   * Expunge a Participant from a Notification. This operation should only be performed
   * as part of a CO Person expunge. A History Record will be created for the subject
   * indicating that a participant was removed, without indicating who. This function
   * should be called from within a transaction.
   *
   * @since  COmanage Registry v0.8.5
   * @param  integer $id                  CO Notification ID
   * @param  string  $role                One of 'actor', 'recipient', or 'resolver'
   * @param  integer $expungerCoPersonId  CO Person ID of person performing expunge
   * @return boolean True on success
   * @throws InvalidArgumentException
   */
  
  public function expungeParticipant($id,
                                     $role,
                                     $expungerCoPersonId) {
    $this->id = $id;
    
    $subjectCoPersonId = $this->field('subject_co_person_id');
    
    if(!$subjectCoPersonId) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_notifications.1'), $id)));
    }
    
    $this->saveField($role.'_co_person_id', null);
    
    $this->ActorCoPerson->HistoryRecord->record($subjectCoPersonId,
                                                null,
                                                null,
                                                $expungerCoPersonId,
                                                ActionEnum::NotificationParticipantExpunged,
                                                _txt('rs.nt.expunge', array($id, $role)));
    
    return true;
  }
  
  /**
   * Obtain pending notifications for a CO Person
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer  $coPersonId CO Person ID to obtain notifications for
   * @return Array    Set of pending notifications
   */
  
  public function pending($coPersonId) {
    // We need the groups the person is a member of. There's probably a clever join
    // to pull from co_notifications in one query, put the obvious joins aren't working.
    
    $args = array();
    $args['joins'][0]['table'] = 'co_group_members';
    $args['joins'][0]['alias'] = 'CoGroupMember';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'RecipientCoGroup.id=CoGroupMember.co_group_id';
    $args['conditions']['RecipientCoGroup.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['contain'] = false;
    
    $groups = $this->RecipientCoGroup->find('list', $args);
    
    $args = array();
    $args['conditions']['OR']['CoNotification.recipient_co_person_id'] = $coPersonId;
    if(!empty($groups)) {
      $args['conditions']['OR']['CoNotification.recipient_co_group_id'] = array_keys($groups);
    }
    $args['conditions']['CoNotification.status'] = array(NotificationStatusEnum::PendingAcknowledgment,
                                                         NotificationStatusEnum::PendingResolution);
    $args['order']['CoNotification.created'] = 'desc';
    $args['contain'] = false;
    
    return $this->find('all', $args);
  }
  
  /**
   * Process the resolution of an outstanding notification
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer  $id         CO Notification ID
   * @param  Integer  $coPersonId CO Person ID of person ackowledging the notification
   * @param  String   $resolution NotificationStatusEnum
   * @return Boolean  True if notification is resolved
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function processResolution($id,
                                       $coPersonId,
                                       $resolution) {
    // First make sure the notification is pending acknowledgment
    
    $args = array();
    $args['conditions']['CoNotification.id'] = $id;
    $args['contain'] = false;
    
    $not = $this->find('first', $args);
    
    if(!isset($not['CoNotification']['status'])) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_notifications.1'), $id)));
    }
    
    // Acknowledged permitted only if PendingAcknowledgment
    if($resolution == NotificationStatusEnum::Acknowledged
       && $not['CoNotification']['status'] != NotificationStatusEnum::PendingAcknowledgment) {
      throw new InvalidArgumentException(_txt('er.nt.ack'));
    }
    
    // Canceled permitted only if Pending
    if($resolution == NotificationStatusEnum::Canceled
       && ($not['CoNotification']['status'] != NotificationStatusEnum::PendingAcknowledgment
           && $not['CoNotification']['status'] != NotificationStatusEnum::PendingResolution)) {
      throw new InvalidArgumentException(_txt('er.nt.cxl'));
    }
    
    // Resolved permitted only if PendingResolution
    if($resolution == NotificationStatusEnum::Resolved
       && $not['CoNotification']['status'] != NotificationStatusEnum::PendingResolution) {
      throw new InvalidArgumentException(_txt('er.nt.res'));
    }
    
    // Update the notification. We don't authorize $coPersonId since the controller
    // should have done that already.
    
    // updateAll has some annoying characteristics, such as not updating the modified
    // time (which Cake otherwise does automatically) and doing strange things with
    // joins to belongsTo assocations. So we'll do use saveField multiple times, even
    // though it's slightly less efficient.
    
    $this->id = $id;
    $this->saveField('status', $resolution);
    $this->saveField('resolver_co_person_id', $coPersonId);
    $this->saveField('resolution_time', date('Y-m-d H:i:s'));
    
    // Create a history record
    
    $hAction = null;
    $hComment = "";
    
    switch($resolution) {
      case NotificationStatusEnum::Acknowledged:
        $hAction = ActionEnum::NotificationAcknowledged;
        // use rs.nt.delivered.email if an email address was found
        $hComment = _txt('rs.nt.ackd-a', array($not['CoNotification']['comment']));
        break;
      case NotificationStatusEnum::Canceled:
        $hAction = ActionEnum::NotificationCanceled;
        $hComment = _txt('rs.nt.cxld-a', array($not['CoNotification']['comment']));
        break;
      case NotificationStatusEnum::Resolved:
        $hAction = ActionEnum::NotificationResolved;
        $hComment = _txt('rs.nt.resd-a', array($not['CoNotification']['comment']));
        break;
      default:
        throw new InvalidArgumentException(_txt('er.unknown', $resolution));
        break;
    }
    
    try {
      $this->ResolverCoPerson->HistoryRecord->record($not['CoNotification']['subject_co_person_id'],
                                                     null,
                                                     null,
                                                     $coPersonId,
                                                     $hAction,
                                                     $hComment);
    }
    catch(Exception $e) {
      throw new RuntimeException($e->getMessage());
    }
    
    return true;
  }
  
  /**
   * Register a new notification
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer $subjectCoPersonId CO Person ID of subject of notification
   * @param  Integer $actorCoPersonId   CO Person ID of actor who generated notification (or null)
   * @param  String  $recipientType     "coperson" or "cogroup"
   * @param  Integer $recipientId       CO Person ID or CO Group ID of recipient, according to $recipientType
   * @param  String  $action            ActionEnum describing notification
   * @param  String  $comment           Human readable notification comment
   * @param  Mixed   $source            Link to source to review/resolve notification; may be a string (url) or cake-style array of controller+action+id (note: 'id' must be specified as array key)
   * @param  Boolean $mustResolve       If true, the notification cannot be acknowledged, only resolved via $source
   * @return Array CO Notification ID(s)
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function register($subjectCoPersonId,
                           $actorCoPersonId,
                           $recipientType,
                           $recipientId,
                           $action,
                           $comment,
                           $source,
                           $mustResolve=false) {
    // Create the notification. Perhaps this should be embedded in a transaction.
    
    $n = array();
    
    $n['CoNotification']['subject_co_person_id'] = $subjectCoPersonId;
    
    if(!empty($actorCoPersonId)) {
      $n['CoNotification']['actor_co_person_id'] = $actorCoPersonId;
    }
    
    // While we only record one CoNotification, we need to send email to each
    // recipient and create individual history records
    
    $recipients = array();
    
    if($recipientType == 'coperson') {
      $n['CoNotification']['recipient_co_person_id'] = $recipientId;
      
      $args = array();
      $args['conditions']['RecipientCoPerson.id'] = $recipientId;
      $args['contain'][] = 'EmailAddress';
      
      $recipients[] = $this->RecipientCoPerson->find('first', $args);
    } elseif($recipientType == 'cogroup') {
      $n['CoNotification']['recipient_co_group_id'] = $recipientId;
      
      // A clever contain will perform our join, but nest our data
      $args = array();
      $args['conditions']['RecipientCoGroup.id'] = $recipientId;
      $args['contain']['CoGroupMember']['CoPerson'] = 'EmailAddress';
      
      $gr = $this->RecipientCoGroup->find('first', $args);
      
      if(!empty($gr['CoGroupMember'])) {
        foreach($gr['CoGroupMember'] as $gm) {
          // Move EmailAddress up a level, as for 'coperson'
          $recipients[] = array(
            'RecipientCoPerson' => $gm['CoPerson'],
            'EmailAddress'      => (!empty($gm['CoPerson']['EmailAdress'])
                                    ? $gm['CoPerson']['EmailAdress']
                                    : array())
          );
        }
      }
      
      if(!$mustResolve) {
        // Since this notification doesn't require action but is only informational,
        // generate one notification per person instead of per group
        
        $ids = array();
        
        foreach($recipients as $recipient) {
          $ids[] = $this->register($subjectCoPersonId,
                                   $actorCoPersonId,
                                   'coperson',
                                   $recipient['RecipientCoPerson']['id'],
                                   $action,
                                   $comment,
                                   $source);
        }
        
        return $ids;
      }
    } else {
      throw new InvalidArgumentException(_txt('er.unknown', array($recipientType)));
    }
    
    $n['CoNotification']['action'] = $action;
    $n['CoNotification']['comment'] = $comment;
    
    if(is_array($source)) {
      if(!empty($source['controller'])) {
        $n['CoNotification']['source_controller'] = $source['controller'];
      }
      if(!empty($source['controller'])) {
        $n['CoNotification']['source_action'] = $source['action'];
      }
      if(!empty($source['controller'])) {
        $n['CoNotification']['source_id'] = $source['id'];
      }
    } else {
      $n['CoNotification']['source_url'] = $source;
    }
    
    if($mustResolve) {
      $n['CoNotification']['status'] = NotificationStatusEnum::PendingResolution;
    } else {
      $n['CoNotification']['status'] = NotificationStatusEnum::PendingAcknowledgment;
    }
    
    $this->create();
    
    if($this->save($n['CoNotification'])) {
      foreach($recipients as $recipient) {
        // Send email XXX
        // update notification_time when email sent
        // which email address to we use?
        // EmailAddresses are available in (eg) $recipient['EmailAddress'][0]['mail']
        
        // Create a history record
        
        if(!empty($recipient['RecipientCoPerson']['id'])) {
          try {
            $this->SubjectCoPerson->HistoryRecord->record($recipient['RecipientCoPerson']['id'],
                                                          null,
                                                          null,
                                                          $actorCoPersonId,
                                                          ActionEnum::NotificationDelivered,
                                                          // use rs.nt.delivered.email if an email address was found
                                                          _txt('rs.nt.delivered', array($comment)));
          }
          catch(Exception $e) {
            throw new RuntimeException($e->getMessage());
          }
        }
      }
    } else {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return array($this->id);
  }
  
  /**
   * Resolve an outstanding notification
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer  $id         CO Notification ID
   * @param  Integer  $coPersonId CO Person ID of person resolving the notification
   * @return Boolean  True if notification is resolved
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function resolve($id,
                          $coPersonId) {
    return $this->processResolution($id, $coPersonId, NotificationStatusEnum::Resolved);
  }
  
  /**
   * Resolve all outstanding notifications from the specified source
   *
   * @since  COmanage Registry v0.9
   * @param  Mixed   $source             Source array or URL, exactly matching what was provided previously to register()
   * @param  Integer $resolverCoPersonId CO Person ID of person who resolved the notification
   * @param  String  $resolution         NotificationStatusEnum
   * @return Boolean True if notification(s) is/are resolved
   */
  
  public function resolveFromSource($source,
                                    $resolverCoPersonId,
                                    $resolution=NotificationStatusEnum::Resolved) {
    // When called by CoPetition via CoGroup, we have an alias of CoNotificationRecipientGroup,
    // which causes errors for the find. As a simple workaround, we just reset our alias.
    $this->alias = 'CoNotification';
    
    // updateAll has some annoying characteristics, as documented in processResolution,
    // above. So we'll pull all matching records and then call processResolution to
    // deal with them.
    
    $args = array();
    // Status must be PendingResolution
    $args['conditions']['status'] = NotificationStatusEnum::PendingResolution;
    $args['contain'] = false;
    
    if(is_array($source)) {
      if(!empty($source['controller'])) {
        $args['conditions']['CoNotification.source_controller'] = $source['controller'];
      }
      if(!empty($source['controller'])) {
        $args['conditions']['CoNotification.source_action'] = $source['action'];
      }
      if(!empty($source['controller'])) {
        $args['conditions']['CoNotification.source_id'] = $source['id'];
      }
    } else {
      $args['conditions']['CoNotification.source_url'] = $source;
    }
    
    $notifications = $this->find('all', $args);
    
    $ret = true;
    
    if(!empty($notifications)) {
      foreach($notifications as $n) {
        $this->processResolution($n['CoNotification']['id'],
                                 $resolverCoPersonId,
                                 $resolution);
        
        // For now, if any one resolution fails, we'll return false
        $ret = false;
      }
    }
    // else fail silently
    
    return $ret;
  }
}