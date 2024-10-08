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

  public $hasMany = array(
    // An API User can have many history records
    'HistoryRecord' => array(
      'foreignKey' => 'actor_api_user_id',
      'dependent' => true
    ),
  );

  // Associated models that should be relinked to the archived attribute during Changelog archiving
  public $relinkToArchive = array('HistoryRecord');
    
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
      ),
      'unique' => array(
        'rule' => array('isUsernameUnique'),
        'message' => array('Username already exists.'),
        'last' => 'true',
      ),
      'prefix' => array(
        'rule' => array('checkPrefix'),
        'message' => array('Prefix invalid.'),
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
    if(is_array($this->data) && !empty($this->data['ApiUser'])) {
      // The username must begin with "co_<co_id>.".
      $prefix = 'co_' . $this->data['ApiUser']['co_id'] . '.';

      // Prepend the prefix to the username if the prefix is not found at the beginning of the string,
      // which means that either the 'strops' will return false or an integer greater than 0
      // The UI will strip the prefix before sending.
      // Nevertheless, when done through the command line, e.g.,
      // the Configuration Export plugin, we will get the full username.
      $position = strpos($this->data['ApiUser']['username'], $prefix);
      if($position === false || $position > 0 ) {
        $this->data['ApiUser']['username'] = $prefix . $this->data['ApiUser']['username'];
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

        // date converts a timestamp according to server localtime which is UTC
        $this->data['ApiUser']['valid_from'] = date("Y-m-d H:i:s", $offsetDT->getTimestamp());
      }

      if(!empty($this->data['ApiUser']['valid_through'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data['ApiUser']['valid_through'], $localTZ);

        // date converts a timestamp according to server localtime which is UTC
        $this->data['ApiUser']['valid_through'] = date("Y-m-d H:i:s", $offsetDT->getTimestamp());
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

  /**
   * Check if the username is Unique
   *
   * @param  array  $check  Array of fields to validate
   *
   * @return bool
   * @since  COmanage Registry v4.4.0
   */

  public function isUsernameUnique($check) {
    if (!is_string($check['username'])) {
      return false;
    }

    // Check if the username is unique. Since we enabled changelog we need to do it manually
    $args = array();
    $args['conditions']['ApiUser.username'] = $check['username'];
    $args['conditions']['ApiUser.co_id'] = $this->data['ApiUser']['co_id'];
    $args['contain'] = false;

    $users = $this->find('all', $args);
    $users_count = count($users);
    $userId = Hash::extract($users, '{n}.ApiUser.id');

    // create
    if($users_count > 0 && empty($this->data['ApiUser']['id'])) {
      return false;
    }
    // edit
    if(
      $users_count > 0
      && !empty($this->data['ApiUser']['id'])
      && !in_array($this->data['ApiUser']['id'], $userId)
    ) {
      return false;
    }

    return true;
  }

  /**
   * Check the username prefix
   *
   * @param  array  $check  Array of fields to validate
   *
   * @return bool
   * @since  COmanage Registry v4.4.0
   */

  public function checkPrefix($check) {
    // The username must begin with "co_<co_id>.".
    $prefix = 'co_' . $this->data['ApiUser']['co_id'] . '.';
    $position = strpos($check['username'], $prefix);
    return !($position === false || $position > 0 );
  }
}