<?php
/**
 * COmanage Registry CO Provisioner Plugin Target Parent Model
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

abstract class CoProvisionerPluginTarget extends AppModel {
  // Define class name for cake
  public $name = "CoProvisionerPluginTarget";
  
  /**
   * Determine the provisioning status of this target for a CO Person or CO Group.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Provisioning Target ID
   * @param  Integer CO Person ID (null if CO Group ID is specified)
   * @param  Integer CO Group ID (null if CO Person ID is specified)
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */
  
  public function status($coProvisioningTargetId, $coPersonId, $coGroupId=null) {
    // Check CoProvisioningExports for status
    
    $ret = array(
      'status'    => ProvisioningStatusEnum::NotProvisioned,
      'timestamp' => null,
      'comment'   => ""
    );
    
    // Try to pull an existing record
    $args = array();
    $args['conditions']['CoProvisioningExport.co_provisioning_target_id'] = $coProvisioningTargetId;
    if($coPersonId) {
      $args['conditions']['CoProvisioningExport.co_person_id'] = $coPersonId;
    } else {
      $args['conditions']['CoProvisioningExport.co_group_id'] = $coGroupId;
    }
    $export = $this->CoProvisioningTarget->CoProvisioningExport->find('first', $args);
    
    if(!empty($export)) {
      $ret['status'] = ProvisioningStatusEnum::Provisioned;
      $ret['timestamp'] = $export['CoProvisioningExport']['exporttime'];
    }
    
    return $ret;
  }
  
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
  
  abstract public function provision($coProvisioningTargetData, $op, $provisioningData);
}
