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
  public $belongsTo = array(
    "Co"
  );
    
  // Default display field for cake generated views
  public $displayField = "username";
  
  // Default ordering for find operations
  public $order = array("username");
  
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'username' => array(
      'content' => array(
        'rule' => array('maxLength', 50),
        'required' => true,
        'allowEmpty' => false,
        'message' => array('Username must not exceed 50 characters.'),
        'last' => 'true',
      ),
      'filter' => array(
        'rule' => array('validateInput'),
        'message' => array('Username contains invalid characters.'),
        'last' => 'true',
      )
    ),
    // This column will be renamed api_key in v5
    'password' => array(
      'rule' => 'notBlank',
      // API Key is set after initial save
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true
    ),
    'privileged' => array(
      'rule' => array('boolean')
    ),
    'valid_from' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      ),
      'precedes' => array(
        'rule' => array('validateTimestampRange', "valid_through", "<"),
      ),
    ),
    'valid_through' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      ),
      'follows' => array(
        'rule' => array("validateTimestampRange", "valid_from", ">"),
      ),
    ),
    'remote_ip' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
  );
  
  /**
   * Obtain a list of available API Users.
   *
   * @since  COmanage Registry v3.3.0
   * @param  int   $coId CO ID
   * @return array       Array of valid API users, as returned by find()
   */
  
  public function availableApiUsers($coId) {
    $args = array();
    $args['conditions']['ApiUser.co_id'] = $coId;
    // Don't return suspended users
    $args['conditions']['ApiUser.status'] = SuspendableStatusEnum::Active;
    // Or those with invalid dates
    $args['conditions']['AND'] = array(
      0 => array(
        'OR' => array(
          'ApiUser.valid_from IS NULL',
          'ApiUser.valid_from < ' => date('Y-m-d H:i:s', time())
        )
      ),
      1 => array(
        'OR' => array(
          'ApiUser.valid_through IS NULL',
          'ApiUser.valid_through > ' => date('Y-m-d H:i:s', time())
        )
      )
    );
    $args['order'] = 'ApiUser.username ASC';
    $args['fields'] = array('id', 'username');
    
    return $this->find('list', $args);
  }

  /**
   * Actions to take before a validate operation is executed.
   *
   * @since  COmanage Registry v3.3.0
   */

  public function beforeValidate($options = array())
  {
    if(!empty($this->data['ApiUser'])) {
      // The username must begin with "co_<co_id>.".
      $prefix = "co_" . $this->data['ApiUser']['co_id'] . ".";
      // Prepend the prefix to the username i got from post
      $this->data['ApiUser']['username'] = $prefix . $this->data['ApiUser']['username'];

      // Check if the username is unique. Since we enabled changelog we need to do it manually
      $args = array();
      $args['conditions']['ApiUser.username'] = $this->data['ApiUser']['username'];
      $args['contain'] = false;

      if($this->find('count', $args) > 0
         && empty($this->data['ApiUser']["id"])) {
        return false;
      }
    }

    return parent::beforeValidate($options);
  }

  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v0.8.4
   */
  
  public function beforeSave($options = array()) {
    // Possibly convert the requested timestamps to UTC from browser time.
    // Do this before the strtotime/time calls below, both of which use UTC.

    if($this->tz) {
      $localTZ = new DateTimeZone($this->tz);

      if(!empty($this->data['ApiUser']['valid_from'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data['ApiUser']['valid_from'], $localTZ);

        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data['ApiUser']['valid_from'] = strftime("%F %T", $offsetDT->getTimestamp());
      }

      if(!empty($this->data['ApiUser']['valid_through'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data['ApiUser']['valid_through'], $localTZ);

        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data['ApiUser']['valid_through'] = strftime("%F %T", $offsetDT->getTimestamp());
      }
    }
    
    return true;
  }
  
  /**
   * Generate an API Key.
   *
   * @since  COmanage Registry v3.3.0
   * @param  int    $id API User ID
   * @return string     API Key
   */
  
  public function generateKey($id) {
    $token = generateRandomToken();
    
    $passwordHasher = new SimplePasswordHasher();
    
    $this->id = $id;
    // Hash the password, per http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html
    // We don't want to trigger beforeSave
    $this->saveField('password', $passwordHasher->hash($token), array('callbacks' => false));
    
    return $token;
  }
}