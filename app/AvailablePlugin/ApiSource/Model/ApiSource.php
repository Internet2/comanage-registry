<?php
/**
 * COmanage Registry API OrgIdentitySource Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class ApiSource extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = array("orgidsource", "job");
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Request Kafka servers
  // XXX How will this work when we support other server types? Probably need to
  // make this an array, then the automagic code would populate an array in response.
  public $cmServerType = ServerEnum::KafkaServer;
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "ApiUser",
    "OrgIdentitySource",
    // KafkaServer is an actual model, this is an alias to Server
    "ServerKafka" => array(
      'className' => 'Server',
      'foreignKey' => 'kafka_server_id'
    )
  );
  
  public $hasMany = array(
    "ApiSourceRecord"
  );
  
  // Default display field for cake generated views
  public $displayField = "sor_label";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  // NOTE: We update these rules in beforeValidate()
  public $validate = array(
    'org_identity_source_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'An Org Identity Source ID must be provided'
    ),
    'sor_label' => array(
      'rule' => 'alphaNumeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'api_user_id' => array(
      'content' => array(
        'rule' => 'notBlank',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'kafka_server_id' => array(
      'content' => array(
        'rule' => 'notBlank',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'kafka_groupid' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'kafka_topic' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v3.3.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Obtain the list of jobs implemented by this plugin.
   *
   * @since COmanage Registry v4.0.0
   * @return Array Array of job names and help texts
   */
  
  public function getAvailableJobs() {
    return array(
      'Poll' => _txt('pl.apisource.job')
    );
  }
  
  /**
   * Actions to take after a validate operation is executed.
   *
   * @since  COmanage Registry v4.0.0
   */

  public function afterValidate($options = array()) {
    // We need to restore the original validation, otherwise FormHelper will
    // render required bits into the form. This would be desirable, except that
    // if the user changes the setting (eg: disables Poll Mode) the other
    // fields will remain required and (worse) also invisible (so the error
    // won't be visible).
    
    // Flip the required fields appropriately
    foreach(array('kafka_server_id', 'kafka_groupid', 'kafka_topic') as $f) {
      $this->validate[$f]['required'] = false;
      $this->validate[$f]['allowEmpty'] = true;
    }
    
    return true;
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.3.0
   */

  public function beforeSave($options = array()) {
    if(!empty($this->data['ApiSource']['sor_label'])) {
      // Make sure sor_label isn't already in use in this CO
      
      $coId = $this->OrgIdentitySource->field('co_id', array('OrgIdentitySource.id' => 
                                                             $this->data['ApiSource']['org_identity_source_id']));
      
      $args = array();
      $args['joins'][0]['table'] = 'org_identity_sources';
      $args['joins'][0]['alias'] = 'OrgIdentitySource';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'OrgIdentitySource.id=ApiSource.org_identity_source_id';
      $args['conditions']['OrgIdentitySource.co_id'] = $coId;
      $args['conditions']['ApiSource.sor_label'] = $this->data['ApiSource']['sor_label'];
      // We don't want to test against our own record
      $args['conditions']['ApiSource.id NOT'] = $this->data['ApiSource']['id'];
      $args['contain'] = false;
      
      $recs = $this->find('count', $args);
      
      if($recs > 0) {
        throw new InvalidArgumentException(_txt('er.apisource.label.inuse', array($this->data['ApiSource']['sor_label'])));
      }
    }
    
    return true;
  }
  
  /**
   * Actions to take before a validate operation is executed.
   *
   * @since  COmanage Registry v4.0.0
   */

  public function beforeValidate($options = array()) {
    // If updating beforeValidate(), be sure to also update afterValidate().
    
    if(!empty($this->data['ApiSource']['poll_mode'])
       && $this->data['ApiSource']['poll_mode'] == ApiSourcePollModeEnum::Kafka) {
      // Flip the required fields appropriately
      foreach(array('kafka_server_id', 'kafka_groupid', 'kafka_topic') as $f) {
        $this->validate[$f]['required'] = true;
        $this->validate[$f]['allowEmpty'] = false;
      }
    }
    
    return true;
  }
  
  /**
   * Poll for new messages.
   *
   * @since  COmanage Registry v4.0.0
   * @param  CoJob   $CoJob       CO Job object, with $id set
   * @param  integer $apiSourceId API Source ID
   * @param  integer $max         Maximum number of records to process
   * @throws RuntimeException
   */
  
  public function poll($CoJob, $apiSourceId, $max=100) {
    // Pull the API Source config
    $args = array();
    $args['conditions']['ApiSource.id'] = $apiSourceId;
    $args['contain'] = array('OrgIdentitySource');
    
    $cfg = $this->find('first', $args);
    
    if($cfg['OrgIdentitySource']['status'] != SuspendableStatusEnum::Active) {
      throw new RuntimeException(_txt('er.status.not', array(SuspendableStatusEnum::Active)));
    }
    
    if($cfg['ApiSource']['poll_mode'] != ApiSourcePollModeEnum::Kafka) {
      throw new RuntimeException('NOT IMPLEMENTED');
    }
    
    $CoJob->update($CoJob->id,
                   $apiSourceId,
                   $cfg['ApiSource']['poll_mode'],
                   _txt('pl.apisource.job.poll.start', array($cfg['ApiSource']['poll_mode'])));
    
    // Track results
    $success = 0;
    $failed = 0;
    
    // XXX This will need refactoring for technologies other than Kafka.
    $KafkaConsumer = $this->ServerKafka->KafkaServer->establishConsumer($cfg['ApiSource']['kafka_server_id'],
                                                                        $cfg['ApiSource']['kafka_groupid'],
                                                                        $cfg['ApiSource']['kafka_topic']);
    
    for($i = 0;$i < $max;$i++) {
      if($CoJob->canceled($CoJob->id)) { return false; }
      
      // Parameter is timeout in milliseconds
      $message = $KafkaConsumer->consume(5000);
      
      if($message->err == RD_KAFKA_RESP_ERR__PARTITION_EOF) {
        // Simply break, nothing else to do
        $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                           null,
                                           _txt('pl.apisource.job.poll.eof'));
        
        break;
      } elseif($message->err != RD_KAFKA_RESP_ERR_NO_ERROR) {
        // Throw an error that will bubble up the stack
        throw new RuntimeException($message->errstr());
      }
      
      // Process the record, which is available in $message->payload. On error,
      // we want to keep going (up to $max)
      try {
        // Parse JSON
        $json = json_decode($message->payload, true);
        
        if(empty($json)) {
          // XXX It might be useful to store/log $message->payload somewhere
          // for diagnostic purposes, but we don't really have a good place to
          // store it, so for now we'll just log it.
          $this->log(_txt('er.apisource.kafka.json', array($message->offset)));
          $this->log($message->payload);
          throw new Exception(_txt('er.apisource.kafka.json', array($message->offset)));
        }
        
        // Check metadata
        foreach(array('resource' => 'sorPersonRole',
                      'version' => '1',
                      'sor' => $cfg['ApiSource']['sor_label'])
                as $a => $v) {
          if(empty($json['meta'][$a]) || $json['meta'][$a] != $v) {
            throw new Exception(_txt('er.apisource.kafka.meta', array($a,
                                                                      $message->offset,
                                                                      !empty($json['meta'][$a]) ? $json['meta'][$a] : "",
                                                                      $v)));
          }
        }
        
        // Find SORID
        if(empty($json['meta']['sorid'])) {
          throw new Exception(_txt('er.apisource.kafka.sorid', array($message->offset)));
        }
        
        $r = $this->upsert($cfg['ApiSource']['id'], 
                           $cfg['ApiSource']['org_identity_source_id'], 
                           $cfg['OrgIdentitySource']['co_id'],
                           $json['meta']['sorid'], 
                           $json);
        
        $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                           $json['meta']['sorid'],
                                           _txt('pl.apisource.job.poll.msg', array($message->offset)),
                                           null,
                                           $r['org_identity_id']);
        
        $success++;
      }
      catch(Exception $e) {
        $CoJob->CoJobHistoryRecord->record($CoJob->id,
                                           !empty($json['meta']['sorid']) ? $json['meta']['sorid'] : null,
                                           $e->getMessage(),
                                           null,
                                           null,
                                           JobStatusEnum::Failed);
        
        $failed++;
      }
      
      if($max > 0) {
        $pctDone = (($i + 1) * 100)/$max;
        $CoJob->setPercentComplete($CoJob->id, $pctDone);
      }
    }
    
    $CoJob->finish($CoJob->id,
                   _txt('pl.apisource.job.poll.finish', array(($success + $failed), $success, $failed)));
  }
  
  /**
   * Insert or update an ApiSource Record and associated Org Identity.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $id    ApiSource ID
   * @param  boolean $oisId Org Identity Source ID
   * @param  integer $coId  CO ID
   * @param  string  $sorid System of Record ID
   * @param  array   $json  Array, from json_decode
   * @return array          Array with keys 'org_identity_id' and 'new' (indicating if the OrgIdentity was newly created)
   */
  
  public function upsert($id, $oisId, $coId, $sorid, $json) {
    $args = array();
    $args['conditions']['ApiSourceRecord.api_source_id'] = $id;
    $args['conditions']['ApiSourceRecord.sorid'] = $sorid;
    $args['contain'] = false;
    
    $currec = $this->ApiSourceRecord->find('first', $args);
    
    $rec = array(
      'api_source_id' => $id,
      'sorid' => $sorid,
      // For consistency, we'll always make the source_record pretty (which
      // should also make it slightly easier for an admin to look at it.
      'source_record' => json_encode($json, JSON_PRETTY_PRINT)
    );
    
    $this->ApiSourceRecord->clear();
    
    if(!empty($currec)) {
      // Update, though it's possible an update for us still needs to trigger a
      // createOrgIdentity() call...
      
      $rec['id'] = $currec['ApiSourceRecord']['id'];
    } else {
      // Insert
    }
    
    // Save the new or updated record
    // We currently don't bother diff'ing the record to see if it changed, but we could
    $this->ApiSourceRecord->save($rec);

    // Do we already have an OIS Record for this sorid?
    $args = array();
    $args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $oisId;
    $args['conditions']['OrgIdentitySourceRecord.sorid'] = $sorid;
    $args['contain'] = false;
    
    $curoisrec = $this->OrgIdentitySource->OrgIdentitySourceRecord->find('first', $args);
    
    $orgId = null;
    
    if(!empty($curoisrec)) {
      // Update
      
      $info = $this->OrgIdentitySource->syncOrgIdentity($oisId, $sorid);
      
      return array(
        'org_identity_id' => $info['id'],
        'new' => false
      );
    } else {
      // Create
      
      $newOrgId = $this->OrgIdentitySource->createOrgIdentity($oisId, $sorid, null, $coId);
      
      return array(
        'org_identity_id' => $newOrgId,
        'new' => true
      );
    }
  }
}
