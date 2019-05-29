<?php
/**
 * COmanage Registry CO MidPoint Provisioner Target Model
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

App::uses("CoProvisionerPluginTarget", "Model");

/**
 * MidPoint provisioner target.
 *
 * @see https://wiki.evolveum.com/display/midPoint/REST+API
 * @see https://wiki.evolveum.com/display/midPoint/MidPoint+Common+Schema
 */
class CoMidPointProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoMidPointProvisionerTarget";

  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");

  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Provisioning Target ID must be provided'
    ),
    'ssl_allow_self_signed' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'ssl_verify_host' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'ssl_verify_peer' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'ssl_verify_peer_name' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
  );

  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    CakeLog::debug("CoMidPointProvisionerTarget.provision start op=$op");
    CakeLog::debug('CoMidPointProvisionerTarget.provision $coProvisioningTargetData ' . var_export($coProvisioningTargetData, true));
    CakeLog::debug('CoMidPointProvisionerTarget.provision $provisioningData ' . var_export($provisioningData, true));

    switch($op) {
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
        return $this->provisionCoPerson($coProvisioningTargetData, $provisioningData);

      default:
        // Log noop and fall through.
        $this->log("MidPointProvisioner provisioning action $op is not implemented");
    }

    CakeLog::debug("CoMidPointProvisionerTarget.provision done op=$op");
    return true;
  }

  public function provisionCoPerson($coProvisioningTargetData, $provisioningData) {
    CakeLog::debug("CoMidPointProvisionerTarget.provisionCoPerson");
    return true;
  }
}