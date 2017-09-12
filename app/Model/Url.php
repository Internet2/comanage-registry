<?php
/**
 * COmanage Registry URL Model
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class Url extends AppModel {
  // Define class name for cake
  public $name = "Url";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Normalization' => array('priority' => 4),
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A URL may be attached to a CO Department
    "CoDepartment",
    // A URL may be attached to a CO Person
    "CoPerson",
    // A URL may be attached to an Org Identity
    "OrgIdentity",
    // A URL created from a Pipeline has a Source URL
    "SourceUrl" => array(
      'className' => 'Url',
      'foreignKey' => 'source_url_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "Url.url";
  
  // Default ordering for find operations
//  public $order = array("url");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'url' => array(
      'content' => array(
        'rule' => array('url', true),
        'required' => true,
        'allowEmpty' => false,
      ),
      'filter' => array(
        'rule' => array('validateInput',
                        array('filter' => FILTER_SANITIZE_URL))
      )
    ),
    'description' => array(
      'content' => array(
        'rule' => array('validateInput'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Url.type',
                              'default' => array(UrlEnum::Official,
                                                 UrlEnum::Personal))),
        'required' => false,
        'allowEmpty' => false
      )
    ),
    'co_person_id' => array(
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
    'source_url_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
}
