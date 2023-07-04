<?php
/**
 * COmanage Registry CO Normalizer Entry Model
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoNormalizer extends AppModel {
  // Define class name for cake
  // XXX We need to use the name CoNormarlizer instead of Normalizer due to namespaces
  public $name = "CoNormalizer";

  // Current schema version for API
  public $version = "1.0";

  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));

  // Association rules from this model to other models
  public $belongsTo = array(
    'Co',
  );

  // Default display field for cake generated views
  public $displayField = "description";

  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'plugin' => array(
      'rule' => 'notBlank',
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


  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v4.3.0
   */

  public function beforeSave($options = array()) {
    // Start a transaction -- we'll commit in afterSave.

    $this->_begin();

    if(empty($this->data['CoNormalizer']['ordr'])) {
      // Find the current high value and add one
      $n = 1;

      $args = array();
      $args['fields'][] = "MAX(Normalizer.ordr) as m";
      $args['conditions']['CoNormalizer.co_normalizer_id'] = $this->data['CoNormalizer']['co_normalizer_id'];
      $args['order'][] = "m";

      $o = $this->find('first', $args);

      if(!empty($o[0]['m'])) {
        $n = $o[0]['m'] + 1;
      }

      $this->data['CoNormalizer']['ordr'] = $n;
    }

    return true;
  }

  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v4.3.0
   * @param  Boolean $created True if new model is saved (ie: add)
   * @param  Array $options Options, as based to model::save()
   * @return Boolean True on success
   */

  public function afterSave($created, $options = Array()) {
    if($created) {
      // Create an instance of the plugin source, if it is flagged
      // as instantiable.

      $pluginName = $this->data['CoNormalizer']['plugin'];
      $modelName = $pluginName;
      $pluginModelName = $pluginName . "." . $modelName;

      $pmodel = ClassRegistry::init($pluginModelName);

      // See if this plugin requires instantiation
      if($pmodel->cmPluginInstantiate) {
        $validator = array();
        $validator[$modelName]['normalizer_id'] = $this->id;

        // Note that we have to disable validation because we want to create an empty row.
        if(!$pmodel->save($validator, false)) {
          return false;
        }
      }
    }

    return true;
  }
}

