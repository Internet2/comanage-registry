<?php
/**
 * COmanage Registry Identifier Enroller Identifier Model
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

class IdentifierEnrollerIdentifier extends AppModel {
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array("IdentifierEnroller.IdentifierEnroller");
  
  // Default display field for cake generated views
  public $displayField = "label";
  
  // Validation rules for table elements
  public $validate = array(
    'identifier_enroller_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'label' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'identifier_type' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'default_env' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );

  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */

  public function findCoForRecord($id) {
    // It's a long walk to the CO...
    
    $args = array();
    $args['conditions']['IdentifierEnrollerIdentifier.id'] = $id;
    $args['contain'] = array(
      'IdentifierEnroller' => array(
        'CoEnrollmentFlowWedge' => array(
          'CoEnrollmentFlow'
        )
      )
    );

    $ef = $this->find('first', $args);

    if(!empty($ef['IdentifierEnroller']['CoEnrollmentFlowWedge']['CoEnrollmentFlow']['co_id'])) {
      return $ef['IdentifierEnroller']['CoEnrollmentFlowWedge']['CoEnrollmentFlow']['co_id'];
    }

    return parent::findCoForRecord($id);
  }
}
