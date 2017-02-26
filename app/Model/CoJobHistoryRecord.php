<?php
/**
 * COmanage Registry CO Job History Model
 *
 * Copyright (C) 2016 MLA
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
 * @copyright     Copyright (C) 2016 MLA
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoJobHistoryRecord extends AppModel {
  // Define class name for cake
  public $name = "CoJobHistoryRecord";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoJob",
    "CoPerson",
    "OrgIdentity"
  );
  
  public $hasOne = array(
    "HistoryRecord"
  );

  // Default display field for cake generated views
  public $displayField = "comment";
  
  // Validation rules for table elements
  public $validate = array(
    'co_job_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'record_key' => array(
      'rule' => array('maxLength', 64),
      'required' => false,
      'allowEmpty' => true
    ),
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'org_identity_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'comment' => array(
      'rule' => array('maxLength', 256),
      'required' => false,
      'allowEmpty' => false
    ),
    'status' => array(
      'rule' => array('inList', array(JobStatusEnum::Complete,
                                      JobStatusEnum::Failed,
                                      JobStatusEnum::InProgress,
                                      JobStatusEnum::Notice,
                                      JobStatusEnum::Queued)),
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Create a CO Job History Record.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer       $coJobId       CO Job ID
   * @param  String        $recordKey     A Job or Job Type-specific record key associated with this record
   * @param  String        $comment       Comment
   * @param  Integer       $coPersonId    CO Person ID
   * @param  Integer       $orgIdentityId Org Identity ID
   * @param  JobStatusEnum $status        Status
   * @return Integer                      CO Job History Record ID
   * @throws RuntimeException
   */
  
  public function record($coJobId, $recordKey, $comment, $coPersonId=null, $orgIdentityId=null,
                         $status=JobStatusEnum::Complete) {
    $coJobHistoryData = array();
    $coJobHistoryData['CoJobHistoryRecord']['co_job_id'] = $coJobId;
    $coJobHistoryData['CoJobHistoryRecord']['record_key'] = $recordKey;
    $coJobHistoryData['CoJobHistoryRecord']['co_person_id'] = $coPersonId;
    $coJobHistoryData['CoJobHistoryRecord']['org_identity_id'] = $orgIdentityId;
    $coJobHistoryData['CoJobHistoryRecord']['status'] = $status;
    
    // Make sure $comment fits in the available space
    $limit = $this->validate['comment']['rule'][1];
    $coJobHistoryData['CoJobHistoryRecord']['comment'] = substr($comment, 0, $limit);
    
    // Call create since we might have multiple history records written in a transaction
    $this->create();
    
    if(!$this->save($coJobHistoryData)) {
      throw new RuntimeException(_txt('er.db.save-a', array('CoJobHistoryRecord')));
    }
    
    return $this->id;
  }
}
