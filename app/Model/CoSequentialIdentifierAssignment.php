<?php
/**
 * COmanage Registry CO Sequential Identifier Assignment Model
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
 * @since         COmanage Registry v0.6
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoSequentialIdentifierAssignment extends AppModel {
  // Define class name for cake
  public $name = "CoSequentialIdentifierAssignment";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoIdentifierAssignment");
  
  // Default display field for cake generated views
  public $displayField = "id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_identifier_assignment_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Identifier Assignment ID must be provided'
    ),
    'affix' => array(
      'rule' => '/.*/',
      'required' => true
    ),
    'last' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A last value must be provided'
    )
  );
  
  /**
   * Obtain the next sequence number for the specified identifier assignment.
   * NOTE: This method should be called from within a transaction
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CoIdentifierAssignment ID
   * @param  String Affix to obtain a sequence number for
   * @param  Integer Initial value to return if sequence not yet started
   * @return Integer Next sequence
   * @throws RuntimeException
   */
  
  public function next($coIdentifierAssignmentID, $affix, $start) {
    // We're basically implementing sequences. We don't actually use sequences
    // because dynamically creating sequences is a recipe for platform dependent
    // coding.
    
    $newCount = 1;
    
    if($start && $start > -1) {
      $newCount = $start;
    }
    
    // Get the current value for this affix. We need to use findForUpdate in case
    // another process is trying to assign the same sequence number at the same time.
    
    $args = array();
    $args['conditions']['CoSequentialIdentifierAssignment.co_identifier_assignment_id'] = $coIdentifierAssignmentID;
    $args['conditions']['CoSequentialIdentifierAssignment.affix'] = $affix;
    
    $cur = $this->findForUpdate($args['conditions'], array('id', 'last'));
    
    $seqData = array();
    
    if(!empty($cur)) {
      // Increment an existing counter
      
      $newCount = $cur[0]['CoSequentialIdentifierAssignment']['last'] + 1;
      
      $seqData[0]['CoSequentialIdentifierAssignment']['id'] = $cur[0]['CoSequentialIdentifierAssignment']['id'];
      $seqData[0]['CoSequentialIdentifierAssignment']['co_identifier_assignment_id'] = $coIdentifierAssignmentID;
      $seqData[0]['CoSequentialIdentifierAssignment']['affix'] = $affix;
      $seqData[0]['CoSequentialIdentifierAssignment']['last'] = $newCount;
    } else {
      // Start a new counter
      
      $seqData['CoSequentialIdentifierAssignment']['co_identifier_assignment_id'] = $coIdentifierAssignmentID;
      $seqData['CoSequentialIdentifierAssignment']['affix'] = $affix;
      $seqData['CoSequentialIdentifierAssignment']['last'] = $newCount;
    }
    
    // Reset the model state in case we're called more than once
    $this->create($seqData);
    
    if($this->saveAll($seqData)) {
      return $newCount;
    } else {
      // throw an error
       throw new RuntimeException(_txt('er.db.save'));
    }
  }
}

