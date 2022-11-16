<?php
/**
 * COmanage Registry History Record Model
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
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class HistoryRecord extends AppModel {
  // Define class name for cake
  public $name = "HistoryRecord";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoEmailList",
    "CoGroup",
    "CoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'co_person_id'
    ),
    "CoPersonRole",
    "OrgIdentity",
    "ActorCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'actor_co_person_id'
    ),
    "ActorApiUser" => array(
      'className' => 'ApiUser',
      'foreignKey' => 'actor_api_user_id'
    )
  );

  // Default display field for cake generated views
  public $displayField = "comment";
  
  public $actsAs = array('Containable',
                         // HistoryRecord doesn't really need to be changelog enabled,
                         // except that its associated models are. Changelog keeps the
                         // history around (in a flagged-as-deleted state).
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  public $validate = array(
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_person_role_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'org_identity_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_email_list_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'actor_co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'actor_api_user_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'action' => array(
      'rule' => array('maxLength', 4),
      'required' => false,
      'allowEmpty' => false
    ),
    'comment' => array(
      'rule' => array('maxLength', 256),
      'required' => false,
      'allowEmpty' => false
    )
  );
  
  /**
   * Expunge the Actor from a History Record. This operation should only be performed
   * as part of a CO Person expunge. A History Record will be created for the subject
   * indicating that a participant was removed, without indicating who. This function
   * should be called from within a transaction.
   *
   * @since  COmanage Registry v0.8.5
   * @param  integer $id                  History Record ID
   * @param  integer $expungerCoPersonId  CO Person ID of person performing expunge
   * @param  integer $expungerApiUserId   API User ID performing expunge
   * @return boolean True on success
   * @throws InvalidArgumentException
   */
  
  public function expungeActor($id,
                               $expungerCoPersonId,
                               $expungerApiUserId = null) {
    $this->id = $id;
    
    $subjectCoPersonId = $this->field('co_person_id');
    $subjectCoPersonRoleId = $this->field('co_person_role_id');
    $subjectOrgIdentityId = $this->field('org_identity_id');
    
    $this->saveField('actor_co_person_id', null);
    
    if($subjectCoPersonId || $subjectOrgIdentityId) {
      $this->record($subjectCoPersonId,
                    $subjectCoPersonRoleId,
                    $subjectOrgIdentityId,
                    $expungerCoPersonId,
                    ActionEnum::HistoryRecordActorExpunged,
                    _txt('rs.hr.expunge', array($id)),
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
   * @since  COmanage Registry v1.0.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function findCoForRecord($id) {
    // HistoryRecords needs to get the CO via the (generally set) Actor CO Person
    
    $args = array();
    $args['conditions']['HistoryRecord.id'] = $id;
    $args['contain'][] = 'ActorCoPerson';
    
    $hr = $this->find('first', $args);
    
    if(!empty($hr['ActorCoPerson']['co_id'])) {
      return $hr['ActorCoPerson']['co_id'];
    }
    
    return parent::findCoForRecord($id);
  }

  /**
   * Create a History Record.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO Person Role ID
   * @param  Integer Org Identity ID
   * @param  Integer Actor CO Person ID
   * @param  ActionEnum Action
   * @param  String Comment (if not provided, default comment for $action is used)
   * @param  Integer CO Group ID
   * @param  Integer CO Email List ID
   * @param  Integer Actor API User ID
   * @throws RuntimeException
   */
  
  public function record($coPersonID,
                         $coPersonRoleID,
                         $orgIdentityId,
                         $actorCoPersonID,
                         $action,
                         $comment=null,
                         $coGroupID=null,
                         $coEmailListId = null,
                         $coServiceId = null,
                         $actorApiUserId = null) {
    $historyData = array();
    $historyData['HistoryRecord']['co_person_id'] = $coPersonID;
    $historyData['HistoryRecord']['co_person_role_id'] = $coPersonRoleID;
    $historyData['HistoryRecord']['org_identity_id'] = $orgIdentityId;
    $historyData['HistoryRecord']['co_group_id'] = $coGroupID;
    $historyData['HistoryRecord']['co_email_list_id'] = $coEmailListId;
    $historyData['HistoryRecord']['co_service_id'] = $coServiceId;
    $historyData['HistoryRecord']['actor_co_person_id'] = $actorCoPersonID;
    $historyData['HistoryRecord']['action'] = $action;
    $historyData['HistoryRecord']['actor_api_user_id'] = $actorApiUserId;

    if(isset($comment)) {
      $historyData['HistoryRecord']['comment'] = $comment;
    } else {
      // Figure out a default value
      $historyData['HistoryRecord']['comment'] = _txt('en.action', null, $action);
    }
    
    // Make sure $comment fits within the available length
    $limit = $this->validate['comment']['rule'][1];
    
    $historyData['HistoryRecord']['comment'] = substr($historyData['HistoryRecord']['comment'],
                                                      0,
                                                      $limit);
    
    // Call create since we might have multiple history records written in a transaction
    $this->create();
    
    if(!$this->save($historyData)) {
      throw new RuntimeException(_txt('er.db.save-a', array('HistoryRecord')));
    }
  }
}
