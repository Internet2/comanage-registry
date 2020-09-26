<?php
/**
 * COmanage Registry CO Department Model
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
  
class CoDepartment extends AppModel {
  // Define class name for cake
  public $name = "CoDepartment";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  // If CoDepartment ever actsAs Provisioner, Identifier::assign() will need to be updated
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "Cou",
    "AdministrativeCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'administrative_co_group_id'
    ),
    "LeadershipCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'leadership_co_group_id'
    ),
    "SupportCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'support_co_group_id'
    )
  );
  
  public $hasMany = array(
    "Address" => array('dependent' => true),
    "AdHocAttribute" => array('dependent' => true),
    "EmailAddress" => array('dependent' => true),
    "Identifier" => array('dependent' => true),
    "TelephoneNumber" => array('dependent' => true),
    "Url" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "name";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'cou_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'name' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
          array('attribute' => 'CoDepartment.type',
            'default' => array(DepartmentEnum::VO,
                               DepartmentEnum::ResearchInstitute,
                               DepartmentEnum::Department))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'leadership_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'administrative_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'support_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Actions to take after a save operation is executed.
   *
   * @since  COmanage Registry v3.3.0
   * @param  boolean $created True if a new record was created (rather than update)
   * @param  array   $options As passed into Model::save()
   */

  public function afterSave($created, $options = array()) {
    // Maybe assign identifiers, but only for new Groups
    if($created 
       && !empty($this->data['CoDepartment']['id'])) {
      $this->Identifier->assign('CoDepartment', $this->data['CoDepartment']['id'], null);
    }

    return true;
  }
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $coId CO ID to constrain search to
   * @param  String  $q    String to search for
   * @return Array Array of search results, as from find('all)
   */
  
  public function search($coId, $q) {
    // Tokenize $q on spaces
    $tokens = explode(" ", $q);
    
    $args = array();
    foreach($tokens as $t) {
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'LOWER(CoDepartment.name) LIKE' => '%' . strtolower($t) . '%'
        )
      );
    }
    $args['conditions']['CoDepartment.co_id'] = $coId;
    $args['order'] = array('CoDepartment.name');
    $args['contain'] = false;
    
    return $this->find('all', $args);
  }
}
