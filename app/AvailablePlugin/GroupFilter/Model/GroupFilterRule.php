<?php
/**
 * COmanage Registry Group DataFilter Rule Model
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class GroupFilterRule extends AppModel {
  // Define class name for cake
  public $name = "GroupFilterRule";
  
  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("GroupFilter.GroupFilter");
  
  // Default display field for cake generated views
  public $displayField = "name_pattern";
  
  // Validation rules for table elements
  public $validate = array(
    'group_filter_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'name_pattern' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'required' => array(
      'rule' => array('range', -2, 2),
      'required' => true,
      'allowEmpty' => false
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

    if(!empty($this->data['GroupFilterRule']['group_filter_id'])
       && empty($this->data['GroupFilterRule']['ordr'])) {
      // Find the current high value and add one
      $n = 1;

      $args = array();
      $args['fields'][] = "MAX(GroupFilterRule.ordr) as m";
      $args['conditions']['GroupFilterRule.group_filter_id'] = $this->data['GroupFilterRule']['group_filter_id'];
      $args['order'][] = "m";

      $o = $this->find('first', $args);

      if(!empty($o[0]['m'])) {
        $n = $o[0]['m'] + 1;
      }

      $this->data['GroupFilterRule']['ordr'] = $n;
    }

    return true;
  }
  
  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function findCoForRecord($id) {
    // We need to get the co id via the parent Filter

    $args = array();
    $args['conditions']['GroupFilterRule.id'] = $id;
    $args['contain'] = array('GroupFilter');
    
    $rule = $this->find('first', $args);
    
    if(empty($rule)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.group_filter_rules.1'), $id)));
    }
    
    // Relations aren't autobinding DataFilter...
    $DataFilter = ClassRegistry::init('DataFilter');
    
    $coId = $DataFilter->field('co_id', array('DataFilter.id' => $rule['GroupFilter']['data_filter_id']));

    if($coId) {
      return $coId;
    }
    
    return parent::findCoForRecord($id);
  }
}
