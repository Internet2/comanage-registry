<?php
/**
 * COmanage Registry CO Changelog Provisioner Target Model
 *
 * Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("CoProvisionerPluginTarget", "Model");

class CoChangelogProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoChangelogProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");
  
  public $hasMany = array("ChangelogProvisioner.CoChangelogProvisionerExport");
  
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
   * Determine the provisioning status of this target for a CO Person ID.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Provisioning Target ID
   * @param  Integer CO Person ID
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */
  
  public function status($coProvisioningTargetId, $coPersonId) {
    $ret = array(
      'status'    => ProvisioningStatusEnum::NotProvisioned,
      'timestamp' => null,
      'comment'   => ""
    );
    
    // Try to pull an existing record
    $args = array();
    $args['conditions']['CoChangelogProvisionerTarget.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['conditions']['CoChangelogProvisionerExport.co_person_id'] = $coPersonId;
    $export = $this->CoChangelogProvisionerExport->find('first', $args);
    
    if(!empty($export)) {
      $ret['status'] = ProvisioningStatusEnum::Provisioned;
      $ret['timestamp'] = $export['CoChangelogProvisionerExport']['exporttime'];
    }
    
    return $ret;
  }
  
  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array CO Person data
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function provision($coProvisioningTargetData, $op, $coPersonData) {
    // We pretty much ignore $op and always write a full record of $coPersonData.
    
    $changeLog = $coProvisioningTargetData['CoChangelogProvisionerTarget']['logfile'];
    
    $log = fopen($changeLog, "a");
    
    if(!$log) {
      throw new RuntimeException(_txt('er.changelogprovisioner.logfile', array($changeLog)));
    }
    
    // Get a lock on the file so we don't interleave output
    
    if(!flock($log, LOCK_EX)) {
      throw new RuntimeException(_txt('er.changelogprovisioner.logfile.lock', array($changeLog)));
    }
    
    fwrite($log, json_encode($coPersonData) . "\n");
    
    // Release the lock and close the file
    flock($log, LOCK_UN);
    fclose($log);
    
    // Update last export time
    $data = array();
    $data['CoChangelogProvisionerExport']['co_changelog_provisioner_target_id'] = $coProvisioningTargetData['CoChangelogProvisionerTarget']['id'];
    $data['CoChangelogProvisionerExport']['co_person_id'] = $coPersonData['CoPerson']['id'];
    $data['CoChangelogProvisionerExport']['exporttime'] = date('Y-m-d H:i:s');
    
    // See if we already have a row to update
    $args = array();
    $args['conditions']['CoChangelogProvisionerExport.co_changelog_provisioner_target_id'] = $coProvisioningTargetData['CoChangelogProvisionerTarget']['id'];
    $args['conditions']['CoChangelogProvisionerExport.co_person_id'] = $coPersonData['CoPerson']['id'];
    $args['contain'] = false;
    $export = $this->CoChangelogProvisionerExport->find('first', $args);
    
    if(!empty($export)) {
      $data['CoChangelogProvisionerExport']['id'] = $export['CoChangelogProvisionerExport']['id'];
    }
    
    if(!$this->CoChangelogProvisionerExport->save($data)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return true;
  }
}
