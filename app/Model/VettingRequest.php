<?php
/**
 * COmanage Registry Vetting Request Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class VettingRequest extends AppModel {
  // Define class name for cake
  public $name = "VettingRequest";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         // We need linkable to run first to set up the query,
                         // changelog to run next to clean it up, and then
                         // containable (which actually doesn't do anything here)
                         'Linkable.Linkable' => array('priority' => 4),
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoPerson",
    "CoJob",
    "VettingStep"
  );
  
  public $hasMany = array(
    "VettingResult" => array('dependent' => true)
  );
  
  public $hasOne = array(
    "CoPetition"
  );
  
  // Default display field for cake generated views
  public $displayField = "id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'vetting_step_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'co_job_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array(
        'inList',
        array(
          VettingStatusEnum::Canceled,
          VettingStatusEnum::Failed,
          VettingStatusEnum::Passed,
          VettingStatusEnum::PendingManual,
          VettingStatusEnum::PendingResult,
          VettingStatusEnum::Requested
        )
      ),
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  // Groupings of Vetting Statuses, for various logical purposes
  public $statusGroupings  = array(
    // "active" statuses are those which preclude a new request from being registered
    'active' => array(
      VettingStatusEnum::PendingManual,
      VettingStatusEnum::PendingResult,
      VettingStatusEnum::Requested
    ),
    // "done" statuses are those which can't be canceled
    'done' => array(
      VettingStatusEnum::Canceled,
      VettingStatusEnum::Failed,
      VettingStatusEnum::Passed
    )
  );
  
  /**
   * Cancel a Vetting Request.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int $id              Vetting Request ID
   * @param  int $actorCoPersonId Actor CO Person ID
   * @throws InvalidArgumentException
   */

  public function cancel($id, $actorCoPersonId) {
    // We don't bother with a transaction here because if something goes wrong
    // we still want to cancel as much as possible.
    
    $args = array();
    $args['conditions']['VettingRequest.id'] = $id;
    $args['contain'] = array('CoPetition');
    
    $request = $this->find('first', $args);
    
    if(empty($request)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.vetting_requests.1'), $id)));
    }
    
    if(in_array($request['VettingRequest']['status'], $this->statusGroupings['done'])) {
      throw new InvalidArgumentException(_txt('er.vetting.cxl'));
    }
    
    $this->clear();
    $this->id = $id;
    $this->saveField('status', VettingStatusEnum::Canceled);
    
    // Cancel the associated Job, though there may not be a current Job (if for
    // example the Vetting Request is Pending Manual Resolution).
    if(!empty($request['VettingRequest']['co_job_id'])
       && $this->CoPerson->Co->CoJob->cancelable($request['VettingRequest']['co_job_id'])) {
      $this->CoPerson->Co->CoJob->cancel($request['VettingRequest']['co_job_id'], $actorCoPersonId);
    }
    
    // Add a History Record
    $this->CoPerson->HistoryRecord->record(
      $request['VettingRequest']['co_person_id'],
      null,
      null,
      $actorCoPersonId,
      ActionEnum::VettingRequestCanceled,
      _txt('rs.vetting.canceled', array($id))
    );
    
    if(!empty($request['CoPetition']['id'])) {
      // If this request is associated with a Petition, Deny the Petition. We do
      // this primarily to handle the case of someone denying a request that was
      // pending manual review, but also if someone manually cancels a request
      // associated with a Petition it probably makes sense to terminate the
      // enrollment as well.
      
      $comment = _txt('rs.vetting.ef.deny', array($request['CoPetition']['id']));
      
      $this->CoPetition->updateStatus($request['CoPetition']['id'],
                                      PetitionStatusEnum::Denied, 
                                      null,
                                      $comment);
      
      // Notify the *Enrollee* that the Petition is Denied (if configured)
      $this->CoPetition->sendApprovalNotification($request['CoPetition']['id'], null);
    }
    
    // Terminate any pending notifications
    $this->resolveNotifications($id, $actorCoPersonId);
  }
  
  /**
   * Complete a Vetting Request
   *
   * @since  COmanage Registry v4.1.0
   * @param  int                $id               Vetting Request ID
   * @param  VettingStatusEnum  $status           Vetting Status Result
   * @param  strings            $comment          Comment
   * @param  int                $actorCoPersonId  Actor CO Person ID
   * @throws RuntimeException
   */
  
  public function complete($id, $status, $comment, $actorCoPersonId=null) {
    // We don't resolve the CoJob, since we expect to be called from there
    
    try {
      $this->_begin();
      
      $this->clear();
      $this->id = $id;
      // This creates multiple changelog entries, one per save...
      $this->saveField('status', $status);
      $this->saveField('comment', $comment);
      $this->saveField('vetting_step_id', null);
      
      // Grab the CO Person ID from the request
      $coPersonId = $this->field('co_person_id', array('VettingRequest.id' => $id));
      
      // Add a History Record
      $this->CoPerson->HistoryRecord->record(
        $coPersonId,
        null,
        null,
        $actorCoPersonId,
        ActionEnum::VettingRequestCompleted,
        $comment
      );
      
      // Terminate any pending notifications
      $this->resolveNotifications($id, $actorCoPersonId);
      
      $this->_commit();
    }
    catch(Exception $e) {
      $this->_rollback();
      throw new RuntimeException($e->getMessage());
    }
  }
  
  /**
   * Queue (register) a CO Job for the Vetting Request.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int  $id         Vetting Request ID
   * @param  bool $requeue    If true, this Vetting Request is being requeued for further processing
   * @param  int $actorCoPersonId Actor CO Person ID
   * @return int              CO Job ID
   */
  
  public function queue($id, $requeue=false, $actorCoPersonId=null) {
    // Register a CoJob to process this request.
    
    $txt = _txt('rs.vetting.registered', array($id));
    $action = ActionEnum::VettingRequestRegistered;
    
    if($requeue) {
      $txt = _txt('rs.vetting.requeued', array($id));
      $action = ActionEnum::VettingRequestRequeued;
    }
    
    // We need to map the CO Person to a CO to proceed.
    
    $coPersonId = $this->field('co_person_id', array('VettingRequest.id' => $id));
    $coId = $this->CoPerson->field('co_id', array('CoPerson.id' => $coPersonId));
    
    // register() starts (and commits) its own transaction
    $jobId = $this->CoPerson->Co->CoJob->register(
      $coId,
      'CoreJob.Vet',
      null,
      "",
      $txt,
      true,
      false,
      array(
        'co_person_id' => $coPersonId,
        'vetting_request_id' => $id,
        'actor_co_person_id' => $actorCoPersonId
      )
    );
    
    // Update the Vetting Request with a reference to the Job ID
    $this->clear();
    $this->id = $id;
    $this->saveField('co_job_id', $jobId);
    
    if($requeue) {
      // Reset the status to Requested from whatever it was
      $this->saveField('status', VettingStatusEnum::Requested);
    }
    
    // Add a History Record
    $this->CoPerson->HistoryRecord->record(
      $coPersonId,
      null,
      null,
      $actorCoPersonId,
      $action,
      $txt
    );
    
    return $jobId;
  }
  
  /**
   * Register a new Vetting Request.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int $coPersonId      CO Person ID
   * @param  int $actorCoPersonId Actor CO Person ID
   * @return int                  Vetting Request ID
   * @throws OverflowException
   * @throws RuntimeException
   */
  
  public function register($coPersonId, $actorCoPersonId) {
    // We only allow one active Vetting Request at a time.

    $this->_begin();

    $args = array();
    $args['conditions']['VettingRequest.co_person_id'] = $coPersonId;
    $args['conditions']['VettingRequest.status'] = $this->statusGroupings['active'];
    $args['contain'] = false;
    
    $reqs = $this->findForUpdate($args['conditions'], array('id'));
    
    if(!empty($reqs[0]['VettingRequest']['id'])) {
      throw new OverflowException(_txt('er.vetting.dupe', array($reqs[0]['VettingRequest']['id'])));
    }

    $req = array(
      'co_person_id' => $coPersonId,
      'status' => VettingStatusEnum::Requested
    );
    
    $reqId = null;
    
    try {
      $this->clear();
      $this->save($req);
      
      $reqId = $this->id;
      
      $jobId = $this->queue($reqId, false, $actorCoPersonId);
      
      $this->_commit();
    }
    catch(Exception $e) {
      $this->_rollback();
      throw new RuntimeException($e->getMessage());
    }

    return $reqId;
  }
  
  /**
   * Resolve notifications associated with a Vetting Request.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int  $id               Vetting Request ID
   * @param  int  $actorCoPersonId  Actor CO Person ID
   */
  
  public function resolveNotifications($id, $actorCoPersonId) {
    $this->VettingStep
         ->VetterCoGroup
         ->CoNotificationRecipientGroup
         ->resolveFromSource(array(
                               'controller'  => 'vetting_requests',
                               'action'      => 'view',
                               'id'          => $id
                             ),
                             $actorCoPersonId);
  }
  
  /**
   * Send notifications associated with a Vetting Request.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int  $id               Vetting Request ID
   * @param  int  $actorCoPersonId  Actor CO Person ID
   */
  
  public function sendNotifications($id, $actorCoPersonId) {
    // Send notifications the vetter_co_group_id (if set) or the CO Admins that
    // this request requires manual review.
    
    // First, figure out who to notify
    $args = array();
    $args['conditions']['VettingRequest.id'] = $id;
    $args['contain'] = array('VettingStep');
    
    $request = $this->find('first', $args);
    
    $recipientCoGroupId = $request['VettingStep']['vetter_co_group_id'];
    
    if(!$recipientCoGroupId) {
      $recipientCoGroupId = $this->VettingStep->Co->CoGroup->adminCoGroupId($request['VettingStep']['co_id']);;
    }
    
    if(!$recipientCoGroupId) {
      return;
    }
    
    $this->VettingStep
         ->VetterCoGroup
         ->CoNotificationRecipientGroup
         ->register($request['VettingRequest']['co_person_id'],
                    null,
                    $actorCoPersonId,
                    'cogroup',
                    $recipientCoGroupId,
                    ActionEnum::VettingRequestRegistered,
                    _txt('em.vetting.body'),
                    array(
                      'controller'  => 'vetting_requests',
                      'action'      => 'view',
                      'id'          => $id
                    ),
                    true,
                    'em.vetting.subject',
                    'em.vetting.body');
  }
}