<?php
/**
 * COmanage Registry Address Model
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

class Address extends AppModel {
  // Define class name for cake
  public $name = "Address";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Provisioner');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // An address may be attached to a CO person role
    "CoPersonRole",
    // An address may be attached to an Org identity
    "OrgIdentity"
  );
  
  // Default display field for cake generated views
  public $displayField = "line1";
  
  // Default ordering for find operations
//  public $order = array("line1");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    // Don't require any element since $belongsTo saves won't validate if they're empty
    'line1' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => false
      )
    ),
    'line2' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'locality' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'state' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'postal_code' => array(
      'content' => array(
        'rule' => array('maxLength', 16),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'country' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'type' => array(
      'content' => array(
        'rule' => array('inList', array(ContactEnum::Home,
                                        ContactEnum::Office,
                                        ContactEnum::Postal)),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'language' => array(
      'content' => array(
        'rule'       => array('validateLanguage'),
        'required'   => false,
        'allowEmpty' => true
      )
    ),
    'co_person_role_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'org_identity_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  // Enum type hints
  
  public $cm_enum_lang = array(
    'type' => 'en.contact.address'
  );
  
  public $cm_enum_types = array(
    'type' => 'contact_t'
  );
}