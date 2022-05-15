<?php
/**
 * COmanage Registry Vetting Step Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class VettingStep extends AppModel {
  // Define class name for cake
  public $name = "VettingStep";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "VetterCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'vetter_co_group_id'
    )
  );
  
  public $hasMany = array(
    "VettingResult" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'plugin' => array(
      // XXX This should be a dynamically generated list based on available plugins
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'vetter_co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'status' => array(
      'rule' => array(
        'inList',
        array(
          SuspendableStatusEnum::Active,
          SuspendableStatusEnum::Suspended
        )
      ),
      'required' => true,
      'allowEmpty' => false
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'review_on_result' => array(
      'rule' => array(
        'inList',
        array(
          VettingStatusEnum::Passed,
          VettingStatusEnum::Failed,
          VettingStatusEnum::PendingManual
        )
      ),
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  public function beforeSave($options = array()) {
    if(!empty($this->data['VettingStep']['co_id'])
       && (empty($this->data['VettingStep']['ordr'])
           || $this->data['VettingStep']['ordr'] == '')) {
      // In order to deterministically execute provisioners, assign an order.
      // Find the current high value and add one
      $n = 1;
      
      $args = array();
      $args['fields'][] = "MAX(ordr) as m";
      $args['conditions']['VettingStep.co_id'] = $this->data['VettingStep']['co_id'];
      $args['order'][] = "m";
      
      $o = $this->find('first', $args);
      
      if(!empty($o[0]['m'])) {
        $n = $o[0]['m'] + 1;
      }
      
      $this->data['VettingStep']['ordr'] = $n;
    }
    
    return true;
  }
  
  /**
   * Resolve a pending result. This will requeue a new job if necessary
   *
   * @since  COmanage Registry v4.1.0
   * @param  int                $id               Vetting Step ID
   * @param  VettingStatusEnum  $pluginResult     Vetting Step Result
   * @param  int                $actorCoPersonId  Actor CO Person ID
   */
  
  public function resolve($id, $vettingRequestId, $pluginResult, $actorCoPersonId=null) {
    $vResult = array(
      'vetting_request_id'  => $vettingRequestId,
      'status'              => $pluginResult['result'],
      'vetting_step_id'     => $id,
      'vetter_co_person_id' => $actorCoPersonId
    );
    
    // Make sure $comment fits in the available space and doesn't have any
    // problematic characters
    $limit = $this->VettingResult->validate['comment']['rule'][1];
    $vResult['comment'] = substr(filter_var($pluginResult['comment'],FILTER_SANITIZE_SPECIAL_CHARS), 0, $limit);
    
    if(!empty($pluginResult['raw'])) {
      $vResult['raw'] = $pluginResult['raw'];
    }
    
    $this->VettingResult->clear();
    $this->VettingResult->save($vResult);
  }
  
  /**
   * Run the requested Vetting Step for the specified CO Person. Note this call
   * will create or update VettingResult as needed, but will NOT update the
   * VettingRequest itself.
   *
   * @since  COmanage Registry v4.1.0
   * @param  integer $id               Vetting Step ID
   * @param  integer $coPersonId       CO Person ID
   * @param  integer $vettingRequestId Vetting Request ID
   * @return VettingStatusEnum         Vetting Result
   */
  
  public function run($id, $coPersonId, $vettingRequestId) {
    // First pull this step
    $args = array();
    $args['conditions']['VettingStep.id'] = $id;
    $args['contain'] = false;
    
    $step = $this->find('first', $args);
    
    if(empty($step) 
       || $step['VettingStep']['status'] != SuspendableStatusEnum::Active
       || empty($step['VettingStep']['plugin'])) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.vetting_steps.1'), $id)));
    }
    
    // Instantiate the plugin and get a result
    $pluginModel = $step['VettingStep']['plugin'] . '.' . $step['VettingStep']['plugin'];
    
    $Plugin = ClassRegistry::init($pluginModel);
    
    try {
      $pluginResult = $Plugin->vet($id, $coPersonId);
    }
    catch(Exception $e) {
      $pluginResult = array(
        'comment' => $e->getMessage(),
        'result' => VettingStatusEnum::Error
      );
    }
    
    if(empty($pluginResult['result']) || empty($pluginResult['comment'])) {
      throw new RuntimeException(_txt('er.vetting.plugin'));
    }
    
    $this->resolve($id, $vettingRequestId, $pluginResult);
    
    // We don't create a HistoryRecord for each VettingStep since we're
    // effectively doing that in VettingResult.
    
    return $pluginResult;
  }
}