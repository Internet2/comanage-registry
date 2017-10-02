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
    // An email address may be attached to a CO Department
    "CoDepartment",
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
    ),
    'co_department_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $coId CO ID to constrain search to
   * @param  String  $q    String to search for
   * @return Array Array of search results, as from find('all)
   */
  
  public function search($coId, $q) {
    $args = array();
    $args['joins'][0]['table'] = 'co_person_roles';
    $args['joins'][0]['alias'] = 'CoPersonRole';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPersonRole.id=TelephoneNumber.co_person_role_id';
    $args['joins'][1]['table'] = 'co_people';
    $args['joins'][1]['alias'] = 'CoPerson';
    $args['joins'][1]['type'] = 'INNER';
    $args['joins'][1]['conditions'][0] = 'CoPerson.id=CoPersonRole.co_person_id';
    $args['conditions']['TelephoneNumber.number'] = $q;
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['order'] = array('TelephoneNumber.number');
    $args['contain'] = false;
    
    return $this->find('all', $args);
  }
}
