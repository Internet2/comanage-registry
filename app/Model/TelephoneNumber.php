<?php
/**
 * COmanage Registry Telephone Number Model
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class TelephoneNumber extends AppModel {
  // Define class name for cake
  public $name = "TelephoneNumber";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Normalization', 'Provisioner');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A telephone number may be attached to a CO Person Role
    "CoPersonRole",
    // A telephone number may be attached to an Org Identity
    "OrgIdentity"
  );
  
  // Default display field for cake generated views
  public $displayField = "number";
  
  // Default ordering for find operations
  public $order = array("number");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    // Don't require number or type since $belongsTo saves won't validate if they're empty
    'number' => array(
      'content' => array(
        'rule' => array('maxLength', 64),   // cake has telephone number validation, but US only
        'required' => false,                // We allow any chars to cover things like "ext 2009"
        'allowEmpty' => false
      )
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'TelephoneNumber.type',
                              'default' => array(ContactEnum::Fax,
                                                 ContactEnum::Home,
                                                 ContactEnum::Mobile,
                                                 ContactEnum::Office))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_person_role_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => false
      )
    ),
    'org_identity_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => false
      )
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'type' => 'contact_t'
  );
}
