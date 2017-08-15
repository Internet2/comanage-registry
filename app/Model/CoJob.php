<?php
/**
 * COmanage Registry CO Job Model
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoJob extends AppModel {
  // Define class name for cake
  public $name = "CoJob";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");
  
  public $hasMany = array("CoJobHistoryRecord");
  
  // Default display field for cake generated views
  public $displayField = "summary";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false,
      'message' => 'A CO ID must be provided'
    ),
    'job_type' => array(
      'rule' => array('inList', array(JobTypeEnum::Expiration,
                                      JobTypeEnum::OrgIdentitySync)),
      'required' => true,
      'allowEmpty' => false
    ),
    'job_mode' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(JobStatusEnum::Canceled,
                                      JobStatusEnum::Complete,
                                      JobStatusEnum::Failed,
                                      JobStatusEnum::InProgress,
                                      JobStatusEnum::Queued)),
      'required' => true,
      'allowEmpty' => false
    ),    
    'finish_summary' => array(
      'rule' => array('maxLength', 256),
      'required' => false,
      'allowEmpty' => true
    ),
    'register_summary' => array(
      'rule' => array('maxLength', 256),
      'required' => false,
      'allowEmpty' => true
    ),
    'start_summary' => array(
      'rule' => array('maxLength', 256),
      'required' => false,
      'allowEmpty' => true
    ),
    'queue_time' => array(
      'rule' => 'datetime',
      'required' => false,
      'allowEmpty' => true
    ),
    'start_time' => array(
      'rule' => 'datetime',
      'required' => false,
      'allowEmpty' => true
    ),
    'complete_time' => array(
      'rule' => 'datetime',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Request a job to be canceled. This flags the job as canceled, but it is up to the Job itself
   * to detect the status change and stop processing.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $id    Job ID
   * @param  String  $actor Login Identifier of actor who requested cancelation
   * @return Boolean True on success
   * @throws InvalidArgumentException
   */
  
  public function cancel($id, $actor) {
    // Make sure the job is in a cancelable status
    
    $curStatus = $this->field('status', array('CoJob.id' => $id));
    
    if(!$curStatus) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_jobs.1'), $id)));
    }
    
    // This array corresponds to View/CoJob/fields.inc
    if(!in_array($curStatus, array(JobStatusEnum::InProgress, JobStatusEnum::Queued))) {
      throw new InvalidArgumentException(_txt('er.jb.cxl.status', array(_txt('en.status.job', null, $curStatus))));
    }
    
    // Finally update the status
    
    return $this->finish($id, _txt('rs.jb.cxld.by', array($actor)), JobStatusEnum::Canceled);
  }
  
  /**
   * Determine if a job has been canceled.
   *
   * @since  COmanage Registry  v3.1.0
   * @param  Integer $id    Job ID
   * @return Boolean True on success
   * @throws InvalidArgumentException
   */
  
  public function canceled($id) {
    $curStatus = $this->field('status', array('CoJob.id' => $id));
    
    if(!$curStatus) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_jobs.1'), $id)));
    }
    
    return ($curStatus == JobStatusEnum::Canceled);
  }
  
  /**
   * Update a job as completed.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer       $id         Job ID
   * @param  String        $summary    Summary
   * @param  JobStatusEnum $result Job Result
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function finish($id, $summary="", $result=JobStatusEnum::Complete) {
    // There's not really an elegant way to update more than 1 but less than all fields...
    
    $this->id = $id;
    $this->saveField('status', $result);
    $this->saveField('complete_time', date('Y-m-d H:i:s', time()));
    
    // Make sure $summary fits in the available space
    $limit = $this->validate['finish_summary']['rule'][1];
    $this->saveField('finish_summary', substr($summary, 0, $limit));
    
    return true;
  }
  
  /**
   * Register a job.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer     $coId      CO ID
   * @param  JobTypeEnum $jobType   Job Type
   * @param  Integer     $jobTypeFk Foreign key suitable for $jobType (eg: cm_org_identity_sources:id)
   * @param  String      $jobMode   Job Mode
   * @param  String      $summary   Summary
   * @param  Boolean     $queued    Whether the job is queued (true) or started (false)
   * @return Integer                Job ID
   * @throws RuntimeException
   */
  
  public function register($coId, $jobType, $jobTypeFk=null, $jobMode="", $summary="", $queued=false) {
    $coJob = array();
    $coJob['CoJob']['co_id'] = $coId;
    $coJob['CoJob']['job_type'] = $jobType;
    $coJob['CoJob']['job_type_fk'] = $jobTypeFk;
    $coJob['CoJob']['job_mode'] = $jobMode;
    $coJob['CoJob']['status'] = ($queued ? JobStatusEnum::Queued : JobStatusEnum::InProgress);
    $coJob['CoJob']['queue_time'] = date('Y-m-d H:i:s', time());

    // Make sure $summary fits in the available space
    $limit = $this->validate['register_summary']['rule'][1];
    $coJob['CoJob']['register_summary'] = substr($summary, 0, $limit);
    
    // Call create since we might have multiple records written in a transaction
    $this->create();
    
    if(!$this->save($coJob)) {
      throw new RuntimeException(_txt('er.db.save-a', array('CoJob')));
    }
    
    return $this->id;
  }
  
  /**
   * Update a job as started (if it was queued).
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id   Job ID
   * @param  String  $summary Summary
   * @throws RuntimeException
   */
  
  public function start($id, $summary="") {
    // There's not really an elegant way to update more than 1 but less than all fields...
    
    $this->id = $id;
    $this->saveField('status', JobStatusEnum::Started);
    $this->saveField('start_time', date('Y-m-d H:i:s', time()));
    
    // Make sure $summary fits in the available space
    $limit = $this->validate['finish_summary']['rule'][1];
    $coJob['CoJob']['finish_summary'] = substr($summary, 0, $limit);
  }
}
