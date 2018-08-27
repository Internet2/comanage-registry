<?php
/**
 * COmanage Registry CO Terms and Condition Agreements Model
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
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

/*
 * Note: This model looks plural because the phrase "Terms and Conditions" is actually
 * singular. Cake's inflector has been configured
 */  

class CoTAndCAgreement extends AppModel {
  // Define class name for cake
  public $name = "CoTAndCAgreement";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("CoTermsAndConditions", "CoPerson");
   
  // Default display field for cake generated views
  public $displayField = "identifier";
  
  public $actsAs = array('Containable', 'Provisioner');
  
  // Validation rules for table elements
  public $validate = array(
    'co_terms_and_conditions_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false,
        'message' => 'A CO Terms And Conditions ID must be provided'
      )
    ),
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'agreement_time' => array(
      'content' => array(
        'rule' => 'datetime',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'identifier' => array(
      'content' => array(
        'rule' => '/.*/',
        'required' => true,
        'allowEmpty' => false
      )
    ),
  );
  
  /**
   * Record a T&C Agreement.
   *
   * @since  COmanage Registry v0.8.3
   * @param  Integer CO Terms and Conditions identifier
   * @param  Integer CO Person identifier of CO Person agreeing to T&C
   * @param  Integer CO Person identifier of CO Person actually clicking agree button (admins can agree on behalf of other CO people)
   * @param  String Identifier of $actorCoPersonId
   * @param  Boolean Whether to trigger provisioning
   * @return Boolean True if successful
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function record($coTAndCId, $coPersonId, $actorCoPersonId, $identifier, $provision=true) {
    // Pull the T&C label
    
    $label = $this->CoTermsAndConditions->field('description',
                                                array('CoTermsAndConditions.id' => $coTAndCId));
    
    if(!$label) {
      throw new RuntimeException(_txt('er.notfound',
                                      array(_txt('ct.co_terms_and_conditions.1'),
                                            $coTAndCId)));
    }
    
    // Record the T&C
    
    $tandcData = array();
    $tandcData['CoTAndCAgreement']['co_terms_and_conditions_id'] = $coTAndCId;
    $tandcData['CoTAndCAgreement']['co_person_id'] = $coPersonId;
    $tandcData['CoTAndCAgreement']['agreement_time'] = date('Y-m-d H:i:s', time());
    $tandcData['CoTAndCAgreement']['identifier'] = $identifier;
    
    // Call create in case we have multiple agreements written in a transaction
    $this->create($tandcData);
    
    if(!$this->save($tandcData, array('provision' => $provision))) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Create a history record
    
    $action = ActionEnum::CoTAndCAgreement;
    $comment = _txt('rs.tc.agree', array($label));
    
    if($coPersonId != $actorCoPersonId) {
      $action = ActionEnum::CoTAndCAgreementBehalf;
      $comment = _txt('rs.tc.agree.behalf', array($label));
    }
    
    try {
      $this->CoPerson->HistoryRecord->record($coPersonId,
                                             null,
                                             null,
                                             $actorCoPersonId,
                                             $action,
                                             $comment);
    }
    catch(Exception $e) {
      throw new RuntimeException($e->getMessage());
    }
    
    return true;
  }
}
