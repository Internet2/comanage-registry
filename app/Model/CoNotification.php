<?php
/**
 * COmanage Registry CO Notification Model
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

App::uses('CakeEmail', 'Network/Email');

class CoNotification extends AppModel {
  // Define class name for cake
  public $name = "CoNotification";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // At leaste one Subject and one Recipient is required, but we currently don't enforce this
    "SubjectCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'subject_co_person_id'
    ),
    "SubjectCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'subject_co_group_id'
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
      'required' => false,
      'allowEmpty' => true
    ),
    'subject_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
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
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'action' => array(
      'rule' => array('maxLength', 4),
      'required' => false,
      'allowEmpty' => true
    ),
    'comment' => array(
      'rule' => array('maxLength', 256),
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
    'source_arg0' => array(
      'rule' => array('maxLength', 80),
      'required' => false,
      'allowEmpty' => true
    ),
    'source_val0' => array(
      'rule' => array('maxLength', 80),
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
    'email_subject' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'email_body' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'resolution_subject' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'resolution_body' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'notification_time' => array(
      'rule' => 'notBlank'
    ),
    'resolution_time' => array(
      'rule' => 'notBlank'
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
   * @param  integer $expungerApiUserId   API User ID performing expunge
   * @return boolean True on success
   * @throws InvalidArgumentException
   */
  
  public function expungeParticipant($id,
                                     $role,
                                     $expungerCoPersonId,
                                     $expungerApiUserId = null) {
    $this->id = $id;
    
    $subjectCoPersonId = $this->field('subject_co_person_id');
    
    $this->saveField($role.'_co_person_id', null);
    
    if($subjectCoPersonId) {
      $this->ActorCoPerson->HistoryRecord->record($subjectCoPersonId,
                                                  null,
                                                  null,
                                                  $expungerCoPersonId,
                                                  ActionEnum::NotificationParticipantExpunged,
                                                  _txt('rs.nt.expunge', array($id, $role)),
                                                  null,
                                                  null,
                                                  null,
                                                  $expungerApiUserId);
    }
    // else subject can be null if (eg) a group provisioner failed and a notification
    // is sent to the admins. In that case, don't bother with the history record
    // because there's nowhere to attach the record to.
    
    return true;
  }
  
  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function findCoForRecord($id) {
    // CoNotification needs to get the CO via the Subject CO Person or Subject CO Group
    
    $args = array();
    $args['conditions'][$this->alias.'.id'] = $id;
    $args['contain'][] = 'SubjectCoPerson';
    $args['contain'][] = 'SubjectCoGroup';
    
    $cop = $this->find('first', $args);
      
    if(!empty($cop['SubjectCoPerson']['co_id'])) {
      return $cop['SubjectCoPerson']['co_id'];
    } elseif(!empty($cop['SubjectCoGroup']['co_id'])) {
      return $cop['SubjectCoGroup']['co_id'];
    }
    
    return parent::findCoForRecord($id);
  }
  
  /**
   * Obtain pending notifications for a CO Person
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer  $coPersonId CO Person ID to obtain notifications for
   * @param  Integer  $max        Maximum number of notifications to obtain, null for all or 0 for a count
   * @return Mixed    Array of pending notifications, or Integer of count of notifications
   */
  
  public function pending($coPersonId, $max=null) {
    // We need the groups the person is a member of. There's probably a clever join
    // to pull from co_notifications in one query, put the obvious joins aren't working.
    
    $args = array();
    $args['joins'][0]['table'] = 'co_group_members';
    $args['joins'][0]['alias'] = 'CoGroupMember';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'RecipientCoGroup.id=CoGroupMember.co_group_id';
    $args['conditions']['RecipientCoGroup.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
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
    if($max > 0) {
      $args['limit'] = $max;
    }
    $args['contain'] = false;
    
    if($max === 0) {
      return $this->find('count', $args);
    } else {
      return $this->find('all', $args);
    }
  }
  
  /**
   * Process the resolution of an outstanding notification
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer  $id          CO Notification ID
   * @param  Integer  $coPersonId  CO Person ID of person ackowledging the notification
   * @param  String   $resolution  NotificationStatusEnum
   * @param  String   $fromAddress Email Address to send the invite from (if null, use default)
   * @return Boolean  True if notification is resolved
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function processResolution($id,
                                       $coPersonId,
                                       $resolution,
                                       $fromAddress=null) {
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
    
    // If a notification is resolved (not acknowledged) and had a receipient group,
    // send email to the non-actor group members notifying them it was resolved.
    // Do this after the history record is created in case something goes wrong.
    
    if($resolution == NotificationStatusEnum::Resolved
       && !empty($not['CoNotification']['recipient_co_group_id'])) {
      $coId = $this->RecipientCoGroup->field('co_id', array('RecipientCoGroup.id' => $not['CoNotification']['recipient_co_group_id']));
      $coName = $this->RecipientCoGroup->Co->field('name', array('Co.id' => $coId));
      
      $sourceurl = $not['CoNotification']['source_url'];
      
      $args = array();
      $args['conditions']['ActorCoPerson.id'] = $coPersonId;
      $args['contain'][] = 'PrimaryName';
      
      $actor = $this->ActorCoPerson->find('first', $args);
      
      if(!$sourceurl) {
        /*
         * Something wacky is happening with Router::url here (vs in register(), where
         * it works fine). The array notation throws an error, though string notation works ok.
        $s = array();
        $s['controller'] = $not['CoNotification']['source_controller'];
        $s['action'] = $not['CoNotification']['source_action'];
        $s[] = $not['CoNotification']['source_id'];
        */
        
        $surl = "/" . $not['CoNotification']['source_controller']
                . "/" . $not['CoNotification']['source_action']
                . "/" . $not['CoNotification']['source_id'];
        
        if(!empty($not['CoNotification']['source_arg0'])) {
          $surl .= "/" . $not['CoNotification']['source_arg0'];
          
          if(!empty($not['CoNotification']['source_val0'])) {
            $surl .= ":" . $not['CoNotification']['source_val0'];
          }
        }
        
        $sourceurl = Router::url($surl, true);
      }
      
      $args = array();
      $args['conditions']['RecipientCoGroup.id'] = $not['CoNotification']['recipient_co_group_id'];
      $args['contain']['CoGroupMember']['CoPerson'] = 'EmailAddress';
      
      $gr = $this->RecipientCoGroup->find('first', $args);
      
      if(!empty($gr['CoGroupMember'])) {
        foreach($gr['CoGroupMember'] as $gm) {
          if(!empty($gm['CoPerson']['EmailAddress'][0]['mail'])) {
            // For now we just pick the first email address, but eventually we should
            // use whatever login register() implements (first delivery address, etc)
            
            try {
              $this->sendEmail($id,
                               $gm['CoPerson']['EmailAddress'][0]['mail'],
                               _txt('em.resolution.subject'),
                               _txt('em.resolution.body'),
                               $coName,
                               $not['CoNotification']['comment'],
                               $sourceurl,
                               !empty($actor['PrimaryName']) ? generateCn($actor['PrimaryName']) : "(?)",
                               $fromAddress,
                               true);
            }
            catch(Exception $e) {
              throw new RuntimeException($e->getMessage());
            }
          }
        }
      }
    }
    
    return true;
  }
  
  /**
   * Register a new notification
   *
   * @since  COmanage Registry v0.8.4
   * @param  Integer $subjectCoPersonId CO Person ID of subject of notification
   * @param  Integer $subjectCoGroupId  CO Group ID of subject of notification
   * @param  Integer $actorCoPersonId   CO Person ID of actor who generated notification (or null)
   * @param  String  $recipientType     "coperson" or "cogroup"
   * @param  Integer $recipientId       CO Person ID or CO Group ID of recipient, according to $recipientType
   * @param  String  $action            ActionEnum describing notification
   * @param  String  $comment           Human readable notification comment
   * @param  Mixed   $source            Link to source to review/resolve notification; may be a string (url) or cake-style array of controller+action+id (note: 'id' must be specified as array key; 'arg0' and 'val0' also accepted)
   * @param  Boolean $mustResolve       If true, the notification cannot be acknowledged, only resolved via $source
   * @param  String  $fromAddress       Email Address to send the invite from (if null, use default)
   * @param  String  $subjectTemplate   Subject template for notification email (if null, default subject is sent)
   * @param  String  $bodyTemplate      Body template for notification email (if null, default body using $comment and $source is sent)
   * @param  String  $cc                Comma separated list of addresses to cc
   * @param  String  $bcc               Comma separated list of addresses to bcc
   * @param  String  $format            Message Body format type it can be txt, html or both
   * @return Array CO Notification ID(s)
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function register($subjectCoPersonId,
                           $subjectCoGroupId,
                           $actorCoPersonId,
                           $recipientType,
                           $recipientId,
                           $action,
                           $comment,
                           $source,
                           $mustResolve=false,
                           $fromAddress=null,
                           $subjectTemplate=null,
                           $bodyTemplate=null,
                           $cc=null,
                           $bcc=null,
                           $format=MessageFormatEnum::Plaintext) {
    // Create the notification. Perhaps this should be embedded in a transaction.
    
    $n = array();
    
    if(!empty($subjectCoPersonId)) {
      $n['CoNotification']['subject_co_person_id'] = $subjectCoPersonId;
    }
    
    if(!empty($subjectCoGroupId)) {
      $n['CoNotification']['subject_co_group_id'] = $subjectCoGroupId;
    }
    
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
      // We'll use an org identity address if there is no CO Person address
      $args['contain']['CoOrgIdentityLink']['OrgIdentity'][] = 'EmailAddress';
      
      $recipients[] = $this->RecipientCoPerson->find('first', $args);
    } elseif($recipientType == 'cogroup') {
      $n['CoNotification']['recipient_co_group_id'] = $recipientId;
      
      // A clever contain will perform our join and filter, but nest our data
      $args = array();
      $args['conditions']['RecipientCoGroup.id'] = $recipientId;
      $args['conditions']['RecipientCoGroup.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = array(
        'CoGroupMember' => array(
          // We only want group members, not owners
          'conditions' => array(
            'CoGroupMember.member' => true,
            'AND' => array(
              array('OR' => array(
                'CoGroupMember.valid_from IS NULL',
                'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
              )),
              array('OR' => array(
                'CoGroupMember.valid_through IS NULL',
                'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
              ))
            )
          ),
          'CoPerson' => array(
            // We only want active people BUT this condition seems to get overwritten
            // in ChangelogBehavior (beforeFind - modifyContain) and is therefore not applied
            // 'conditions' => array('CoPerson.status' => StatusEnum::Active),
            'EmailAddress'
          )
        )
      );
      
      $gr = $this->RecipientCoGroup->find('first', $args);
      
      if(!empty($gr['CoGroupMember'])) {
        foreach($gr['CoGroupMember'] as $gm) {
          if(!empty($gm['CoPerson']) 
            && $gm['CoPerson']['status'] == StatusEnum::Active) {
            // Move EmailAddress up a level, as for 'coperson'
            $recipients[] = array(
              'RecipientCoPerson' => $gm['CoPerson'],
              'EmailAddress'      => (!empty($gm['CoPerson']['EmailAddress'])
                                      ? $gm['CoPerson']['EmailAddress']
                                      : array())
            );
          }
        }
      }
      
      if(!$mustResolve) {
        // Since this notification doesn't require action but is only informational,
        // generate one notification per person instead of per group
        
        $ids = array();
        
        foreach($recipients as $recipient) {
          $r = $this->register($subjectCoPersonId,
                               $subjectCoGroupId,
                               $actorCoPersonId,
                               'coperson',
                               $recipient['RecipientCoPerson']['id'],
                               $action,
                               $comment,
                               $source,
                               $mustResolve,
                               $fromAddress,
                               $subjectTemplate,
                               $bodyTemplate,
                               $cc,
                               $bcc,
                               $format);
          
          // We get an array back but it should only have one entry
          $ids[] = $r[0];
        }
        
        return $ids;
      }
    } else {
      throw new InvalidArgumentException(_txt('er.unknown', array($recipientType)));
    }
    
    $n['CoNotification']['action'] = $action;
    
    $limit = $this->validate['comment']['rule'][1];
    $n['CoNotification']['comment'] = substr($comment, 0, $limit);
    
    $csource = array();
    
    if(is_array($source)) {
      // While we're here, "fix" $source since it has an "id" key but Cake doesn't expect it
      
      if(!empty($source['controller'])) {
        $n['CoNotification']['source_controller'] = $source['controller'];
        $csource['controller'] = $source['controller'];
      }
      if(!empty($source['action'])) {
        $n['CoNotification']['source_action'] = $source['action'];
        $csource['action'] = $source['action'];
      }
      if(!empty($source['id'])) {
        $n['CoNotification']['source_id'] = $source['id'];
        $csource[] = $source['id'];
      }
      if(!empty($source['arg0'])) {
        $n['CoNotification']['source_arg0'] = $source['arg0'];
        
        if(!empty($source['val0'])) {
          $n['CoNotification']['source_val0'] = $source['val0'];
          $csource[ $source['arg0'] ] = $source['val0'];
        } else {
          $csource[] = $source['arg0'];
        }
      }
    } else {
      $n['CoNotification']['source_url'] = $source;
    }
    
    if($mustResolve) {
      $n['CoNotification']['status'] = NotificationStatusEnum::PendingResolution;
    } else {
      $n['CoNotification']['status'] = NotificationStatusEnum::PendingAcknowledgment;
    }
    
    // We need to map CoPerson to get the CO ID to get the CO Name
    
    $coId = null;
    
    if($subjectCoPersonId) {
      $coId = $this->SubjectCoPerson->field('co_id', array('SubjectCoPerson.id' => $subjectCoPersonId));
    } elseif($subjectCoGroupId) {
      $coId = $this->SubjectCoGroup->field('co_id', array('SubjectCoGroup.id' => $subjectCoGroupId));
    }
    $coName = $this->RecipientCoPerson->Co->field('name', array('Co.id' => $coId));
    $sourceurl = (is_array($source)
                  ? Router::url($csource, true) // Use the source formatted for cake
                  : $source);
    
    $this->create();
    
    if($this->save($n['CoNotification'])) {
      // Make sure we don't lose it
      $notificationId = $this->id;
      
      $args = array();
      $args['conditions']['ActorCoPerson.id'] = $actorCoPersonId;
      $args['contain'][] = 'PrimaryName';
      
      $actor = $this->ActorCoPerson->find('first', $args);
      
      foreach($recipients as $recipient) {
        $toaddr = null;
        
        if(!empty($recipient['EmailAddress'][0]['mail'])) {
          // Send email, if we have an email address
          // Which email address do we use? for now, the first one (same as in processResolution())
          // (ultimately we probably want the first address of type delivery)
          $toaddr = $recipient['EmailAddress'][0]['mail'];
        } elseif(!empty($recipient['CoOrgIdentityLink'][0]['OrgIdentity']['EmailAddress'][0]['mail'])) {
          // If we don't have a CO Person email address, we'll try one attached to an Org Identity
          // (useful for initial enrollment approval notification)
          $toaddr = $recipient['CoOrgIdentityLink'][0]['OrgIdentity']['EmailAddress'][0]['mail'];
        }
        
        if($toaddr) {
          try {
            // Send email will update the record with the subject and body it constructs
            $this->sendEmail($notificationId,
                             $toaddr,
                             ($subjectTemplate ? $subjectTemplate : _txt('em.notification.subject')),
                             ($bodyTemplate ? $bodyTemplate : _txt('em.notification.body')),
                             $coName,
                             $comment,
                             $sourceurl,
                             !empty($actor['PrimaryName']) ? generateCn($actor['PrimaryName']) : "(?)",
                             $fromAddress,
                             false,
                             $cc,
                             $bcc,
                             $format);
          }
          catch(Exception $e) {
            throw new RuntimeException($e->getMessage());
          }
        }
        
        // Create a history record
        
        if(!empty($recipient['RecipientCoPerson']['id'])) {
          $c = "";
          
          if($toaddr) {
            $c = _txt('rs.nt.delivered.email', array($toaddr, $comment));
          } else {
            $c = _txt('rs.nt.delivered', array($comment));
          }
          
          try {
            $this->SubjectCoPerson->HistoryRecord->record($recipient['RecipientCoPerson']['id'],
                                                          null,
                                                          null,
                                                          $actorCoPersonId,
                                                          ActionEnum::NotificationDelivered,
                                                          $c);
          }
          catch(Exception $e) {
            throw new RuntimeException($e->getMessage());
          }
        }
      }
      
      // Now that the notifications have been done, update notification_time
      
      // Make sure we're writing to the right object
      $this->id = $notificationId;
      
      $this->saveField('notification_time', date('Y-m-d H:i:s', time()));
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
      if(!empty($source['action'])) {
        $args['conditions']['CoNotification.source_action'] = $source['action'];
      }
      if(!empty($source['id'])) {
        $args['conditions']['CoNotification.source_id'] = $source['id'];
      }
      if(!empty($source['arg0'])) {
        $args['conditions']['CoNotification.source_arg0'] = $source['arg0'];
      }
      if(!empty($source['val0'])) {
        $args['conditions']['CoNotification.source_val0'] = $source['val0'];
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
  
  /**
   * Send email for a notification or notification resolution
   *
   * @since  COmanage Registry v0.9
   * @param  Integer $notificationId    CO Notification related to this email
   * @param  Array   $recipient         Address to send notification to
   * @param  String  $subjectTemplate   Subject template for notification email
   * @param  String  $bodyTemplate      Body template for notification email
   * @param  String  $coName            CO Name, for template substitution
   * @param  String  $comment           Comment, for template substitution
   * @param  String  $sourceUrl         Source URL, for template substitution
   * @param  String  $actorName         Human readable name of Actor, for template substitution
   * @param  String  $fromAddress       Email Address to send the invite from (if null, use default)
   * @param  Boolean $resolution        If true, store a copy of the subject and email as the resolution message for the specified CO Notification (otherwise store as notification)
   * @param  String  $cc                Comma separated list of addresses to cc
   * @param  String  $bcc               Comma separated list of addresses to bcc
   * @param  String  $format            Message Body format type it can be txt, html or both
   * @throws RuntimeException
   */
  
  protected function sendEmail($notificationId,
                               $recipient,
                               $subjectTemplate,
                               $bodyTemplate,
                               $coName,
                               $comment,
                               $sourceUrl,
                               $actorName=null,
                               $fromAddress=null,
                               $resolution=false,
                               $cc=null,
                               $bcc=null,
                               $format=MessageFormatEnum::Plaintext) {
    // Create the message subject and body based on the templates.
    
    $msgBody = "";
    $msgSubject = "";
    
    $nurl = array(
      'controller'  => 'co_notifications',
      'action'      => 'view',
      $notificationId
    );

    $substitutions = array(
      'ACTOR_NAME'        => ($actorName ? $actorName : ""),
      'CO_NAME'           => $coName,
      'COMMENT'           => $comment,
      'NOTIFICATION_URL'  => Router::url($nurl, true) ,
      'SOURCE_URL'        => $sourceUrl
    );
    
    // Construct subject and body
    
    $msgSubject = processTemplate($subjectTemplate, $substitutions);
    $msgBody = processTemplate($bodyTemplate, $substitutions);
    
    // Send email, if we have an email address
    // Which email address do we use? for now, the first one
    // (ultimately we probably want the first address of type delivery)
    
    try {
      $email = new CakeEmail('default');
      
      // If a from address was provided, use it
      
      if($fromAddress) {
        $email->from($fromAddress);
      }
      
      // Add cc and bcc if specified
      if($cc) {
        $email->cc(array_map('trim', explode(',', $cc)));
      }
      
      if($bcc) {
        $email->bcc(array_map('trim', explode(',', $bcc)));
      }

      if($format === MessageFormatEnum::PlaintextAndHTML
         && is_array($msgBody)) {
        $viewVariables = array(
          MessageFormatEnum::Plaintext  => $msgBody[MessageFormatEnum::Plaintext],
          MessageFormatEnum::HTML => $msgBody[MessageFormatEnum::HTML],
        );
        $msgBody = $msgBody[MessageFormatEnum::Plaintext];
      } elseif($format === MessageFormatEnum::HTML
               && is_array($msgBody)) {
        $viewVariables = array(
          MessageFormatEnum::HTML => $msgBody[MessageFormatEnum::HTML],
        );
        $msgBody = $msgBody[MessageFormatEnum::HTML];
      } else {
        if(is_array($msgBody)) {
          $viewVariables = array(
            MessageFormatEnum::Plaintext => $msgBody[MessageFormatEnum::Plaintext],
          );
          $msgBody = $msgBody[MessageFormatEnum::Plaintext];
        } else {
          $viewVariables = array(
            MessageFormatEnum::Plaintext => $msgBody,
          );
        }
      }

      $email->template('custom', 'basic')
        ->emailFormat($format)
        ->to($recipient)
        ->viewVars($viewVariables)
        ->subject($msgSubject);
      $email->send();
      
      // Store a copy of this message in the appropriate place
      
      $this->id = $notificationId;
      
      if($resolution) {
        // Truncate subject and body to fit in the available database width
        
        $this->saveField('resolution_subject', substr($msgSubject, 0, 255));
        // At least for postgres, this is stored as text, which doesn't have a fixed limit
        $this->saveField('resolution_body', $msgBody);
      } else {
        // Truncate subject and body to fit in the available database width
        
        $this->saveField('email_subject', substr($msgSubject, 0, 255));
        // At least for postgres, this is stored as text, which doesn't have a fixed limit
        $this->saveField('email_body', $msgBody);
      }
    }
    catch(Exception $e) {
      // Should we really abort all notifications if a send fails? Probably not...
      throw new RuntimeException($e->getMessage() . PHP_EOL . _txt('er.nt.send-a', array($recipient)));
    }
  }
}
