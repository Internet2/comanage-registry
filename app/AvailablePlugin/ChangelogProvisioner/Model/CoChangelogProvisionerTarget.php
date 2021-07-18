<?php
/**
 * COmanage Registry CO Changelog Provisioner Target Model
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
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoProvisionerPluginTarget", "Model");

class CoChangelogProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoChangelogProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");
  
  // Default display field for cake generated views
  public $displayField = "logfile";
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Provisioning Target ID must be provided'
    ),
    'logfile' => array(
      'rule' => array('custom', '/\/.*/'),
      'required' => true,
      'allowEmpty' => false,
      'message' => 'Please enter a valid file path'
    )
  );
  
  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    // We pretty much ignore $op and always write a full record of $provisioningData.
    
    $changeLog = $coProvisioningTargetData['CoChangelogProvisionerTarget']['logfile'];
    
    $log = fopen($changeLog, "a");
    
    if(!$log) {
      throw new RuntimeException(_txt('er.changelogprovisioner.logfile', array($changeLog)));
    }
    
    // Get a lock on the file so we don't interleave output
    
    if(!flock($log, LOCK_EX)) {
      throw new RuntimeException(_txt('er.changelogprovisioner.logfile.lock', array($changeLog)));
    }
    
    if(!empty($provisioningData)) {
      fwrite($log, json_encode($provisioningData) . "\n");
    }
    
    // Release the lock and close the file
    flock($log, LOCK_UN);
    fclose($log);
    
    return true;
  }
}
