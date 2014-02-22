<?php
/**
 * COmanage Registry History Record Model
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class HistoryRecord extends AppModel {
  // Define class name for cake
  public $name = "HistoryRecord";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'co_person_id'
    ),
    "CoPersonRole",
    "OrgIdentity",
    "ActorCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'actor_co_person_id'
    )
  );

  // Default display field for cake generated views
  public $displayField = "comment";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'co_person_role_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'org_identity_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'actor_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'action' => array(
      'rule' => array('maxLength', 4),
      'required' => false,
      'allowEmpty' => false
    ),
    'comment' => array(
      'rule' => array('maxLength', 160),
      'required' => false,
      'allowEmpty' => false
    )
  );
  
  /**
   * Expunge the Actor from a Notification. This operation should only be performed
   * as part of a CO Person expunge. A History Record will be created for the subject
   * indicating that a participant was removed, without indicating who. This function
   * should be called from within a transaction.
   *
   * @since  COmanage Registry v0.9
   * @param  integer $id                  History Record ID
   * @param  integer $expungerCoPersonId  CO Person ID of person performing expunge
   * @return boolean True on success
   * @throws InvalidArgumentException
   */
  
  public function expungeActor($id,
                               $expungerCoPersonId) {
    $this->id = $id;
    
    $subjectCoPersonId = $this->field('co_person_id');
    $subjectCoPersonRoleId = $this->field('co_person_role_id');
    $subjectOrgIdentityId = $this->field('org_identity_id');
    
    if(!$subjectCoPersonId && !$subjectOrgIdentityId) {
      // We should have at least one CO Person ID or one Org Identity ID
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.history_records.1'), $id)));
    }
    
    $this->saveField('actor_co_person_id', null);
    
    $this->record($subjectCoPersonId,
                  $subjectCoPersonRoleId,
                  $subjectOrgIdentityId,
                  $expungerCoPersonId,
                  ActionEnum::HistoryRecordActorExpunged,
                  _txt('rs.hr.expunge', array($id)));
    
    return true;
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
   * @throws RuntimeException
   */
  
  function record($coPersonID, $coPersonRoleID, $orgIdentityId, $actorCoPersonID, $action, $comment=null) {
    $historyData = array();
    $historyData['HistoryRecord']['co_person_id'] = $coPersonID;
    $historyData['HistoryRecord']['co_person_role_id'] = $coPersonRoleID;
    $historyData['HistoryRecord']['org_identity_id'] = $orgIdentityId;
    $historyData['HistoryRecord']['actor_co_person_id'] = $actorCoPersonID;
    $historyData['HistoryRecord']['action'] = $action;
    
    if(isset($comment)) {
      $historyData['HistoryRecord']['comment'] = $comment;
    } else {
      // Figure out a default value
      $historyData['HistoryRecord']['comment'] = _txt('en.action', null, $action);
    }
    
    // Call create since we might have multiple history records written in a transaction
    $this->create($historyData);
    
    if(!$this->save($historyData)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
  }
}
