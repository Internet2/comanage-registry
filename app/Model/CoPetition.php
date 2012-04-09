<?php
/**
 * COmanage Registry CO Petition Model
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoPetition extends AppModel {
  // Define class name for cake
  public $name = "CoPetition";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "ApproverCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'approver_co_person_id'
    ),
    "Co",                // A CO Petition is associated with a CO
    "Cou",               // A CO Petition may be associated with a COU
    "CoEnrollmentFlow",  // A CO Petition follows a CO Enrollment Flow
    "EnrolleeCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'enrollee_co_person_id'
    ),
    "EnrolleeCoPersonRole" => array(
      'className' => 'CoPersonRole',
      'foreignKey' => 'enrollee_co_person_role_id'
    ),
    "EnrolleeOrgIdentity" => array(
      'className' => 'OrgIdentity',
      'foreignKey' => 'enrollee_org_identity_id'
    ),
    "PetitionerCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'petitioner_co_person_id'),
    "SponsorCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'sponsor_co_person_id')
  );
  
  public $hasMany = array(
    // A CO Petition has zero or more CO Petition Attributes
    "CoPetitionAttribute" => array('dependent' => true),
    // A CO Petition has zero or more CO Petition History Records
    "CoPetitionHistoryRecord" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "CoPetition.id";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order? id will be ambiguous in some queries, but CoPetition.id
// breaks delete cascading since the model may be aliased to (eg) CoPetitionApprover.
//  public $order = array("id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_enrollment_flow_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'co_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'cou_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'enrollee_org_identity_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'enrollee_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'enrollee_co_person_role_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'petitioner_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'sponsor_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'approver_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Approved,
                                      StatusEnum::Declined,
                                      StatusEnum::Denied,
                                      StatusEnum::Invited,
                                      StatusEnum::PendingApproval)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'status_t'
  );

  /**
   * Update the status of a CO Petition.
   * - precondition: The Petition must be in a state suitable for the desired new status.
   *
   * @since  COmanage Registry v0.5
   * @param  Integer CO Petition ID
   * @param  StatusEnum Target status
   * @param  Integer CO Person ID of person causing update
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  function updatePetition($id, $newStatus, $actorCoPersonID) {
    // Try to find the status of the requested petition
    
    $this->id = $id;
    $curStatus = $this->field('status');
    
    if(!$curStatus) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
    }
    
    // Do we have a valid new status? If so, do we need to update CO Person status?
    $valid = false;
    $newCoPersonStatus = null;
    
    if($curStatus == StatusEnum::PendingApproval) {
      // A Petition can go from PendingApproval to Approved or Denied
      
      if($newStatus == StatusEnum::Approved
         || $newStatus == StatusEnum::Denied) {
        $valid = true;
        $newCoPersonStatus = $newStatus;
      }
    }
    
    if($valid) {
      // Process the new status
      $fail = false;
      
      // Start a tronsaction
      $dbc = $this->getDataSource();
      $dbc->begin();
      
      // Update the Petition status
      
      $this->saveField('status', $newStatus);
      
      // If this is an approval, update the approver field as well
      
      if($newStatus == StatusEnum::Approved) {
        $this->saveField('approver_co_person_id', $actorCoPersonID);
      }
      
      // Write a Petition History Record
      
      if(!$fail) {
        $petitionAction = null;
        
        switch($newStatus) {
          case StatusEnum::Approved:
            $petitionAction = PetitionActionEnum::Approved;
            break;
          case StatusEnum::Denied:
            $petitionAction = PetitionActionEnum::Denied;
            break;
        }
        
        try {
          $this->CoPetitionHistoryRecord->record($id,
                                                 $actorCoPersonID,
                                                 $petitionAction);
        }
        catch (Exception $e) {
          $fail = true;
        }
      }
      
      // Update CO Person Role state
      
      if(!$fail && isset($newCoPersonStatus)) {
        $coPersonRoleID = $this->field('enrollee_co_person_role_id');
        
        if($coPersonRoleID) {
          $this->EnrolleeCoPersonRole->id = $coPersonRoleID;
          $this->EnrolleeCoPersonRole->saveField('status', $newCoPersonStatus);
        } else {
          $fail = true;
        }
      }
      
      // Maybe update CO Person state, but only if it's currently Pending Approval
      
      if(!$fail && isset($newCoPersonStatus)) {
        $coPersonID = $this->field('enrollee_co_person_id');
        
        if($coPersonID) {
          $this->EnrolleeCoPerson->id = $coPersonID;
          
          $curCoPersonStatus = $this->EnrolleeCoPerson->field('status');
          
          if(isset($curCoPersonStatus)
             && ($curCoPersonStatus == StatusEnum::PendingApproval)) {
            $this->EnrolleeCoPerson->saveField('status', $newCoPersonStatus);
          }
          // else not a fail
        } else {
          $fail = true;
        }
      }
      
      if(!$fail) {
        // Commit
        
        $dbc->commit();
      } else {
        // Rollback
        
        $dbc->rollback();
        throw new RuntimeException(_txt('er.db.save'));
      }
    } else {
      throw new InvalidArgumentException(_txt('er.pt.status'), array($curStatus, $newStatus));
    }
  }
}
