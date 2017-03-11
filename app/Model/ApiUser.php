<?php
/**
 * COmanage Registry ApiUser Model
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
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('SimplePasswordHasher', 'Controller/Component/Auth');

class ApiUser extends AppModel {
  // Define class name for cake
  public $name = "ApiUser";
  
  // Association rules from this model to other models
  //public $hasOne = array("User");      // An API user has an associated User
  
  // Default display field for cake generated views
  public $displayField = "username";
  
  // Default ordering for find operations
  public $order = array("username");
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'username' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false,
      'message' => 'A username must be provided'
    ),
    'password' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false,
      'message' => 'A password must be provided'
    )
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v0.8.4
   */
  
  public function beforeSave($options = array()) {
    // Hash the password, per http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html
    
    if(isset($this->data[$this->alias]['password'])) {
      $passwordHasher = new SimplePasswordHasher();
      $this->data[$this->alias]['password'] = $passwordHasher->hash($this->data[$this->alias]['password']);
    }
    
    return true;
  }
}