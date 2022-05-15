<?php
/**
 * COmanage Registry Vetting Result Model
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

class VettingResult extends AppModel {
  // Define class name for cake
  public $name = "VettingResult";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "VettingRequest",
    "VettingStep",
    "VetterCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'vetter_co_person_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "comment";
  
  // Validation rules for table elements
  public $validate = array(
    'vetting_request_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'vetting_step_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'vetter_co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array(
        'inList',
        array(
          VettingStatusEnum::Canceled,
          VettingStatusEnum::Failed,
          VettingStatusEnum::Passed,
          VettingStatusEnum::PendingManual,
          VettingStatusEnum::PendingResult,
          VettingStatusEnum::Requested
        )
      ),
      'required' => true,
      'allowEmpty' => false
    ),
    'comment' => array(
      'rule' => array('maxLength', 256),
      'required' => false,
      'allowEmpty' => true
    ),
    'raw' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Obtain the CO ID for a record, overriding AppModel behavior.
   *
   * @since  COmanage Registry v4.1.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function findCoForRecord($id) {
    // Vetting Results will refer to a Vetting Step

    $args = array();
    $args['conditions'][$this->alias.'.id'] = $id;
    $args['contain'][] = 'VettingStep';

    $vr = $this->find('first', $args);

    if(!empty($vr['VettingStep']['co_id'])) {
      return $vr['VettingStep']['co_id'];
    } else {
      return parent::findCoForRecord($id);
    }
  }
}