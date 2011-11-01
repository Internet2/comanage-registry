<?php
  /*
   * COmanage Registry CO Petition Model
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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
   */

  class CoPetition extends AppModel {
    // Define class name for cake
    var $name = "CoPetition";
    
    // Association rules from this model to other models
    var $belongsTo = array("ApproverCoPerson" =>
                           array('className' => 'CoPerson',
                                 'foreignKey' => 'approver_co_person_id'),
                           "Co",                // A CO Petition is associated with a CO
                           "Cou",               // A CO Petition may be associated with a COU
                           "CoEnrollmentFlow",  // A CO Petition follows a CO Enrollment Flow
                           "EnrolleeCoPerson" =>
                           array('className' => 'CoPerson',
                                 'foreignKey' => 'enrollee_co_person_id'),
                           "EnrolleeCoPersonRole" =>
                           array('className' => 'CoPerson',
                                 'foreignKey' => 'enrollee_co_person_role_id'),
                           "EnrolleeOrgIdentity" =>
                           array('className' => 'OrgIdentity',
                                 'foreignKey' => 'enrollee_org_identity_id'),
                           "PetitionerCoPerson" =>
                           array('className' => 'CoPerson',
                                 'foreignKey' => 'petitioner_co_person_id'),
                           "SponsorCoPerson" =>
                           array('className' => 'CoPerson',
                                 'foreignKey' => 'sponsor_co_person_id'));
    
    var $hasMany = array("CoPetitionAttribute",       // A CO Petition has zero or more CO Petition Attributes
                         "CoPetitionHistoryRecords"); // A CO Petition has zero or more CO Petition History Records
    
    // Default display field for cake generated views
    var $displayField = "id";
    
    // Default ordering for find operations
    var $order = array("id");
    
    // Validation rules for table elements
    var $validate = array(
      'status' => array(
        'rule' => array('inList', array('A', 'I')),
        'required' => true,
        'message' => 'A valid status must be selected'
      )
    );
    
    // Enum type hints
    
    var $cm_enum_types = array(
      'status' => 'status_t'
    );
  }
?>