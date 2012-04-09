<?php
/**
 * COmanage Registry CO Person Role Model
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
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoPersonRole extends AppModel {
  // Define class name for cake
  public $name = "CoPersonRole";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A CO Org Person To is attached to one COU
    "Cou",
    "CoPerson"=> array(
      'className' => 'CoPerson',
      'foreignKey' => 'co_person_id'
    ),
    // A CO Org Person To is attached to one CO Person    
    "CoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'sponsor_co_person_id'
    )        // foreign key to sponsor
  );
  
  public $hasMany = array(
    // A person can have one or more address
    "Address" => array('dependent' => true),
    "CoPetition" => array(
      'dependent' => true,
      'foreignKey' => 'enrollee_co_person_role_id'
    ),
    // A person can have one or more telephone numbers
    "TelephoneNumber" => array('dependent' => true)
  );

  // Default display field for cake generated views
  public $displayField = "CoPersonRole.id";
  
  // Default ordering for find operations
  public $order = array("CoPersonRole.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_person_id' => array(
      'rule' => array('numeric'),
      'required' => true,
      'message' => 'A CO Person ID must be provided'
    ),
    'cou_id' => array(
      'rule' => array('numeric'),
      'required' => false,
      'allowEmpty' => true
    ),
    'title' => array(
      'rule' => array('maxLength', 128),
      'required' => false,
      'allowEmpty' => true
    ),
    'o' => array(
      'rule' => array('maxLength', 128),
      'required' => false,
      'allowEmpty' => true
    ),
    'ou' => array(
      'rule' => array('maxLength', 128),
      'required' => false,
      'allowEmpty' => true
    ),
    'valid_from' => array(
      'rule' => array('validateTimestamp'),
      'required' => false,
      'allowEmpty' => true
    ),
    'valid_through' => array(
      'rule' => array('validateTimestamp'),
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Active,
                                      StatusEnum::Approved,
                                      StatusEnum::Declined,
                                      StatusEnum::Deleted,
                                      StatusEnum::Denied,
                                      StatusEnum::Invited,
                                      StatusEnum::Pending,
                                      StatusEnum::PendingApproval,
                                      StatusEnum::Suspended))
    ),
    'sponsor_co_person_id' => array(
      'rule' => array('numeric'),
      'required' => false,
      'allowEmpty' => true
    ),
    'affiliation' => array(
      'rule' => array('inList', array(AffiliationEnum::Faculty,
                                      AffiliationEnum::Student,
                                      AffiliationEnum::Staff,
                                      AffiliationEnum::Alum,
                                      AffiliationEnum::Member,
                                      AffiliationEnum::Affiliate,
                                      AffiliationEnum::Employee,
                                      AffiliationEnum::LibraryWalkIn)),
      'required' => true
    )
  );
  
  // Enum type hints
  
  public $cm_enum_txt = array(
    'affiliation' => 'en.affil',
    'status' => 'en.status'
  );
  
  public $cm_enum_types = array(
    'affiliation' => 'affil_t',
    'status' => 'status_t'
  );
}
