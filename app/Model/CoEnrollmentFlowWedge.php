<?php
/**
 * COmanage Registry CO Enrollment Flow Wedge Model
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

class CoEnrollmentFlowWedge extends AppModel {
  // Define class name for cake
  public $name = "CoEnrollmentFlowWedge";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoEnrollmentFlow"
  );
  
  // Default display field for cake generated views
  public $displayField = "CoEnrollmentFlowWedgedescription";
  
  // Default ordering for find operations
  public $order = array("CoEnrollmentFlowWedge.ordr");
  
  // Validation rules for table elements
  public $validate = array(
    'co_enrollment_flow_id' => array(
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
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'allowEmpty' => false
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  public $_targetid = null;
  
  /**
   * Actions to take after a save operation is executed.
   *
   * @since  COmanage Registry v4.0.0
   * @param  boolean $created True if a new record was created (rather than update)
   * @param  array   $options As passed into Model::save()
   */

  public function afterSave($created, $options = array()) {
    if($created) {
      $modelName = $this->data['CoEnrollmentFlowWedge']['plugin'];

      $target = array();
      $target[$modelName]['co_enrollment_flow_wedge_id'] = $this->data['CoEnrollmentFlowWedge']['id'];

      // We need to disable validation since we want to create an empty row
      if(!$this->$modelName->save($target, false)) {
        $this->_rollback();

        return;
      }
      
      $this->_targetid = $this->$modelName->id;
    }
    
    $this->_commit();

    return;
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v4.0.0
   */

  public function beforeSave($options = array()) {
    // Start a transaction -- we'll commit in afterSave.
    // This is primarily for add(), since we want to create the plugin's record.

    $this->_begin();
    
    if(!empty($this->data['CoEnrollmentFlowWedge']['co_enrollment_flow_id'])
       && empty($this->data['CoEnrollmentFlowWedge']['ordr'])) {
      // Find the current high value and add one
      $n = 1;

      $args = array();
      $args['fields'][] = "MAX(ordr) as m";
      $args['conditions']['CoEnrollmentFlowWedge.co_enrollment_flow_id'] = $this->data['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
      $args['order'][] = "m";

      $o = $this->find('first', $args);

      if(!empty($o[0]['m'])) {
        $n = $o[0]['m'] + 1;
      }

      $this->data['CoEnrollmentFlowWedge']['ordr'] = $n;
    }

    return true;
  }
  
  /**
   * Obtain the CO ID for a record, overriding AppModel behavior.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */
  
  public function findCoForRecord($id) {    
    // Enrollment Flow Wedges will refer to a Enrollment Flows
    
    $args = array();
    $args['conditions'][$this->alias.'.id'] = $id;
    $args['contain'][] = 'CoEnrollmentFlow';
    
    $coefw = $this->find('first', $args);
    
    if(!empty($coefw['CoEnrollmentFlow']['co_id'])) {
      return $coefw['CoEnrollmentFlow']['co_id'];
    } else {
      return parent::findCoForRecord($id);
    }
  }
}