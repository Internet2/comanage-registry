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
    ),
    'groupid' => array(
      'content' => array(
        'rule' => array('validateInput'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'topic' => array(
      'content' => array(
        'rule' => array('validateInput'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'batch_size' => array(
      'content' => array(
        'rule' => array('range', 1, null),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'partition' => array(
      'content' => array(
        'rule' => array('range', 0, null),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'timeout' => array(
      'content' => array(
        'rule' => array('range', 1, null),
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  // The server configuration
  protected $srvr = null;

  // Kafka Consumer
  protected $consumer = null;

  // Kafka Topic
  protected $topic = null;

  // Poll Error Collection
  protected $pollErrors = array();

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
   * Close the consumer.
   * 
   * @since  COmanage Registry v4.1.0
   */

  public function closeConsumer() {
    if($this->consumer) {
      // No close() call in low level consumer
      // $this->consumer->close();
    }

    $this->srvr = null;
    $this->consumer = null;
    $this->topic = null;
  }

  /**
   * Consume a batch of messages from the active consumer/topic.
   * 
   * @since  COmanage Registry v4.1.0
   */

  public function consumeBatch() {
    if($this->topic == null) 
      return array();
    
    $consumed = $this->topic->consumeBatch($this->srvr['KafkaServer']['partition'],
                                      $this->srvr['KafkaServer']['timeout'] * 1000,
                                      $this->srvr['KafkaServer']['batch_size']);
    /*
    * poll timeout fixed at 1 second
    */
    $this->consumer->poll(1000);
    
    /*
    * separator fixed at semicolon
    */
    if(!empty($this->pollErrors)){
      throw new Exception(implode("; ", $this->pollErrors));
    }

    return $consumed;
  }

  /**
   * Establish a Kafka Consumer.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $serverId Server ID
   * @return bool              true on success
   */
  
  public function establishConsumer($serverId) {
    $args = array();
    $args['conditions']['Server.id'] = $serverId;
    $args['contain'] = array('KafkaServer');
    
    $this->srvr = $this->Server->find('first', $args);
    
    if(!$this->srvr) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.servers.1', $serverId))));
    }
    
    // https://github.com/edenhill/librdkafka/blob/master/CONFIGURATION.md
    $conf = new RdKafka\Conf();
    
// XXX note in initial testing security_protocol was ALL_CAPS but is now lowercase (as per the documentation)
    $conf->set('security.protocol', $this->srvr['KafkaServer']['security_protocol']);
    $conf->set('sasl.mechanism', $this->srvr['KafkaServer']['sasl_mechanism']);
    $conf->set('sasl.username', $this->srvr['KafkaServer']['username']);
    $conf->set('sasl.password', $this->srvr['KafkaServer']['password']);

    $conf->setErrorCb(function (RdKafka\Consumer $kafka, int $err, string $reason){
      $this->pollErrors[] = $reason;
    });

    // Configure the group.id. All consumer with the same group.id will consume
    // different partitions.
    $conf->set('group.id', $this->srvr['KafkaServer']['groupid']);
    
    $this->consumer = new RdKafka\Consumer($conf);

    // Add the configured set of Kafka brokers
    $this->consumer->addBrokers($this->srvr['KafkaServer']['brokers']);

    // Configuration for the topic
    $topicConf = new RdKafka\TopicConf();

    // Set where to start consuming messages when there is no initial offset in
    // offset store or the desired offset is out of range.
    // 'earliest': start from the beginning
    $topicConf->set('auto.offset.reset', 'earliest');

    $this->topic = $this->consumer->newTopic($this->srvr['KafkaServer']['topic'], $topicConf);

    $this->topic->consumeStart($this->srvr['KafkaServer']['partition'], RD_KAFKA_OFFSET_STORED);

    return true;
  }
}