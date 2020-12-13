<?php
/**
 * COmanage Registry Kafka Server Model
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoHttpClient', 'Lib');

class KafkaServer extends AppModel {
  // Define class name for cake
  public $name = "KafkaServer";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("Server");
  
  // Default display field for cake generated views
  public $displayField = "brokers";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'server_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'brokers' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    // For the next two, we could define enums, but there isn't much value in
    // localizing these strings. Maybe define them that way when it makes more sense.
    'security_protocol' => array(
      'rule' => array('inList', array('plaintext', 'ssl', 'ssl_plaintext', 'sasl_ssl')),
      'required' => true,
      'allowEmpty' => false
    ),
    'sasl_mechanism' => array(
      'rule' => array('inList', array('GSSAPI', 'PLAIN', 'SCRAM-SHA-256', 'SCRAM-SHA-512', 'OAUTHBEARER')),
      'required' => false,
      'allowEmpty' => true
    ),
    'username' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'password' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v4.0.0
   * @return Boolean
   */
  /* We don't implement a connection test here because doing so requires
   * spinning up a consumer or producer, which requires more specific configuration
   * information than we track here.
   *
  public function beforeSave($options = array()) {
  }*/
  
  /**
   * Establish a Kafka Consumer.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $serverId Server ID
   * @param  string  $groupid  Kafka Group ID
   * @param  string  $topic    Kafka Topic to subscribe to
   * @return RdKafkaConsumer   Kafka Consumer
   */
  
  public function establishConsumer($serverId, $groupid, $topic) {
    $args = array();
    $args['conditions']['Server.id'] = $serverId;
    $args['contain'] = array('KafkaServer');
    
    $srvr = $this->Server->find('first', $args);
    
    if(!$srvr) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.servers.1', $serverId))));
    }
    
    // https://github.com/edenhill/librdkafka/blob/master/CONFIGURATION.md
    $conf = new RdKafka\Conf();
    
    $conf->set('security.protocol', $srvr['KafkaServer']['security_protocol']);
    $conf->set('sasl.mechanism', $srvr['KafkaServer']['sasl_mechanism']);
    $conf->set('sasl.username', $srvr['KafkaServer']['username']);
    $conf->set('sasl.password', $srvr['KafkaServer']['password']);
    
    // Configure the group.id. All consumer with the same group.id will consume
    // different partitions.
    $conf->set('group.id', $groupid);
    
    // Initial list of Kafka brokers
    $conf->set('metadata.broker.list', $srvr['KafkaServer']['brokers']);
    
    // Set where to start consuming messages when there is no initial offset in
    // offset store or the desired offset is out of range.
    // 'smallest': start from the beginning
    $conf->set('auto.offset.reset', 'smallest');
    
    $consumer = new RdKafka\KafkaConsumer($conf);
    
    $consumer->subscribe(array($topic));
    
    return $consumer;
  }
}