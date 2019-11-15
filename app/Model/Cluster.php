<?php
/**
 * COmanage Registry Cluster Model
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
 * @since         COmanage Registry v3.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class Cluster extends AppModel {
  // Define class name for cake
  public $name = "Cluster";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co"
  );
  
  public $hasManyPlugins = array(
    "cluster" => array(
      'coreModelFormat' => '%s'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'plugin' => array(
      // XXX This should be a dynamically generated list based on available plugins
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'status' => array(
      'rule' => array(
        'inList',
        array(
          SuspendableStatusEnum::Active,
          SuspendableStatusEnum::Suspended
        )
      ),
      'required' => true
    )
  );
  
  public $_targetid = null;
  
  /**
   * Actions to take after a save operation is executed.
   *
   * @since  COmanage Registry v3.4.0
   * @param  boolean $created True if a new record was created (rather than update)
   * @param  array   $options As passed into Model::save()
   */
  
  public function afterSave($created, $options = array()) {
    if($created) {
      $modelName = $this->data['Cluster']['plugin'];
      
      $target = array();
      $target[$modelName]['cluster_id'] = $this->data['Cluster']['id'];
      
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
   * @since  COmanage Registry v3.1.0
   */
  
  public function beforeSave($options = array()) {
    // Start a transaction -- we'll commit in afterSave.
    // This is primarily for add(), since we want to create the plugin's record.

    $this->_begin();
    
    return true;
  }
  
  /**
   * Obtain Cluster status for a CO Person.
   *
   * @since  COmanage Registry v3.4.0
   * @param  integer $id				      Cluster ID
   * @param  integer $coPersonId      CO Person ID
	 * @return Array Array with values
	 * 							 comment: Human readable string, visible to the CO Person
   */
  
  public function status($id, $coPersonId) {
    $ret = array(
			'comment' => _txt('fd.set.not')
		);
    
    $args = array();
    $args['conditions']['Cluster.id'] = $id;
    
    $status = $this->find('first', $args);
    
    // See what the backend has to say
    
    if(!empty($status['Cluster']['plugin'])) {
      if($status['Cluster']['status'] != SuspendableStatusEnum::Active) {
        $ret['comment'] = _txt('er.perm.status',
                               array('en.status.susp', null, $status['Cluster']['status']));
        
        return $ret;
      }
      
      $plugin = $status['Cluster']['plugin'];
      
      $args = array();
      $args['conditions'][$plugin.'.cluster_id'] = $id;
      $args['contain'] = false;
      
      $pcfg = $this->$plugin->find('first', $args);
      
      if(!empty($status['Cluster'])) {
        // Merge in the parent config that we already have
        $pcfg['Cluster'] = $status['Cluster'];
      }
      
      $this->$plugin->setConfig($pcfg);
      $ret = $this->$plugin->status($coPersonId);
    }
    
    return $ret;
  }
}