<?php
/**
 * COmanage Registry CO Provisioning Target Filter Model
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoProvisioningTargetFilter extends AppModel {
  // Define class name for cake
  public $name = "CoProvisioningTargetFilter";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    'CoProvisioningTarget',
    'DataFilter'
  );
  
  public $hasMany = array(
  );
  
  // Default display field for cake generated views
  public $displayField = "ordr";
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'data_filter_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );

  /**
   * Actions to take after a save operation is executed.
   *
   * @since  COmanage Registry v3.3.0
   * @param  boolean $created True if a new record was created (rather than update)
   * @param  array   $options As passed into Model::save()
   */

  public function afterSave($created, $options = array()) {
    $this->_commit();

    return;
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.3.0
   */

  public function beforeSave($options = array()) {
    // Start a transaction -- we'll commit in afterSave.
    
    $this->_begin();

    if(!empty($this->data['CoProvisioningTargetFilter']['co_provisioning_target_id'])
       && empty($this->data['CoProvisioningTargetFilter']['ordr'])) {
      // Find the current high value and add one
      $n = 1;

      $args = array();
      $args['fields'][] = "MAX(CoProvisioningTargetFilter.ordr) as m";
      $args['conditions']['CoProvisioningTargetFilter.co_provisioning_target_id'] = $this->data['CoProvisioningTargetFilter']['co_provisioning_target_id'];
      $args['order'][] = "m";

      $o = $this->find('first', $args);

      if(!empty($o[0]['m'])) {
        $n = $o[0]['m'] + 1;
      }

      $this->data['CoProvisioningTargetFilter']['ordr'] = $n;
    }

    return true;
  }
}