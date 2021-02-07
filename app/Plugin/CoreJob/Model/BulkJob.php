<?php
/**
 * COmanage Registry Bulk Job Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoJobBackend", "Model");

class BulkJob extends CoJobBackend {
  /**
   * Execute the requested Job.
   *
   * @since  COmanage Registry v4.0.0
   * @param  int   $coId    CO ID
   * @param  CoJob $CoJob   CO Job Object, id available at $CoJob->id
   * @param  array $params  Array of parameters, as requested via parameterFormat()
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function execute($coId, $CoJob, $params) {
    try {
      $this->validateParams($coId, $params);
      
      $records = explode(',', $params['records']);
      $actionArgs = $this->parseActionArgs(isset($params['actionArgs']) ? $params['actionArgs'] : null);
      
      switch($params['action']) {
        case 'expunge':
          $this->expunge($CoJob, $records);
          break;
        case 'updateStatus':
          $this->updateStatus($CoJob, $params['recordType'], $records, $actionArgs);
          break;
        default:
          throw new InvalidArgumentException(_txt('er.bulkjob.action.unknown', array($params['action'])));
          break;
      }
      
      $CoJob->finish($CoJob->id, _txt('pl.bulkjob.done'));
    }
    catch(Exception $e) {
      $CoJob->finish($CoJob->id, $e->getMessage(), JobStatusEnum::Failed);
    }
  }
  
  /**
   * Expunge records.
   *
   * @since  COmanage Registry v4.0.0
   * @param  CoJob $CoJob     CO Job
   * @param  array $recordIds List of records to expunge
   */
  
  protected function expunge($CoJob, $recordIds) {
    $CoPerson = ClassRegistry::init('CoPerson');
    
    foreach($recordIds as $r) {
      // Normally we'd put a $coPersonId link into the CoJobHistoryRecord, but
      // we're about to expunge it, so...
      
      $CoPerson->expunge($r, null);
      
      $CoJob->CoJobHistoryRecord->record($CoJob->id, $r, _txt('rs.expunged'));
    }
  }
  
  /**
   * Obtain the list of parameters supported by this Job.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Array of supported parameters.
   */
  
  public function parameterFormat() {
    $params = array(
      'action' => array(
        'help'     => _txt('pl.bulkjob.arg.action'),
        'type'     => 'select',
        'required' => true,
        'choices'  => array('expunge', 'updateStatus')
      ),
      'actionArgs' => array(
        'help'     => _txt('pl.bulkjob.arg.actionArgs'),
        'type'     => 'string',
        'required' => false
      ),
      'recordType' => array(
        'help'     => _txt('pl.bulkjob.arg.recordType'),
        'type'     => 'select',
        'required' => true,
        'choices'  => array('CoPerson', 'CoPersonRole')
      ),
      'records' => array(
        'help'     => _txt('pl.bulkjob.arg.records'),
        'type'     => 'string',
        'required' => true
      )
    );
    
    return $params;
  }
  
  /**
   * Parse an action argument string into an array.
   *
   * @since  COmanage Registry v4.0.0
   * @param  string $actionArgs Action specific arguments
   * @return array              Arguments parsed into an array
   */
  
  protected function parseActionArgs($actionArgs) {
    $ret = array();
    
    if(!empty($actionArgs)) {
      $as = explode(',', $actionArgs);
      
      foreach($as as $a) {
        $bits = explode('=', $a);
        $ret[ $bits[0] ] = $bits[1];
      }
    }
    
    return $ret;
  }
  
  /**
   * Update record status.
   *
   * @since  COmanage Registry v4.0.0
   * @param  CoJob  $CoJob      CO Job
   * @param  string $recordType Record type to process
   * @param  array  $recordIds  List of records to expunge
   * @param  array  $actionArgs Action specific arguments
   */
  
  protected function updateStatus($CoJob, $recordType, $recordIds, $actionArgs=null) {
    $Model = ClassRegistry::init($recordType);
    
    foreach($recordIds as $r) {
      // We need a CO Person ID for History Record purposes
      
      $copid = null;
      $action = null;
      
      if($recordType == 'CoPerson') {
        $copid = $r;
        $action = ActionEnum::CoPersonEditedManual;
      } else {
        // CoPersonRole, need to map
        $copid = $Model->field('co_person_id', array($recordType.'.id' => $r));
        $action = ActionEnum::CoPersonRoleEditedManual;
      }
      
      $Model->clear();
      $Model->id = $r;
      // validateParams already checked that status was specified
      $Model->saveField('status', $actionArgs['status']);
      
      $Model->HistoryRecord->record($copid,
                                    ($recordType == 'CoPersonRole' ? $r : null),
                                    null,
                                    null,
                                    $action,
                                    _txt('pl.bulkjob.updateStatus.done', array($recordType, $actionArgs['status'])));
      
      $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                         $r,
                                         _txt('pl.bulkjob.updateStatus.done', array($recordType, $actionArgs['status'])),
                                         $copid);
    }
  }
  
  /**
   * Validate Bulk Job parameters.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $coId   CO ID
   * @param  array   $params Array of parameters
   * @return boolean         true if parameters are valid
   * @throws InvalidArgumentException
   */
  
  protected function validateParams($coId, $params) {
    $actions = array(
      'expunge' => array('CoPerson'),
      'updateStatus' => array('CoPerson', 'CoPersonRole')
    );
    
    $actionArgs = array(
      'expunge' => null,
      'updateStatus' => array('status')
    );
    
    // First make sure the requested action supports the requested recordType
    
    if(!in_array($params['recordType'], $actions[ $params['action'] ])) {
      throw new InvalidArgumentException(_txt('er.bulkjob.recordType', array($params['action'], $params['recordType'])));
    }
    
    // Next make sure any required action specific arguments were provided
    
    if(!empty($actionArgs[ $params['action'] ])) {
      // Action Args are of the form key1=value1,key2=value2
      $parsedActionArgs = null;
      
      if(!empty($params['actionArgs'])) {
        $parsedActionArgs = $this->parseActionArgs($params['actionArgs']);
      }
      
      foreach($actionArgs[ $params['action'] ] as $a) {
        if(!$parsedActionArgs || !array_key_exists($a, $parsedActionArgs)) {
          throw new InvalidArgumentException(_txt('er.bulkjob.arg.actionArgs', array($a)));
        }
      }
    }
    
    // Finally make sure all record IDs are in $coId
    
    $Model = ClassRegistry::init($params['recordType']);
    
    foreach(explode(',', $params['records']) as $r) {
      if($Model->findCoForRecord($r) != $coId) {
        throw new InvalidArgumentException(_txt('er.bulkjob.record.co', array($r, $coId)));
      }
    }
    
    return true;
  }
}
