<?php
/**
 * COmanage Registry Unix Cluster Group Model
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
  
class UnixClusterGroup extends AppModel {
  // Define class name for cake
  public $name = "UnixClusterGroup";
  
  // Current schema version for API
  public $version = "1.0";
  
  public $permittedApiFilters = array(
    'unix_cluster_id' => 'UnixCluster.UnixCluster'
  );
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "UnixCluster.UnixCluster",
    "CoGroup"
  );
  
  // Default display field for cake generated views
  public $displayField = "co_group_id";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order?
//  public $order = array("co_person_id");
  
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));

  // Validation rules for table elements
  public $validate = array(
    'co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true
      )
    ),
    'unix_cluster_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true
      )
    )
  );
  
  /**
   * Pull the list of available Cluster groups.
   *
   * @since  COmanage Registry v3.3.0
   * @param  int   $unixClusterId Unix Cluster ID
   * @return array                Array of CoGroup IDs and Names
   */
  
  public function availableUnixGroups($unixClusterId) {
    $args = array();
    $args['conditions']['UnixClusterGroup.unix_cluster_id'] = $unixClusterId;
    $args['contain'] = array('CoGroup' => array('conditions' => array('CoGroup.status' => SuspendableStatusEnum::Active)));
    
    $clusterGroups = $this->UnixCluster->UnixClusterGroup->find('all', $args);
    $groups = array();
    
    foreach($clusterGroups as $cg) {
      if(!empty($cg['CoGroup']['id'])) {
        $groups[ $cg['CoGroup']['id'] ] = $cg['CoGroup']['name'];
      }
    }
    
    return $groups;
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.3.0
   * @throws InvalidArgumentException
   */

  public function beforeSave($options = array()) {
    if(!empty($this->data['UnixClusterGroup'])) {
      // The group cannot already be attached to the unix cluster
      $args = array();
      $args['conditions']['UnixClusterGroup.co_group_id'] = $this->data['UnixClusterGroup']['co_group_id'];
      $args['conditions']['UnixClusterGroup.unix_cluster_id'] = $this->data['UnixClusterGroup']['unix_cluster_id'];
      if(!empty($this->data['UnixClusterGroup']['id'])) {
        $args['conditions']['UnixClusterGroup.id <>'] = $this->data['UnixClusterGroup']['id'];
      }

      if($this->find('count', $args) > 0) {
        throw new InvalidArgumentException(_txt('er.unixcluster.group.already'));
      }
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
    // We need to get the co id via the group, since it's a shorter path than
    // via the cluster
    
    $args = array();
    $args['conditions']['UnixClusterGroup.id'] = $id;
    $args['contain'][] = 'CoGroup';

    $gr = $this->find('first', $args);

    if(!empty($gr['CoGroup']['co_id'])) {
      return $gr['CoGroup']['co_id'];
    }

    return parent::findCoForRecord($id);
  }
}
