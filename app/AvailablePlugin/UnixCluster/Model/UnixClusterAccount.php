<?php
/**
 * COmanage Registry Unix Cluster Account Model
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
 * @since         COmanage Registry v3.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
class UnixClusterAccount extends AppModel {
  // Define class name for cake
  public $name = "UnixClusterAccount";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "UnixCluster.UnixCluster",
    "CoPerson",
    "PrimaryCoGroup" => array(
      'className'  => 'CoGroup',
      'foreignKey' => 'primary_co_group_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "gecos";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order?
//  public $order = array("co_person_id");
  
  public $actsAs = array('Containable',
// XXX should we enable Provisioner?
//                         'Provisioner',
                         'Changelog' => array('priority' => 5));

  // Validation rules for table elements
  public $validate = array(
    'co_person_id' => array(
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
    ),
    'sync_mode' => array(
      'content' => array(
        'rule' => array('inList',
                        array(UnixClusterSyncEnum::Full,
                              UnixClusterSyncEnum::Manual)),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'status' => array(
      'content' => array(
        'rule' => array('inList', array(StatusEnum::Active,
                                        StatusEnum::Deleted,
                                        StatusEnum::Duplicate,
                                        StatusEnum::Expired,
                                        StatusEnum::GracePeriod,
                                        StatusEnum::PendingApproval,
                                        StatusEnum::Suspended)),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'username' => array(
      'content' => array(
        'rule' => array('maxLength', 256),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'uid' => array(
      'content' => array(
        // We really want a custom validator here, since range will allow
        // non-integers, and we probably also want to figure out what MAXUID is
        'rule' => array('range', 1, null),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'gecos' => array(
      'content' => array(
        'rule' => array('maxLength', 80),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'login_shell' => array(
      'content' => array(
        'rule' => array('inList', 
                        array(UnixShellEnum::Bash,
                              UnixShellEnum::Csh,
                              UnixShellEnum::Dash,
                              UnixShellEnum::Ksh,
                              UnixShellEnum::Sh,
                              UnixShellEnum::Tcsh,
                              UnixShellEnum::Zsh)),
        // The UnixCluster level default shell is used to auto-populate this
        // value, but we always expect an account-specific shell
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'home_directory' => array(
      'content' => array(
        'rule' => array('maxLength', 64),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'primary_co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'valid_from' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'valid_through' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.4.0
   * @throws InvalidArgumentException
   */

  public function beforeSave($options = array()) {
    if(!empty($this->data['UnixClusterAccount'])) {
      // The username and uid can not already be in use within this cluster
      
      $args = array();
      $args['conditions']['UnixClusterAccount.username'] = $this->data['UnixClusterAccount']['username'];
      $args['conditions']['UnixClusterAccount.unix_cluster_id'] = $this->data['UnixClusterAccount']['unix_cluster_id'];
      if(!empty($this->data['UnixClusterAccount']['id'])) {
        $args['conditions']['UnixClusterAccount.id <>'] = $this->data['UnixClusterAccount']['id'];
      }
      
      if($this->find('count', $args) > 0) {
        throw new InvalidArgumentException(_txt('er.unixcluster.ud.already', array(_txt('pl.unixcluster.fd.username'), $this->data['UnixClusterAccount']['username'])));
      }
      
      $args = array();
      $args['conditions']['UnixClusterAccount.uid'] = $this->data['UnixClusterAccount']['uid'];
      $args['conditions']['UnixClusterAccount.unix_cluster_id'] = $this->data['UnixClusterAccount']['unix_cluster_id'];
      if(!empty($this->data['UnixClusterAccount']['id'])) {
        $args['conditions']['UnixClusterAccount.id <>'] = $this->data['UnixClusterAccount']['id'];
      }
      
      if($this->find('count', $args) > 0) {
        throw new InvalidArgumentException(_txt('er.unixcluster.ud.already', array(_txt('pl.unixcluster.fd.uid'), $this->data['UnixClusterAccount']['uid'])));
      }
    }
    
    return true;
  }
  
  /**
   * Obtain the CO ID for a record.
   *
   * @since  COmanage Registry v3.4.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */
  
  public function findCoForRecord($id) {
    // We need to get the co id via the person, since it's a shorter path than
    // via the cluster
    
    $args = array();
    $args['conditions']['UnixClusterAccount.id'] = $id;
    $args['contain'][] = 'CoPerson';

    $a = $this->find('first', $args);

    if(!empty($a['CoPerson']['co_id'])) {
      return $a['CoPerson']['co_id'];
    }

    return parent::findCoForRecord($id);
  }
}
