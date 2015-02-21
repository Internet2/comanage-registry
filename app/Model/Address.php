<?php
/**
 * COmanage Registry Address Model
 *
 * Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
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
  public $actsAs = array('Containable', 'Normalization', 'Provisioner');
  
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
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Address.type',
                              'default' => array(ContactEnum::Home,
                                                 ContactEnum::Office,
                                                 ContactEnum::Postal))),
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
  
  /**
   * Actions to take before a validate operation is executed.
   *
   * @since  COmanage Registry v0.9.1
   */
  
  public function beforeValidate($options = array()) {
    // Update validation rules according to CO Settings, but only for records attached
    // to a CO Person Role
    
    if(!empty($this->data['Address']['co_person_role_id'])) {
      // Map to the CO ID
      
      $args = array();
      $args['joins'][0]['table'] = 'cm_co_person_roles';
      $args['joins'][0]['alias'] = 'CoPersonRole';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoPersonRole.co_person_id';
      $args['conditions']['CoPersonRole.id'] = $this->data['Address']['co_person_role_id'];
      $args['contain'] = false;
      
      $cop = $this->CoPersonRole->CoPerson->find('first', $args);
      
      if($cop) {
        $fields = $this->CoPersonRole->CoPerson->Co->CoSetting->getRequiredAddressFields($cop['CoPerson']['co_id']);
        
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