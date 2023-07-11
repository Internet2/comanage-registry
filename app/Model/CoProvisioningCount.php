<?php
/**
 * COmanage Registry CO Provisioning Count Model
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
 * @since         COmanage Registry v4.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoProvisioningCount extends AppModel {
  // Define class name for cake
  public $name = "CoProvisioningCount";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoProvisioningTarget",
  );
  
  // Default display field for cake generated views
  public $displayField = "co_provisioning_count_id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_job_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'provisioning_count' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => false,
        'allowEmpty' => true,
      ),
      'filter' => array(
        'rule' => array('comparison', '>', 0),
        'message' => 'Must be greater than 0(zero)'
      )
    )
  );
  
  /**
   * Obtain the current retry count for the specified provisioning target and job.
   *
   * @since  COmanage Registry v4.2.0
   * @param  Integer $coProvisioningTargetId CO Provisioning Target ID
   * @param  Integer $co_job_id              Job Id
   * @return Integer Current count
   */
  
  public function count($coProvisioningTargetId, $co_job_id) {
    // We don't currently try to validate either foreign key.
    $args = array();
    $args['conditions']['CoProvisioningCount.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['conditions']['CoProvisioningCount.co_job_id'] = $co_job_id;
    $args['contain'] = false;

    $ret = $this->field('provisioning_count', $args['conditions']);
   
    return (integer)($ret ?? 0);
  }
  
  /**
   * Increment the current provisioning count for the specified provisioning target and job.
   *
   * @since  COmanage Registry v4.2.0
   * @param  Integer $co_job_id              Job Id
   * @param  Integer $coProvisioningTargetId CO Provisioning Target ID
   * @param  Integer $parent_co_job_id       Parent Job Id
   * @return Integer New count
   */

  public function increment($co_job_id, $parent_co_job_id, $coProvisioningTargetId) {
    $ret = 0;
    
    // Is there already a count?
    
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    // We don't currently try to validate either foreign key.
    $args = array();
    $args['conditions']['CoProvisioningCount.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['conditions']['CoProvisioningCount.co_job_id'] = $parent_co_job_id;
    
    $count = $this->findForUpdate($args['conditions'], array('id', 'provisioning_count'));
    
    $this->clear();
    
    if(!empty($count)) {
      $ret = $count[0]['CoProvisioningCount']['provisioning_count'] + 1;
      
      $this->id = $count[0]['CoProvisioningCount']['id'];
      $this->saveField('provisioning_count', $ret);
      $this->saveField('co_job_id', $co_job_id);
    } else {
      $ret = 1;
      
      $count = array(
        'co_provisioning_target_id' => $coProvisioningTargetId,
        'co_job_id'                 => $co_job_id,
        'provisioning_count'        => $ret
      );
      
      if(!$this->save($count)) {
        throw new RuntimeException(_txt('er.db.save-a', array(_txt('er.prov.job.count'))));
      }
    }
    
    $dbc->commit();
    
    return $ret;
  }

  /**
   * Reset the current provisioning count for the specified provisioning target and job.
   *
   * @since  COmanage Registry v4.2.0
   * @param  Integer $co_job_id              Job Id
   * @param  Integer $parent_co_job_id       Parent Job Id
   * @param  Integer $coProvisioningTargetId CO Provisioning Target ID
   */

  public function reset($co_job_id, $parent_co_job_id, $coProvisioningTargetId) {
    $dbc = $this->getDataSource();
    $dbc->begin();

    // We don't currently try to validate either foreign key.
    $args = array();
    $args['conditions']['CoProvisioningCount.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['conditions']['CoProvisioningCount.co_job_id'] = $parent_co_job_id;

    $count = $this->findForUpdate($args['conditions'], array('id', 'provisioning_count'));

    if(empty($count)) {
      // No record found. Just return
      return;
    }

    $this->clear();

    $count = array(
      'co_provisioning_target_id' => $coProvisioningTargetId,
      'co_job_id'                 => $co_job_id,
      'provisioning_count'        => 0
    );

    if(!$this->save($count)) {
      throw new RuntimeException(_txt('er.db.save-a', array(_txt('er.prov.job.count'))));
    }

    $dbc->commit();
  }
}
