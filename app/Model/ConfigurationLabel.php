<?php
/**
 * COmanage Registry Configuration Label Model
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class ConfigurationLabel extends AppModel {
  // Define class name for cake
  public $name = 'ConfigurationLabel';
  
  // Current schema version for API
  public $version = '1.0';
  
  // Association rules from this model to other models
  public $belongsTo = array('Co');

  public $hasMany = array(
    "CoEnrollmentAttribute" => array('dependent' => true),
  );
  
  // Default display field for cake generated views
  public $displayField = 'label';

  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'label' => array(
      'content' => array(
        'rule' => array('validateLabel'),
        'required' => true,
        'allowEmpty' => false,
        'message' => array('Allowed characters are a-z0-9_-. Value must be at least 3 characters long.'),
        'last' => 'true',
      ),
      'size' => array(
        'rule' => array('maxlength', 64),
        'message' => array('Max length is 64 characters.'),
        'last' => 'true',
      ),
      'unique' => array(
        'rule' => array('isUniqueCust', 'label'),
        'message' => array('Label name already exists.'),
        'last' => 'true',
      ),
    ),
    'color' => array(
      'content' => array(
        'rule' => array('validateHex'),
        'required' => true,
        'allowEmpty' => false,
      ),
      'size' => array(
        'rule' => array('maxlength', 9),
        'message' => array('Max length is 9 characters.'),
        'last' => 'true',
      ),
      'unique' => array(
        'rule' => array('isUniqueCust', 'color'),
        'message' => array('Color value already exists.'),
        'last' => 'true',
      ),
    )
  );

  /**
   * Check if the label is Unique
   *
   * @param  array  $check  Array of fields to validate
   * @param  string $field  Field to check
   *
   * @return bool
   * @since  COmanage Registry v4.4.0
   */

  public function isUniqueCust($check, $field) {
    if (!is_string($check[$field])) {
      return false;
    }

    // Check if the label is unique. Since we enabled changelog we need to do it manually
    $args = array();
    $args['conditions']['ConfigurationLabel.' . $field] = $check[$field];
    $args['conditions']['ConfigurationLabel.co_id'] = $this->data['ConfigurationLabel']['co_id'];
    $args['contain'] = false;

    $labels = $this->find('all', $args);
    $labels_count = count($labels);
    $labelId = Hash::extract($labels, '{n}.ConfigurationLabel.id');

    // create
    if($labels_count > 0 && empty($this->data['ConfigurationLabel']['id'])) {
      return false;
    }
    // edit
    if(
      $labels_count > 0
      && !empty($this->data['ConfigurationLabel']['id'])
      && !in_array($this->data['ConfigurationLabel']['id'], $labelId)
    ) {
      return false;
    }

    return true;
  }

  /**
   * Validates whether a hexadecimal/plain color value is syntactically correct.
   *
   * @param array $check
   *   The hexadecimal string to validate. Must contain a leading '#'
   *
   * @return bool TRUE if $hex is valid or FALSE if it is not.
   * @since  COmanage Registry v4.4.0
 */
  public function validateHex($check) {
    if (!is_string($check['color'])) {
      return false;
    }
    return preg_match('/^(#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?)$/', $check['color']) === 1;
  }

  /**
   * Validates whether a label value is syntactically correct.
   *
   * @param array $check
   *
   * @return bool TRUE if label is valid or FALSE if it is not.
   * @since  COmanage Registry v4.4.0
   */
  public function validateLabel($check) {
    if (!is_string($check['label'])) {
      return false;
    }
    return preg_match('/^[a-z0-9_-]{3,}$/', $check['label']) === 1;
  }
}
