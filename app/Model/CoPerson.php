<?php
/**
 * COmanage Registry CO Person Model
 *
 * Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoPerson extends AppModel {
  // Define class name for cake
  public $name = "CoPerson";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");                    // A CO Person Source is attached to one CO
  
  public $hasOne = array(
    // A person can have one invite (per CO)
    "CoInvite" => array('dependent' => true),
    // A person can have one (preferred) name per CO
    // This could change if Name became an MVPA    
    "Name" => array('dependent' => true)
  );
  
  public $hasMany = array(
    // A person can have one or more groups
    "CoGroupMember" => array('dependent' => true),
    // A person can have more than one org identity
    "CoOrgIdentityLink" => array('dependent' => true),
    // A person can have one or more person roles
    "CoPersonRole" => array('dependent' => true),
    "CoPetitionApprover" => array(
      'className' => 'CoPetition',
      'dependent' => true,
      'foreignKey' => 'approver_co_person_id'
    ),
    "CoPetitionEnrollee" => array(
      'className' => 'CoPetition',
      'dependent' => true,
      'foreignKey' => 'enrollee_co_person_id'
    ),
    "CoPetitionPetitioner" => array(
      'className' => 'CoPetition',
      'dependent' => true,
      'foreignKey' => 'petitioner_co_person_id'
    ),
    "CoPetitionSponsor" => array(
      'className' => 'CoPetition',
      'dependent' => true,
      'foreignKey' => 'sponsor_co_person_id'
    ),
    // A person can be an actor on a petition and generate history
    "CoPetitionHistoryRecord" => array(
      'dependent' => true,
      'foreignKey' => 'actor_co_person_id'
    ),
    // A person can have one or more email address
    "EmailAddress" => array('dependent' => true),
    // A person can have many identifiers within a CO
    "Identifier" => array('dependent' => true)
  );

  // Default display field for cake generated views
  public $displayField = "CoPerson.id";
  
  // Default ordering for find operations
  public $order = array("CoPerson.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Active,
                                      StatusEnum::Deleted,
                                      StatusEnum::Invited,
                                      StatusEnum::Pending,
                                      StatusEnum::Suspended,
                                      StatusEnum::Declined)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'status_t'
  );
  
  /**
   * Determine if an org identity is already associated with a CO.
   *
   * @since  COmanage Registry v0.3
   * @param  integer Identifier of CO
   * @param  integer Identifier of Org Identity
   * @return boolean true if $orgIdentityId is linked to $coId, false otherwise
   */
  
  public function orgIdIsCoPerson($coId, $orgIdentityId) {
    // Try to retrieve a link for this org identity id where the co person id
    // is a member of this CO
      
    $args['joins'][0]['table'] = 'co_org_identity_links';
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

  /**
   * Retrieve list of sponsors for display in dropdown.
   *
   * @since  COmanage Registry v0.3
   * @return Array Array with co_person id as keys and full name as values
   */
  
  public function sponsorList() {
    // Query database for people

    $args['contain'][] = 'Name';
    $args['order'][] = 'Name.family ASC';
    $nameData = $this->find('all', $args);

    // Make data human readable for dropdown, keyed by id
    foreach($nameData as $pers)
    {
      $drop[ $pers['CoPerson']['id'] ] = generateCn($pers['Name'], true);
    }
    return $drop;
  }
}
