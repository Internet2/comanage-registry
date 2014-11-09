<?php
/**
 * COmanage Registry CO Person Role Model
 *
 * Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
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
  
  // Add behaviors
  public $actsAs = array('Containable', 'Provisioner');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A CO Org Person To is attached to one COU
    "Cou",
    "CoPerson"=> array(
      'className' => 'CoPerson',
      'foreignKey' => 'co_person_id'
    ),
    // A CO Org Person To is attached to one CO Person    
    "SponsorCoPerson" => array(
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
    // It's probably not right to delete history records, but generally CO person roles shouldn't be deleted
    "HistoryRecord" => array('dependent' => true),
    // A person can have one or more telephone numbers
    "TelephoneNumber" => array('dependent' => true)
  );

  // Default display field for cake generated views
  public $displayField = "CoPersonRole.id";
  
// XXX CO-296 Toss default order?
  // Default ordering for find operations
  //  public $order = array("CoPersonRole.id");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'co_person_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => true,
        'message' => 'A CO Person ID must be provided'
      )
    ),
    'cou_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'title' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'o' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'ou' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'valid_from' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'valid_through' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'status' => array(
      'content' => array(
        'rule' => array('inList', array(StatusEnum::Active,
                                        StatusEnum::Approved,
                                        StatusEnum::Declined,
                                        StatusEnum::Deleted,
                                        StatusEnum::Denied,
                                        StatusEnum::Duplicate,
                                        StatusEnum::Expired,
                                        StatusEnum::GracePeriod,
                                        StatusEnum::Invited,
                                        StatusEnum::Pending,
                                        StatusEnum::PendingApproval,
                                        StatusEnum::PendingConfirmation,
                                        StatusEnum::Suspended))
      )
    ),
    'sponsor_co_person_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'affiliation' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'CoPersonRole.affiliation',
                              'default' => array(AffiliationEnum::Faculty,
                                                 AffiliationEnum::Student,
                                                 AffiliationEnum::Staff,
                                                 AffiliationEnum::Alum,
                                                 AffiliationEnum::Member,
                                                 AffiliationEnum::Affiliate,
                                                 AffiliationEnum::Employee,
                                                 AffiliationEnum::LibraryWalkIn))),
        'required' => true,
        'allowEmpty' => false
      )
    )
  );
  
  // Enum type hints
  
  public $cm_enum_txt = array(
// Don't use this anymore due to extended types
//    'affiliation' => 'en.co_person_role.affiliation',
    'status' => 'en.status'
  );
  
  public $cm_enum_types = array(
    'affiliation' => 'affil_t',
    'status' => 'status_t'
  );
}
