<?php
/**
 * COmanage Registry CO Localization Model
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
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoLocalization extends AppModel {
  // Define class name for cake
  public $name = "CoLocalization";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");
  
  // Default display field for cake generated views
  public $displayField = "lkey";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false,
      'message' => 'A CO ID must be provided'
    ),
    'lkey' => array(
      'rule' => '/.*/',
      'required' => true,
      'allowEmpty' => false
    ),
    'language' => array(
      'rule' => '/.*/',
      'required' => true,
      'allowEmpty' => false
    ),
    'text' => array(
      'rule' => '/.*/',
      'required' => true,
      'allowEmpty' => false
    )
  );

  /**
   * Load language localizations
   *
   * @param int $coid CO Id
   */
  public function load($coid) {
    // Load dynamic texts. We do this here because lang.php doesn't have access to models yet.
    global $cm_lang, $cm_texts;

    $args = array();
    $args['joins'][0]['table'] = 'cos';
    $args['joins'][0]['alias'] = 'Co';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoLocalization.co_id=Co.id';
    $args['conditions']['Co.name'] = DEF_COMANAGE_CO_NAME;
    $args['conditions']['Co.status'] = StatusEnum::Active;
    $args['conditions']['CoLocalization.language'] = $cm_lang;
    $args['fields'] = array('CoLocalization.lkey', 'CoLocalization.text');
    $args['contain'] = false;

    $ls_cm = $this->find('list', $args);
    unset($args);

    // First load the Platform localization variables
    if(!empty($ls_cm)) {
      $cm_texts[$cm_lang] = array_merge($cm_texts[$cm_lang], $ls_cm);
    }

    $args = array();
    $args['conditions']['CoLocalization.co_id'] = $coid;
    $args['conditions']['CoLocalization.language'] = $cm_lang;
    $args['fields'] = array('CoLocalization.lkey', 'CoLocalization.text');
    $args['contain'] = false;

    $ls_co = $this->find('list', $args);

    // Replace all default texts with the ones configured in CO level
    if(!empty($ls_co)) {
      $cm_texts[$cm_lang] = array_merge($cm_texts[$cm_lang], $ls_co);
    }
  }
}
