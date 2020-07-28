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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class Cluster extends AppModel {
  // Define class name for cake
  public $name = "Cluster";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
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
   * @since  COmanage Registry v3.3.0
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
   * Autogenerate Cluster Accounts for the specified CO Person.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Integer $coPersonId      CO Person ID
   * @param  Integer $actorCoPersonId Actor CO Person ID
   * @param  Array   $clusterIds      Cluster IDs to assign for, or null to assign accounts on all Clusters
   * @return Array Array of results, where the key is the Cluster description and the value is
   *               true if Account(s) were created, false if Account(s) already existed, or an error string
   * @throws InvalidArgumentException
   */
  
  public function assign($coPersonId, $actorCoPersonId, $clusterIds=null) {
    // Similar to Identifier::assign
    
    $ret = array();
    
    // $Map the $coPersonId to $coId
    $coId = $this->Co->CoPerson->field('co_id', array('CoPerson.id' => $coPersonId));
    
    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.co.unk'));
    }
    
    // Pull the list of configured clusters
    
    $args = array();
    $args['conditions']['Cluster.co_id'] = $coId;
    $args['conditions']['Cluster.status'] = SuspendableStatusEnum::Active;
    if(!is_null($clusterIds)) {
      // But only include these clusters, if specified
      $args['conditions']['Cluster.id'] = $clusterIds;
    }
    $args['contain'] = false;
    
    $clusters = $this->find('all', $args);
    
    foreach($clusters as $c) {
      // Call the plugin for each Cluster
      
      $plugin = $c['Cluster']['plugin'];
      
      try {
        $pluginModelName = $plugin . "." . $plugin;

        $pluginModel = ClassRegistry::init($pluginModelName);
        
        $ret[ $c['Cluster']['description'] ] = $pluginModel->assign($c, $coPersonId);
        
        if($ret[ $c['Cluster']['description'] ]) {
          // Create a history record
          $this->Co->CoPerson->HistoryRecord->record($coPersonId,
                                                     null,
                                                     null,
                                                     $actorCoPersonId,
                                                     ActionEnum::ClusterAccountAutoCreated,
                                                     _txt('rs.cluster.acct.ok', array($c['Cluster']['description'])));
        }
      }
      catch(Exception $e) {
        $ret[ $c['Cluster']['description'] ] = $e->getMessage();
      }
    }
    
// XXX do we need to maybe fire provisioning (as per Identifier::assign?)
// note enrollment flows will fire provisioning after this function is called

    return $ret;
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
   * @since  COmanage Registry v3.3.0
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