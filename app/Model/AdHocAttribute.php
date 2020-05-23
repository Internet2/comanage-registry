<?php
/**
 * COmanage Registry Ad Hoc Attribute Model
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class AdHocAttribute extends AppModel {
  // Define class name for cake
  public $name = "AdHocAttribute";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // An Ad Hoc Attribute may be attached to a CO Department
    "CoDepartment",
    // A Ad Hoc Attribute may be attached to a CO Person Role
    "CoPersonRole",
    // A Ad Hoc Attribute may be attached to an Org Identity
    "OrgIdentity",
    // A Ad Hoc Attribute created from a Pipeline has a Source URL
    "SourceAdHocAttribute" => array(
      'className' => 'AdHocAttribute',
      'foreignKey' => 'source_ad_hoc_attribute_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "AdHocAttribute.tag";
  
  // Default ordering for find operations
//  public $order = array("tag");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'tag' => array(
      'content' => array(
        'rule' => array('validateInput'),
        'required' => true,
        'allowEmpty' => false,
      )
    ),
    'value' => array(
      'content' => array(
        'rule' => array('validateInput'),
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
    ),
    'co_department_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'source_ad_hoc_attribute_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
}
