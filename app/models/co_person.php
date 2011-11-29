<?php
  /*
   * COmanage Gears CO Person Model
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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

  class CoPerson extends AppModel {
    // Define class name for cake
    var $name = "CoPerson";
    
    // Add behaviors
    var $actsAs = array('Containable');
    
    // Association rules from this model to other models
    var $belongsTo = array("Co");                    // A CO Person Source is attached to one CO
    
    var $hasOne = array("CoInvite" =>                 // A person can have one invite (per CO)
                        array('dependent' => true),
                        "Name" =>                     // A person can have one (preferred) name per CO
                        array('dependent' => true));  // This could change if Name became an MVPA    
    
    var $hasMany = array("CoGroupMember" =>           // A person can have one or more groups
                         array('dependent' => true),
                         "CoOrgIdentityLink" =>       // A person can have more than one org identity
                         array('dependent' => true),
                         "CoPersonRole" =>            // A person can have one or more person roles
                         array('dependent' => true),
                         "CoPetitionApprover" =>
                         array('className' => 'CoPetition',
                               'dependent' => true,
                               'foreignKey' => 'approver_co_person_id'),
                         "CoPetitionEnrollee" =>
                         array('className' => 'CoPetition',
                               'dependent' => true,
                               'foreignKey' => 'enrollee_co_person_id'),
                         "CoPetitionPetitioner" =>
                         array('className' => 'CoPetition',
                               'dependent' => true,
                               'foreignKey' => 'petitioner_co_person_id'),
                         "CoPetitionSponsor" =>
                         array('className' => 'CoPetition',
                               'dependent' => true,
                               'foreignKey' => 'sponsor_co_person_id'),
                         "CoPetitionHistoryRecord" => // A person can be an actor on a petition and generate history
                         array('dependent' => true,
                               'foreignKey' => 'actor_co_person_id'),
                         "EmailAddress" =>            // A person can have one or more email address
                         array('dependent' => true),
                         "Identifier" =>              // A person can have many identifiers within a CO
                         array('dependent' => true));

    // Default display field for cake generated views
    var $displayField = "CoPerson.id";
    
    // Default ordering for find operations
    var $order = array("CoPerson.id");
    
    // Validation rules for table elements
    var $validate = array(
    );
    
    // Enum type hints
    
    var $cm_enum_types = array(
      'status' => 'status_t'
    );
    
    function orgIdIsCoPerson($coId, $orgIdentityId)
    {
      // Determine if an org identity is already associated with a CO.
      //
      // Parameters:
      // - coId: CO to check
      // - orgIdentityId: Org Identity to check
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      //     None
      //
      // Returns:
      // - true if $orgIdentityId is linked to $coId, false otherwise
      
      // Try to retrieve a link for this org identity id where the co person id
      // is a member of this CO
        
      $dbo = $this->getDataSource();
          
      $args['joins'][0]['table'] = $dbo->fullTableName($this->CoOrgIdentityLink);
      $args['joins'][0]['alias'] = 'CoOrgIdentityLink';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoOrgIdentityLink.co_person_id';
      $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $orgIdentityId;
      $args['conditions']['CoPerson.co_id'] = $coId;
      $args['contain'] = false;
      
      $link = $this->find('first', $args);
      
      if($link)
        return(true);
      
      return(false);
    }
  }
?>