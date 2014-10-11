<?php
/**
 * COmanage Registry Names Model
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

class Name extends AppModel {
  // Define class name for cake
  public $name = "Name";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Provisioner');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A name is attached to a CO Person
    "CoPerson",
    // A name is attached to an Org Identity
    "OrgIdentity"
  );
  
  // Default display field for cake generated views
  public $displayField = "family";
  
  // Default ordering for find operations
  public $order = array(
    "family",
    "given"
  );
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'honorific' => array(
      'content' => array(
        'rule' => array('maxLength', 32),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'given' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => true,
        'allowEmpty' => false,
        'message' => 'A given name must be provided'
      )
    ),
    'middle' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'family' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'suffix' => array(
      'content' => array(
        'rule' => array('maxLength', 32),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Name.type',
                              'default' => array(NameEnum::Alternate,
                                                 NameEnum::Author,
                                                 NameEnum::FKA,
                                                 NameEnum::Official,
                                                 NameEnum::Preferred))),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'language' => array(
      'content' => array(
        'rule'       => array('validateLanguage'),
        'required'   => false,
        'allowEmpty' => true
      )
    ),
    'primary_name' => array(
      'content' => array(
        'rule'       => array('boolean'),
        'required'   => false,
        'allowEmpty' => true
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
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'type' => 'name_t'
  );
  
  /**
   * Actions to take before a validate operation is executed.
   *
   * @since  COmanage Registry v0.9.1
   */
  
  public function beforeValidate($options = array()) {
    // Update validation rules according to CO Settings, but only for records attached
    // to a CO Person
    
    if(!empty($this->data['Name']['co_person_id'])) {
      // Map to the CO ID
      
      $args = array();
      $args['conditions']['CoPerson.id'] = $this->data['Name']['co_person_id'];
      $args['contain'] = false;
      
      $cop = $this->CoPerson->find('first', $args);
      
      if($cop) {
        $fields = $this->CoPerson->Co->CoSetting->getRequiredNameFields($cop['CoPerson']['co_id']);
        
        foreach($fields as $f) {
          // Make this field required
          $this->validator()->getField($f)->getRule('content')->required = true;
          $this->validator()->getField($f)->getRule('content')->allowEmpty = false;
          $this->validator()->getField($f)->getRule('content')->message = _txt('fd.required');
        }
      } else {
        // If for some reason we can't find the CO, fall back to the defaults
      }
    }
    
    return true;
  }
}