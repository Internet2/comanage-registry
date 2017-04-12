<?php
/**
 * COmanage Registry Telephone Number Model
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class TelephoneNumber extends AppModel {
  // Define class name for cake
  public $name = "TelephoneNumber";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Normalization' => array('priority' => 4),
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A telephone number may be attached to a CO Person Role
    "CoPersonRole",
    // A telephone number may be attached to an Org Identity
    "OrgIdentity",
    // A telephone number created from a Pipeline has a Source Telephone Number
    "SourceTelephoneNumber" => array(
      'className' => 'TelephoneNumber',
      'foreignKey' => 'source_telephone_number_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "number";
  
  // Default ordering for find operations
//  public $order = array("number");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    // Don't require number or type since $belongsTo saves won't validate if they're empty
    'country_code' => array(
      'content' => array(
        'rule' => array('maxLength', 3),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'area_code' => array(
      'content' => array(
        'rule' => array('maxLength', 8),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'number' => array(
      'content' => array(
        'rule' => array('maxLength', 64),   // cake has telephone number validation, but US only
        'required' => false,                // We allow any chars to cover things like "ext 2009"
        'allowEmpty' => false
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'extension' => array(
      'content' => array(
        'rule' => array('maxLength', 16),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'TelephoneNumber.type',
                              'default' => array(ContactEnum::Campus,
                                                 ContactEnum::Fax,
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
}
