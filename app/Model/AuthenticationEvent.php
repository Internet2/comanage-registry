<?php
/**
 * COmanage Registry Authentication Event Model
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class AuthenticationEvent extends AppModel {
  // Define class name for cake
  public $name = "AuthenticationEvent";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Default display field for cake generated views
  public $displayField = "authenticated_identifier";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'authenticated_identifier' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'authentication_event' => array(
      'rule' => array('inList',
                      array(AuthenticationEventEnum::ApiLogin,
                            AuthenticationEventEnum::RegistryLogin)),
      'required' => true,
      'allowEmpty' => false
    ),
    'remote_ip' => array(
      'rule' => 'ip',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Obtain the last login information for an identifier.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String $identifier Identifier
   * @return Array AuthenticationEvent record
   */
  
  public function lastlogin($identifier) {
    $args = array();
    $args['conditions']['AuthenticationEvent.authenticated_identifier'] = $identifier;
    $args['order'] = array('AuthenticationEvent.id' => 'desc');
    $args['limit'] = 1;
    $args['contain'] = false;
    
    return $this->find('first', $args);
  }
  
  /**
   * Record an authentication event.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String                  $identifier Authenticated identifier
   * @param  AuthenticationEventEnum $eventType  AuthenticationEventEnum
   * @param  String                  $remoteIp   Remote IP address, if known
   * @throws RuntimeException
   */
  
  public function record($identifier, $eventType, $remoteIp=null) {
    $recordData = array();
    $recordData['AuthenticationEvent']['authenticated_identifier'] = $identifier;
    $recordData['AuthenticationEvent']['authentication_event'] = $eventType;
    if($remoteIp) {
      $recordData['AuthenticationEvent']['remote_ip'] = $remoteIp;
    }
    
    $this->create();
    
    if(!$this->save($recordData)) {
      throw new RuntimeException(_txt('er.db.save-a', array('AuthenticationEvent')));
    }
  }
}
