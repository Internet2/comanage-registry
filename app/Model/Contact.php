<?php
/**
 * COmanage Registry Contact Model
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class Contact extends AppModel {
  // Define class name for cake
  public $name = "Contact";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A Contact may be attached to a CO Department
    "CoDepartment",
    // A Contact may be attached to an Organization
    "Organization"
  );
  
  // Default display field for cake generated views
  public $displayField = "given";
  
  // Default ordering for find operations
//  public $order = array("given");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    // We don't require any specific fields here because this model is largely expected to be
    // populated automatically from external data (via OrganizationSource plugins), which may
    // not have any particular consistency. This might result in contacts with no data.
    'given' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true,
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'family' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'company' => array(
      'content' => array(
        'rule' => array('validateInput'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'number' => array(
      'content' => array(
        'rule' => array('maxLength', 64),   // We allow any chars to cover things like "ext 2009"
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'mail' => array(
      'content' => array(
        'rule' => array('email'),
        'required' => false,
        'allowEmpty' => true,
      ),
      'filter' => array(
        'rule' => array('validateInput',
                        array('filter' => FILTER_SANITIZE_EMAIL))
      )
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
          array('attribute' => 'Contact.type',
                'default' => array(
                  ContactTypeEnum::Administrative,
                  ContactTypeEnum::Billing,
                  ContactTypeEnum::Other,
                  ContactTypeEnum::Support,
                  ContactTypeEnum::Technical
                ))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_department_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'organization_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
}
