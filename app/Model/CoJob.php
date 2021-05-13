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
      'allowEmpty' => false
    ),
    'job_type' => array(
      'rule' => array('maxLength', 64),
      'required' => true,
      'allowEmpty' => false
    ),
    'job_mode' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'job_params' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'requeue_interval' => array(
      'rule' => array('numeric'),   // We really want range of 0+
      'required' => false,
      'allowEmpty' => true
    ),
    'retry_interval' => array(
      'rule' => array('numeric'),   // We really want range of 0+
      'required' => false,
      'allowEmpty' => true
    ),
    'requeued_from_co_job_id' => array(
      'rule' => 'numeric',
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
    'start_after_time' => array(
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
    ),
    'percent_complete' => array(
      'rule' => array('range', -1, 101),
      'required' => false,
      'allowEmpty' => true
    )
  );


  // Enum type hints

  public $cm_enum_types = array(
    'status' => 'JobStatusEnum'
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
    if(!$id) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_jobs.1'), $id)));
    }
    
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
   * Determine if a job failed.
   *
   * @since  COmanage Registry  v4.0.0
   * @param  Integer $id    Job ID
   * @return Boolean True on success
   * @throws InvalidArgumentException
   */

  public function failed($id) {
    $curStatus = $this->field('status', array('CoJob.id' => $id));

    if(!$curStatus) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_jobs.1'), $id)));
    }

    return ($curStatus == JobStatusEnum::Failed);
  }

  /**
   * Update a job as completed.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer       $id         Job ID
   * @param  String        $summary    Summary
   * @param  JobStatusEnum $result Job Result
   * @return Boolean True on success
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function finish($id, $summary="", $result=JobStatusEnum::Complete) {
    if(!$id) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_jobs.1'), $id)));
    }
    
    // Pull the current job
    
    $args = array();
    $args['conditions']['CoJob.id'] = $id;
    $args['contain'] = false;
    
    $job = $this->find('first', $args);
    
    if(empty($job)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_jobs.1'), $id)));
    }
    
    $job['CoJob']['status'] = $result;
    $job['CoJob']['complete_time'] = date('Y-m-d H:i:s', time());
    
    // Make sure $summary fits in the available space
    $limit = $this->validate['finish_summary']['rule'][1];
    $job['CoJob']['finish_summary'] = substr($summary, 0, $limit);
    
    $this->save($job, true, array('complete_time', 'finish_summary', 'status'));
    
    // On success, if a requeue_interval is specified then register a new job
    // with the same parameters, but with a delayed start time. Do the same thing
    // on failure if a retry_interval is specified. We need to do this after we
    // update the status of $id to avoid issues with concurrent jobs.
    
    if($result == JobStatusEnum::Complete
       && !empty($job['CoJob']['requeue_interval'])
       && $job['CoJob']['requeue_interval'] > 0) {
      // The new job will be substantially the same as the last one...
      
      $this->register($job['CoJob']['co_id'], 
                      $job['CoJob']['job_type'],
                      $job['CoJob']['job_type_fk'],
                      $job['CoJob']['job_mode'],
                      $job['CoJob']['register_summary'],
                      true,
                      // we only support serialized jobs, not concurrent
                      false,
                      json_decode($job['CoJob']['job_params'], true),
                      $job['CoJob']['requeue_interval'],
                      $job['CoJob']['requeue_interval'],
                      $job['CoJob']['retry_interval'],
                      $job['CoJob']['id']);
    } elseif($result == JobStatusEnum::Failed
             && !empty($job['CoJob']['retry_interval'])
             && $job['CoJob']['retry_interval'] > 0) {
      // The new job will be substantially the same as the last one...
      
      $this->register($job['CoJob']['co_id'], 
                      $job['CoJob']['job_type'],
                      $job['CoJob']['job_type_fk'],
                      $job['CoJob']['job_mode'],
                      $job['CoJob']['register_summary'],
                      true,
                      // we only support serialized jobs, not concurrent
                      false,
                      json_decode($job['CoJob']['job_params'], true),
                      $job['CoJob']['retry_interval'],
                      $job['CoJob']['requeue_interval'],
                      $job['CoJob']['retry_interval'],
                      $job['CoJob']['id']);
    }
    
    return true;
  }
  
  /**
   * Determine the last start time for a successfully completed Job.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer     $coId       CO ID
   * @param  string      $jobType    Job Type
   * @param  Integer     $jobTypeFk  Foreign key suitable for $jobType (eg: cm_org_identity_sources:id)
   * @return Integer                 Timestamp, or 0 if no previous run was found
   */
  
  public function lastStart($coId, $jobType, $jobTypeFk=null) {
    $args = array();
    $args['conditions']['CoJob.co_id'] = $coId;
    $args['conditions']['CoJob.job_type'] = $jobType;
    $args['conditions']['CoJob.job_type_fk'] = $jobTypeFk;
    $args['conditions']['CoJob.status'] = JobStatusEnum::Complete;
    $args['order'] = array('CoJob.start_time DESC');
    $args['contain'] = false;
    
    $job = $this->find('first', $args);
    
    if(empty($job)) {
      return 0;
    } else {
      return strtotime($job['CoJob']['start_time']);
    }
  }

  /**
   * Get Jobs Queued by type
   *
   * @since  COmanage Registry v4.0.0
   * @param  Integer     $coId       CO ID
   * @param  string      $jobType    Job Type
   * @param  Integer     $jobTypeFk  Foreign key suitable for $jobType (eg: cm_org_identity_sources:id)
   * @return array                   List of Jobs matching the Job Type
   */

  public function jobsQueuedByType($coId, $jobType, $jobTypeFk=null) {
    $args = array();
    $args['conditions']['CoJob.co_id'] = $coId;
    $args['conditions']['CoJob.job_type'] = $jobType;
    $args['conditions']['CoJob.job_type_fk'] = $jobTypeFk;
    $args['conditions']['CoJob.status'] = JobStatusEnum::Queued;
    $args['contain'] = false;

    $jobs = $this->find('all', $args);

    return (empty($jobs)) ? array() : $jobs;
  }
  
  /**
   * Register a job.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer     $coId            CO ID
   * @param  String      $jobType         Job Type
   * @param  Integer     $jobTypeFk       Foreign key suitable for $jobType (eg: cm_org_identity_sources:id)
   * @param  String      $jobMode         Job Mode
   * @param  String      $summary         Summary
   * @param  Boolean     $queued          Whether the job is queued (true) or started (false)
   * @param  Boolean     $concurrent      Whether multiple instances of this job (coid+jobtype+jobtypefk) are permitted to run concurrently
   * @param  Array       $params          Parameters to pass to job plugin
   * @param  Integer     $delay           Minimum number of seconds to delay the start of this job
   * @param  Integer     $requeueInterval If non-zero, number of seconds after successful completion to requeue the same job
   * @param  Integer     $retryInterval   If non-zero, number of seconds after failed completion to requeue the same job
   * @param  Integer     $requeuedFrom    If requeued, the CO Job ID that created this Job
   * @return Integer                      Job ID
   * @throws RuntimeException
   */
  
  public function register($coId, 
                           $jobType,
                           $jobTypeFk=null,
                           $jobMode="",
                           $summary="",
                           $queued=false,
                           $concurrent=false,
                           $params=null,
                           $delay=0,
                           $requeueInterval=0,
                           $retryInterval=0,
                           $requeuedFrom=null) {
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    $jobs = array();
    
    if(!$concurrent) {
      // First make sure there is no current job running
      
      $args = array();
      $args['conditions']['CoJob.co_id'] = $coId;
      $args['conditions']['CoJob.job_type'] = $jobType;
      if($jobTypeFk) {
        $args['conditions']['CoJob.job_type_fk'] = $jobTypeFk;
      }
      if($params) {
        $args['conditions']['CoJob.job_params'] = json_encode($params);
      }
      $args['conditions']['CoJob.job_mode'] = $jobMode;
      $args['conditions']['CoJob.status'] = array(JobStatusEnum::Queued, JobStatusEnum::InProgress);
      $args['fields'] = array(); //array('co_id', 'job_type', 'job_params', 'job_mode', 'status');
      
      $jobs = $this->findForUpdate($args['conditions'], $args['fields']);
      
      // In order to present the error via the UI, we still want to insert a job record,
      // so we don't rollback.
    }
    
    // Make sure $summary fits in the available space
    $limit = $this->validate['register_summary']['rule'][1];
    
    $coJob = array();
    $coJob['CoJob']['co_id'] = $coId;
    $coJob['CoJob']['job_type'] = $jobType;
    if($params) {
      $coJob['CoJob']['job_params'] = json_encode($params);
    }
    $coJob['CoJob']['job_type_fk'] = $jobTypeFk;
    $coJob['CoJob']['job_mode'] = $jobMode;
    $coJob['CoJob']['queue_time'] = date('Y-m-d H:i:s', time());
    $coJob['CoJob']['requeue_interval'] = $requeueInterval;
    $coJob['CoJob']['retry_interval'] = $retryInterval;
    if($requeuedFrom) {
      $coJob['CoJob']['requeued_from_co_job_id'] = $requeuedFrom;
    }
    if($delay > 0) {
      $coJob['CoJob']['start_after_time'] = date('Y-m-d H:i:s', time()+$delay);
    }
    if(!empty($jobs)) {
      $coJob['CoJob']['status'] = JobStatusEnum::Failed;
      $coJob['CoJob']['complete_time'] = $coJob['CoJob']['queue_time'];
      $coJob['CoJob']['register_summary'] = substr(_txt('er.jb.concurrent', array($jobs[0]['CoJob']['id'])), 0, $limit);
      $coJob['CoJob']['finish_summary'] = $coJob['CoJob']['register_summary'];
    } else {
      $coJob['CoJob']['status'] = ($queued ? JobStatusEnum::Queued : JobStatusEnum::InProgress);
      $coJob['CoJob']['register_summary'] = substr($summary, 0, $limit);
    }
    
    // Call create since we might have multiple records written in a transaction
    $this->create();
    
    if(!$this->save($coJob)) {
      $dbc->rollback();
      throw new RuntimeException(_txt('er.db.save-a', array('CoJob::register')));
    }
    
    $dbc->commit();
    
    if(!empty($jobs)) {
      // Now throw the exception
      throw new RuntimeException($coJob['CoJob']['register_summary']);
    }
    
    return $this->id;
  }
  
  /**
   * Set the percentage complete of the job.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $id      Job ID
   * @param  integer $percent Percent complete, between 0 and 100
   */

  public function setPercentComplete($id, $percent) {
    $this->clear();
    $this->id = $id;
    // Cake validation will allow floats in a range, so we cast to integer
    $this->saveField('percent_complete', (integer)$percent);
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
    if(!$id) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_jobs.1'), $id)));
    }
    
    // There's not really an elegant way to update more than 1 but less than all fields...
    
    $this->clear();
    $this->id = $id;
    $this->saveField('status', JobStatusEnum::InProgress);
    $this->saveField('start_time', date('Y-m-d H:i:s', time()));
    
    // Make sure $summary fits in the available space
    $limit = $this->validate['start_summary']['rule'][1];
    $this->saveField('start_summary', substr($summary, 0, $limit));
  }
  
  /**
   * Determine the start time for the specified job.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $id Job ID
   * @return Integer     Timestamp
   * @throws InvalidArgumentException
   */
  
  public function startTime($id) {
    $start = $this->field('start_time', array('CoJob.id' => $id));
    
    if(!$start) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_jobs.1'), $id)));
    }
    
    return strtotime($start);
  }
  
  /**
   * Update job information. Once set, values cannot be changed to null via this function.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $id           CO Job ID
   * @param  integer $jobTypeFk    CO Job Foreign Key
   * @param  string  $jobMode      CO Job Mode
   * @param  string  $startSummary Job start summary
   * @throws RuntimeException
   */
  
  public function update($id, $jobTypeFk=null, $jobMode=null, $startSummary=null) {
    if(!$id) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_jobs.1'), $id)));
    }
    
    $fieldList = array();
    
    $coJob = array(
      'CoJob' => array('id' => $id)
    );
    
    if($jobTypeFk) {
      $coJob['CoJob']['job_type_fk'] = $jobTypeFk;
      $fieldList[] = 'job_type_fk';
    }
    
    if($jobMode) {
      $coJob['CoJob']['job_mode'] = $jobMode;
      $fieldList[] = 'job_mode';
    }
    
    if($startSummary) {
      $coJob['CoJob']['start_summary'] = $startSummary;
      $fieldList[] = 'start_summary';
    }
    
    // Call create since we might have multiple records written in a transaction
    $this->create();
    
    if(!$this->save($coJob, array('fieldList' => $fieldList))) {
      throw new RuntimeException(_txt('er.db.save-a', array('CoJob::update')));
    }
  }

  /**
   * Perform SshKey model upgrade steps for version 3.3.0.
   * Resizes the job_type column.
   * This function should only be called by UpgradeVersionShell.
   *
   * @since  COmanage Registry v3.3.0
   */
  public function _ug330() {
    $dbc = $this->getDataSource();

    $db_driver = explode("/", $dbc->config['datasource'], 2);

    if ($db_driver[0] !== 'Database') {
      throw new RuntimeException("Unsupported db_method: " . $db_driver[0]);
    }

    $db_driverName = $db_driver[1];

    // Nothing to do if not Postgres
    if ($db_driverName !== 'Postgres') {
      return;
    }

    $dbprefix = $dbc->config['prefix'];

    $sql = "ALTER TABLE " . $dbprefix . "co_jobs ALTER COLUMN job_type TYPE varchar(32)";

    $this->query($sql);
  }
}
