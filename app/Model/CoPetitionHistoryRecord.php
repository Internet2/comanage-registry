<?php
/**
 * COmanage Registry CO Petition History Model
 *
 * Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoPetitionHistoryRecord extends AppModel {
  // Define class name for cake
  public $name = "CoPetitionHistoryRecord";
  
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
      $coPetitionHistoryData['CoPetitionHistoryRecord']['comment'] = $comment;
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
