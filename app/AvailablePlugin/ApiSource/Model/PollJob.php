<?php
/**
 * COmanage Registry API Source Poll Job Model
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoJobBackend", "Model");
App::uses("CoJob", "Model");
class PollJob extends CoJobBackend {

  protected $CoJob = null;

  // Validation rules for table elements
  public $validate = array();
  
   /**
   * Execute the requested Job.
   *
   * @since  COmanage Registry v4.0.0
   * @param  int   $coId    CO ID
   * @param  CoJob $CoJob   CO Job Object, id available at $CoJob->id
   * @param  array $params  Array of parameters, as requested via parameterFormat()
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */


  public function execute($coId, $CoJob, $params) {
    
    $this->CoJob = new CoJob();

    // For Kafka integrations, we'll usually just want to poll the Partition ID configured
    // in the KafkaServer settings. However, for experimental parallel processing (CO-2696)
    // the administrator needs to be able to specify the Partition ID in order to have the
    // queue processed concurrently by multiple consumers.

    $kafkaPartitionID = (isset($params['kafka_partition_id']) ? (int)$params['kafka_partition_id'] : null);
    
    $existingKafkaPartitionJobId = $this->runningKafkaPartitionJobExists($params['coid'], $params['api_source_id'], $kafkaPartitionID);
    if (isset($existingKafkaPartitionJobId)) {
      if(isset($CoJob->id)) {
        $this->CoJob->finish($CoJob->id, 
          _txt('pl.apisource.job.existing.kafka.partition', array($existingKafkaPartitionJobId, $params['api_source_id'], $kafkaPartitionID)),
          JobStatusEnum::Canceled);
      }
      return;
    }
    
    $ApiSource = ClassRegistry::init("ApiSource.ApiSource");

    // Note we don't verify that api_source_id is in $coId. (We basically
    // ignore $coId.) Whatever queued the job should enforce that.

    $ApiSource->poll($CoJob, $params['api_source_id'], $params['max'], $kafkaPartitionID);
  }

  /**
   * Obtain the list of parameters supported by this Job.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Array of supported parameters.
   */

  public function parameterFormat() {
    $params = array(
      'api_source_id' => array(
        'help'     => _txt('pl.apisource.job.poll.id'),
        'type'     => 'int',
        'required' => true
      ),
      'kafka_partition_id' => array(
        'help'     => _txt('pl.apisource.job.poll.kafka_partition_id'),
        'type'     => 'int',
        'required' => false
      ),
      'max' => array(
        'help'     => _txt('pl.apisource.job.poll.max'),
        'type'     => 'int',
        'required' => false,
        'default'  => 10
      )
    );

    return $params;
  }

  /**
   * Check for running kafka partition poll job
   *     
   * @return Boolean
   */

   protected function runningKafkaPartitionJobExists($coid, $apiSourceId, $kafkaPartitionId) {

    $runningJobId = null;
    
    if(!isset($kafkaPartitionId) || !isset($apiSourceId))      
        return $runningJobId;

    $args = array();
    $args['conditions']['CoJob.co_id'] = $coid;
    $args['conditions']['CoJob.job_mode'] = ApiSourcePollModeEnum::Kafka;
    $args['conditions']['CoJob.status'] = array(JobStatusEnum::Queued, JobStatusEnum::InProgress);
    $args['conditions'][] = 'CoJob.job_params IS NOT NULL';
   
    $existingApiSourceKafkaPartitionJobs = $this->CoJob->find('all', $args);
    
    foreach($existingApiSourceKafkaPartitionJobs as $j) {

        $jp  = json_decode($j['CoJob']['job_params'], true);

        if($apiSourceId == (int)$jp['api_source_id'] && $kafkaPartitionId == (int)$jp['kafka_partition_id']) {
          $runningJobId = (int)$j['CoJob']['id'];
          break;
        }
    }

    return $runningJobId;
  }
  
}
