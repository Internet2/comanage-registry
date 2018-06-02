<?php
/**
 * COmanage Registry CO Dashboard Widget Model
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoDashboardWidget extends AppModel {
  // Define class name for cake
  public $name = "CoDashboardWidget";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoDashboard"
  );
  
  // Default display field for cake generated views
  public $displayField = "CoDashboardWidget.description";
  
  // Default ordering for find operations
  public $order = array("CoDashboardWidget.ordr");
  
  // Validation rules for table elements
  public $validate = array(
    'co_dashboard_id' => array(
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
   * @since  COmanage Registry v3.2.0
   * @param  boolean $created True if a new record was created (rather than update)
   * @param  array   $options As passed into Model::save()
   */

  public function afterSave($created, $options = array()) {
    if($created) {
      // We prefix a "Co" since this is a CO oriented plugin, but maybe the naming
      // should be cleaned up as part of v4.0.0. (CO-1593)
      $modelName = "Co" . $this->data['CoDashboardWidget']['plugin'];

      $target = array();
      $target[$modelName]['co_dashboard_widget_id'] = $this->data['CoDashboardWidget']['id'];

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
   * @since  COmanage Registry v3.2.0
   */

  public function beforeSave($options = array()) {
    // Start a transaction -- we'll commit in afterSave.
    // This is primarily for add(), since we want to create the plugin's record.

    $this->_begin();
    
    if(!empty($this->data['CoDashboardWidget']['co_dashboard_id'])
       && empty($this->data['CoDashboardWidget']['ordr'])) {
      // Find the current high value and add one
      $n = 1;

      $args = array();
      $args['fields'][] = "MAX(ordr) as m";
      $args['conditions']['CoDashboardWidget.co_dashboard_id'] = $this->data['CoDashboardWidget']['co_dashboard_id'];
      $args['order'][] = "m";

      $o = $this->find('first', $args);

      if(!empty($o[0]['m'])) {
        $n = $o[0]['m'] + 1;
      }

      $this->data['CoDashboardWidget']['ordr'] = $n;
    }

    return true;
  }
  
  /**
   * Obtain the CO ID for a record, overriding AppModel behavior.
   *
   * @since  COmanage Registry v3.2.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */
  
  public function findCoForRecord($id) {    
    // Dashboard Widgets will refer to a Dashboards
    
    $args = array();
    $args['conditions'][$this->alias.'.id'] = $id;
    $args['contain'][] = 'CoDashboard';
    
    $codw = $this->find('first', $args);
    
    if(!empty($codw['CoDashboard']['co_id'])) {
      return $codw['CoDashboard']['co_id'];
    } else {
      return parent::findCoForRecord($id);
    }
  }
}