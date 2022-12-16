<?php
/**
 * COmanage Registry Elector Data Filter Precedence Model
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class ElectorDataFilterPrecedence extends AppModel
{
  // Define class name for cake
  public $name = "ElectorDataFilterPrecedence";

  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');

  // Association rules from this model to other models
  public $belongsTo = array(
    "ElectorDataFilter.ElectorDataFilter",
    "OrgIdentitySource"
  );

  // Default display field for cake generated views
  public $displayField = "inbound_attribute_type";

  // Validation rules for table elements
  public $validate = array(
    'org_identity_source_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
      )
    ),
    'inbound_attribute_type' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false, // XXX We disable presence validation since we will handle it manually in the beforeSave Callback
      'allowEmpty' => true,
    ),
    'elector_data_filter_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
  );

  /**
   * Actions to take after a save operation is executed.
   *
   * @since  COmanage Registry v4.1.0
   * @param  boolean $created True if a new record was created (rather than update)
   * @param  array   $options As passed into Model::save()
   */

  public function afterSave($created, $options = array()) {
    $this->_commit();

    return true;
  }

  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v4.1.0
   */

  public function beforeSave($options = array()) {
    // Start a transaction -- we'll commit in afterSave.

    $this->_begin();

    if(empty($this->data['ElectorDataFilterPrecedence']['ordr'])) {
      // Find the current high value and add one
      $n = 1;

      $args = array();
      $args['fields'][] = "MAX(ElectorDataFilterPrecedence.ordr) as m";
      $args['conditions']['ElectorDataFilterPrecedence.elector_data_filter_id'] = $this->data['ElectorDataFilterPrecedence']['elector_data_filter_id'];
      $args['order'][] = "m";

      $o = $this->find('first', $args);

      if(!empty($o[0]['m'])) {
        $n = $o[0]['m'] + 1;
      }

      $this->data['ElectorDataFilterPrecedence']['ordr'] = $n;
    }

    return true;
  }

  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v4.1.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */

  public function findCoForRecord($id) {
    $pre_rule = $this->findRecord($id);

    if(!empty($pre_rule["ElectorDataFilter"]["DataFilter"]["co_id"])) {
      return $pre_rule["ElectorDataFilter"]["DataFilter"]["co_id"];
    }

    return parent::findCoForRecord($id);
  }

  /**
   * Obtain the record.
   *
   * @since  COmanage Registry v4.1.0
   * @param  integer  Id to retrieve for
   * @return array    Corresponding Record and linked models
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */

  public function findRecord($id) {
    $args = array();
    $args['conditions']['ElectorDataFilterPrecedence.id'] = $id;
    $args['contain'] = array('ElectorDataFilter' => array('DataFilter'));

    return $this->find('first', $args);
  }
}