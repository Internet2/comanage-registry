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
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'username' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
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
      )
    ),
    'valid_through' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'remote_ip' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v0.8.4
   */
  
  public function beforeSave($options = array()) {
    // Pull the current record (if any) and the CO's name
    $current = null;
    $coName = null;
    
    if(!empty($this->data['ApiUser']['id'])) {
      $args = array();
      $args['conditions']['ApiUser.id'] = $this->data['ApiUser']['id'];
      $args['contain'] = false;
      
      $current = $this->find('first', $args);
    }
    
    $coName = $this->Co->field('Co.name', array('id' => $this->data['ApiUser']['co_id']));
    $prefix = $coName . ".";
    
    if(empty($current['ApiUser']['username'])
       || $current['ApiUser']['username'] != $this->data['ApiUser']['username']) {
      // The username must begin with "coname.".
      
      if(strncmp($this->data['ApiUser']['username'], $prefix, strlen($prefix))) {
        throw new InvalidArgumentException(_txt('er.api.username.prefix', array($prefix)));
      }

      // Check that there's something after the dot
      if(strlen($this->data['ApiUser']['username']) == strlen($prefix)) {
        throw new InvalidArgumentException(_txt('er.api.username.prefix', array($prefix)));
      }
      
      // The username must not already exist (unless we're editing that record).
      // Note we do not need to check against cm_identifiers since web auth and API auth
      // use different mechanisms. (CO-104)
      
      $args = array();
      $args['conditions']['ApiUser.username'] = $this->data['ApiUser']['username'];
      $args['contain'] = false;
      
      $inUse = $this->find('count', $args);
      
      if($inUse > 0) {
        throw new InvalidArgumentException(_txt('er.ia.exists',
                                                array(filter_var($this->data['ApiUser']['username'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }
    
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