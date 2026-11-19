<?php
/**
 * COmanage Registry SLIP Identity Documnet Model
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class SlipIdentityDocument extends AppModel {
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "SlipPlugin.SlipProofing",
    "IdentityDocument"
  );

  // Default display field for cake generated views
  public $displayField = "id";
  
  // Validation rules for table elements
  public $validate = array(
    'slip_proofing_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'identity_document_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    )
  );

  /**
   * Obtain the CO ID for a record, overriding AppModel behavior.
   *
   * @since  COmanage Registry v4.7.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function findCoForRecord($id) {
    // SlipIdentityDocuments refer to SlipProofings which refer to SlipPlugins

    $args = array();
    $args['conditions'][$this->alias.'.id'] = $id;
    $args['contain'] = array('SlipProofing' => array('SlipPlugin'));

    $sid = $this->find('first', $args);

    if(!empty($sid['SlipProofing']['SlipPlugin']['co_id'])) {
      return $sid['SlipProofing']['SlipPlugin']['co_id'];
    } else {
      return parent::findCoForRecord($id);
    }
  }
}
