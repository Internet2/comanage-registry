<?php
/**
 * COmanage Registry Authenticator Status Model
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class AuthenticatorStatus extends AppModel {
  // Define class name for cake
  public $name = "AuthenticatorStatus";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Authenticator",
    "CoPerson"
  );
  
  // Default display field for cake generated views
  public $displayField = "status";
  
  // Validation rules for table elements
  public $validate = array(
    'authenticator_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'locked' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Update the status for an Authenticator for a CO Person.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  integer $authenticatorId Authenticator ID
   * @param  integer $coPersonId      CO Person ID
   * @param  integer $actorCoPersonId Actor CO Person ID
   * @param  boolean $locked          true if the Authenticator is locked, false otherwise
   * @throws RuntimeException
   */
  
  public function setStatus($authenticatorId, $coPersonId, $actorCoPersonId, $locked=false) {
    // First see if this Authenticator is already at this status.
    // We need to see if there is a record already anyway.
    
    $this->_begin();
    
    $args = array();
    $args['conditions']['AuthenticatorStatus.authenticator_id'] = $authenticatorId;
    $args['conditions']['AuthenticatorStatus.co_person_id'] = $coPersonId;
    $args['contain'][] = 'Authenticator';
    
    $curStatus = $this->find('first', $args);
    
    // Start constructing the record to save
    
    $data = array(
      'AuthenticatorStatus' => array(
        'authenticator_id' => $authenticatorId,
        'co_person_id'     => $coPersonId
      )
    );
    
    $curStatusFlag = AuthenticatorStatusEnum::NotSet;
    
    if(!empty($curStatus)) {
      // Current record, key the data
      $data['AuthenticatorStatus']['id'] = $curStatus['AuthenticatorStatus']['id'];
      
      if(!$locked) {
        if(!isset($curStatus['AuthenticatorStatus']['locked'])
           || !$curStatus['AuthenticatorStatus']['locked']) {
          // Record is not locked, cannot unlock
          $this->_rollback();
          throw new RuntimeException(_txt('er.authr.unlocked'));
        }
      } elseif($locked) {
        if(isset($curStatus['AuthenticatorStatus']['locked'])
           && $curStatus['AuthenticatorStatus']['locked']) {
          // Record is locked, cannot lock
          $this->_rollback();
          throw new RuntimeException(_txt('er.status.already', array(_txt('en.status.authr', null, $newStatus))));
        }
      }
    }
    // else no current record
    
    $comment = "";
    
    if(!$locked) {
      $data['AuthenticatorStatus']['locked'] = false;
      $comment = _txt('rs.authr.unlocked', array($curStatus['Authenticator']['description']));
    } elseif($locked) {
      $data['AuthenticatorStatus']['locked'] = true;
      $comment = _txt('rs.authr.locked', array($curStatus['Authenticator']['description']));
    }
    
    $this->save($data);
    
    // Cut history
    $this->CoPerson->HistoryRecord->record($coPersonId,
                                           null,
                                           null,
                                           $actorCoPersonId,
                                           ActionEnum::AuthenticatorStatusEdited,
                                           $comment);
    
    $this->_commit();
    return true;
  }
}