<?php
/**
 * COmanage Registry CO Petition History Model
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoPetitionHistoryRecord extends AppModel {
  // Define class name for cake
  public $name = "CoPetitionHistoryRecord";
  
  // Add behaviors
  // We add Changelog so that if a petition is deleted (archived), the related
  // history is archived as well (and not physically removed from the database)
  public $actsAs = array('Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A CO Petition History Record is attached to a CO Petition
    "CoPetition",
    "ActorCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'actor_co_person_id'
    )
  );

  // Default display field for cake generated views
  public $displayField = "comment";
  
  // Default ordering for find operations
  public $order = array("comment");
  
  // Validation rules for table elements
  public $validate = array(
    'comment' => array(
      'rule' => array('maxLength', 160),
      'required' => false,
      'allowEmpty' => false
    )
  );
  
  /**
   * Create a CO Petition History Record.
   *
   * @since  COmanage Registry v0.5
   * @param  Integer CO Petition ID
   * @param  Integer Actor CO Person ID
   * @param  PetitionActionEnum Action
   * @param  String Comment (if not provided, default comment for $action is used)
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  function record($coPetitionID, $actorCoPersonID, $action, $comment=null) {
    $coPetitionHistoryData = array();
    $coPetitionHistoryData['CoPetitionHistoryRecord']['co_petition_id'] = $coPetitionID;
    $coPetitionHistoryData['CoPetitionHistoryRecord']['actor_co_person_id'] = $actorCoPersonID;
    $coPetitionHistoryData['CoPetitionHistoryRecord']['action'] = $action;
    
    if(isset($comment)) {
      $limit = $this->validate['comment']['rule'][1];
      
      $coPetitionHistoryData['CoPetitionHistoryRecord']['comment'] = substr($comment, 0, $limit);
    } else {
      // Figure out a default value
      
      $coPetitionHistoryData['CoPetitionHistoryRecord']['comment'] = _txt('en.action.petition', null, $action);
    }
    
    // Call create since we might have multiple history records written in a transaction
    $this->create($coPetitionHistoryData);
    
    if(!$this->save($coPetitionHistoryData)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
  }
}
