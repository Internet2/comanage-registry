<?php
/**
 * COmanage Registry Vet Job Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoJobBackend", "Model");

class VetJob extends CoJobBackend {
  /**
   * Execute the requested Job.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int   $coId    CO ID
   * @param  CoJob $CoJob   CO Job Object, id available at $CoJob->id
   * @param  array $params  Array of parameters, as requested via parameterFormat()
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function execute($coId, $CoJob, $params) {
    $VettingRequest = ClassRegistry::init('VettingRequest');
    $VettingStep = ClassRegistry::init('VettingStep');
    
    // We'll have a vetting request ID in $params, so dispatch it for processing
    
    if(empty($params['co_person_id'])) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_people.1'))));
    }
    
    if(empty($params['vetting_request_id'])) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.vetting_requests.1'))));
    }
    
    // Pull the Vetting Request along with any previously recorded Vetting Results
    
    $args = array();
    $args['conditions']['VettingRequest.id'] = $params['vetting_request_id'];
    $args['contain'] = array(
      'CoPetition',
      'VettingResult' => array('order' => array('VettingResult.created' => 'desc'))
    );
    
    $request = $VettingRequest->find('first', $args);
    
    if(empty($request)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.vetting_requests.1'), $params['vetting_request_id'])));
    }
    
    // If the VettingRequest status is anything other than Requested, throw an error
    
    if($request['VettingRequest']['status'] != VettingStatusEnum::Requested) {
      throw new InvalidArgumentException(__txt('er.vetting.status', array($params['vetting_request_id'], $request['VettingRequest']['status'])));
    }
    
    // We might be returning to a Request that was formerly Pending; an in progress
    // Request will have at least one entry in $request['VettingResult'].
    
    $args = array();
    $args['conditions']['VettingStep.co_id'] = $coId;
    $args['conditions']['VettingStep.status'] = SuspendableStatusEnum::Active;
    $args['order'] = array('VettingStep.ordr' => 'asc');
    $args['contain'] = false;
    
    $steps = $VettingStep->find('all', $args);
    
    $stepsDone = 0;
    
    foreach($steps as $step) {
      $stepId = $step['VettingStep']['id'];
      $result = null;
      
      // First see if we have a result for this step already. This would be the
      // case if a step previously ended in Pending status.
      
      $stepResults = Hash::extract($request, 'VettingResult.{n}[vetting_step_id='.$stepId.']');
      
      if(!empty($stepResults[0])) {
        // If there is more than one result for this step, we should have the
        // most recent result first (per the ordering, above). ie: We can ignore
        // all the other (earlier) results.
        
        // We'll basically take the most recent result and stuff it into $result
        // for processing below. If we have more than one step, the earlier steps
        // by definition must have passed, so we'll just run the loop to the next
        // step. If the most recent result is in any state other than passed,
        // then we'll use the usual logic to determine what to do next.
        
        $result = array(
          'result' => $stepResults[0]['status'],
          'comment' => $stepResults[0]['comment']
        );
        // We don't override the result when replaying (otherwise we'll get
        // stuck in a loop), but we need to set $effectiveResult since it's
        // used below
        $effectiveResult = $result['result'];
        
        // Note that we're replaying a previous result
        $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                           $stepId,
                                           _txt('rs.vetting.replayed', array($stepId, $step['VettingStep']['description'])),
                                           $params['co_person_id'],
                                           null,
                                           JobStatusEnum::InProgress);
      } else {
        // We haven't yet run this step, so do so now
        
        // We'll record the current step in the Vetting Request to facilitate
        // reporting in the user interface, but we don't actually use it for
        // calculating what to do next.
        $VettingRequest->clear();
        $VettingRequest->id = $request['VettingRequest']['id'];
        $VettingRequest->saveField('vetting_step_id', $stepId);
        
        $result = $VettingStep->run($stepId, $params['co_person_id'], $params['vetting_request_id']);
        
        // Depending on the Vetting Step config, we might override the result
        $effectiveResult = $result['result'];
        
        if(($step['VettingStep']['review_on_result'] == VettingStatusEnum::Failed
            && $result['result'] == VettingStatusEnum::Failed)
           ||
           ($step['VettingStep']['review_on_result'] == VettingStatusEnum::Passed
               && in_array($result['result'], array(VettingStatusEnum::Failed, VettingStatusEnum::Passed)))) {
          $effectiveResult = VettingStatusEnum::PendingManual;
          
          $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                             $stepId,
                                             _txt('rs.vetting.manual', array($step['VettingStep']['description'], _txt('en.status.vet', null, $result['result']))),
                                             $params['co_person_id'],
                                             null,
                                             JobStatusEnum::InProgress);
        }
      }
      
      switch($effectiveResult) {
        case VettingStatusEnum::Canceled:
        case VettingStatusEnum::Error:
        case VettingStatusEnum::Failed:
          // Terminate the Request and the Job
          $txt = _txt('rs.vetting.failed', array($params['vetting_request_id'], $result['result'], $stepId, $step['VettingStep']['description']));
          $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                             $stepId,
                                             (!empty($result['comment']) ? $result['comment'] : _txt('en.status.vet', null, $result['result'])),
                                             $params['co_person_id'],
                                             null,
                                             JobStatusEnum::Failed);
          $VettingRequest->complete($params['vetting_request_id'],
                                    $effectiveResult,
                                    $txt);
          if($result['result'] == VettingStatusEnum::Failed) {
            // It's not clear what we should do with a Petition if Vetting is
            // canceled or errors out, so we leave it in Pending Vetting
            $this->updatePetition($CoJob, $request, $effectiveResult);
          }
          $CoJob->finish($CoJob->id, $txt, JobStatusEnum::Failed);
          return;
        case VettingStatusEnum::PendingManual:
        case VettingStatusEnum::PendingResult:
        // Plugins shouldn't return Requested, but just in case...
        case VettingStatusEnum::Requested:
          // Only terminate the Job, since something will restart the Request
          // Note we intentionally record the original result in the comment so
          // there's a record of it, but then we update the vetting request with
          // the new effectiveResult.
          $txt = _txt('rs.vetting.pending', array($params['vetting_request_id'], $result['result'], $stepId, $step['VettingStep']['description']));
          $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                             $stepId,
                                             (!empty($result['comment']) ? $result['comment'] : _txt('en.status.vet', null, $result['result'])),
                                             $params['co_person_id'],
                                             null,
                                             JobStatusEnum::InProgress);
          $CoJob->finish($CoJob->id, $txt);
          // Update the Vetting Request with the new status
          $VettingRequest->clear();
          $VettingRequest->id = $params['vetting_request_id'];
          $VettingRequest->saveField('status', $effectiveResult);
          // Possibly send notifications
          if($effectiveResult == VettingStatusEnum::PendingManual) {
            $VettingRequest->sendNotifications($params['vetting_request_id'], $params['actor_co_person_id']);
          }
          return;
        case VettingStatusEnum::Passed:
          // Resolve any outstanding notification (from PendingManual results)
          $VettingRequest->resolveNotifications($params['vetting_request_id'], $params['actor_co_person_id']);
          // Continue the loop
          break;
      }
      
      // Record a Job History Record
      $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                         $stepId,
                                         (!empty($result['comment']) ? $result['comment'] : _txt('en.status.vet', null, $result['result'])),
                                         $params['co_person_id']);
                                         // XXX or + [null, Failed]
      
      $stepsDone++;
      
      // Update % complete
      $CoJob->setPercentComplete($CoJob->id, (integer)($stepsDone * 100)/count($steps));
    }
    
    // If we made it here, all steps were successful and there's nothing else
    // to do but clean up.
    
    $txt = _txt('rs.vetting.completed', array($params['vetting_request_id'], count($steps)));
    
    $VettingRequest->complete($params['vetting_request_id'],
                              VettingStatusEnum::Passed,
                              $txt);
    
    // If we passed vetting, re-enter the Enrollment Flow, if there is one.
    // Since we're operating via cron here, the way we do this (for now) is by
    // requiring Approval, and triggering Approval Notification.
    
    $this->updatePetition($CoJob, $request, VettingStatusEnum::Passed);
    
    $CoJob->finish($CoJob->id, $txt);
  }
  
  /**
   * Obtain the list of parameters supported by this Job.
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Array of supported parameters.
   */
  
  public function parameterFormat() {
    $params = array();
    
    return $params;
  }
  
  /**
   * Update the CO Petition associated with a Vetting Request.
   *
   * @since  COmanage Registry v4.1.0
   * @param  CoJob              $CoJob    CoJob object
   * @param  array              $request  Array of VettingRequest data
   * @param  VettingStatusEnum  $result   Result of Vetting
   */
  
  protected function updatePetition($CoJob, $request, $result) {
    if(!empty($request['CoPetition']['id'])) {
      // This vetting request is associated with a CO Petition.
      
      $CoEnrollmentFlow = ClassRegistry::init('CoEnrollmentFlow');
      
      // Make sure the Enrollment Flow is configured for Requires Approval
      $enabled = $CoEnrollmentFlow->field('approval_required', array('CoEnrollmentFlow.id' => $request['CoPetition']['co_enrollment_flow_id']));
      $comment = "";
      
      if($enabled) {
        // This is mostly copy/paste from CoPetitionsController::execute_sendApproverNotification
        // and execute_approve.
        
        $CoPetition = ClassRegistry::init('CoPetition');
        
        // Annoyingly, sendApproverNotification assumes updateStatus hasn't been
        // called yet, while sendApprovalNotification assumes it has...
        
        if($result == VettingStatusEnum::Passed) {
          // Notify the *Approver* that the Petition is pending Approval
          
          $comment = _txt('rs.vetting.ef', array($request['CoPetition']['co_enrollment_flow_id'], $request['CoPetition']['id']));
          
          $CoPetition->sendApproverNotification($request['CoPetition']['id'], null);
          
          // We don't pass $comment because it's only used for approval/denial
          // and we're not approving the Petition here
          $CoPetition->updateStatus($request['CoPetition']['id'],
                                    PetitionStatusEnum::PendingApproval, 
                                    null);
        } else {
          // Notify the *Enrollee* that the Petition is Denied
          
          $comment = _txt('rs.vetting.ef.deny', array($request['CoPetition']['id']));
          
          $CoPetition->updateStatus($request['CoPetition']['id'],
                                    PetitionStatusEnum::Denied, 
                                    null,
                                    $comment);
          
          $CoPetition->sendApprovalNotification($request['CoPetition']['id'], null);
        }
        
        $CoPetition->CoPetitionHistoryRecord->record($request['CoPetition']['id'],
                                                     null,
                                                     PetitionActionEnum::VettingCompleted,
                                                     $comment);
      } else {
        // Record an error, but there's not much else to do since we're already
        // done with the vetting process
        
        $comment = _txt('er.vetting.ef', array($request['CoPetition']['co_enrollment_flow_id'], $request['CoPetition']['id']));
      }
      
      $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                         null,
                                         $comment,
                                         $request['VettingRequest']['co_person_id'],
                                         null,
                                         JobStatusEnum::Notice);
      
      $CoJob->Co->CoPerson->HistoryRecord->record($request['VettingRequest']['co_person_id'],
                                                  null,
                                                  null,
                                                  null,
                                                  ActionEnum::CoPetitionUpdated,
                                                  $comment);
    }
    // else nothing to do
  }
}
