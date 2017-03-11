<?php
/**
 * COmanage Registry CO Homedir Provisioner Target Model
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
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoProvisionerPluginTarget", "Model");

class CoHomedirProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoHomedirProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");
  
  // Default display field for cake generated views
  public $displayField = "co_provisioning_target_id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Provisioning Target ID must be provided'
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
    // For this first pass, we keep it simple: on person add/edit operations, create
    // the directory (which may or may not exist).
    
    $gidnumber = null;
    $homedir = null;
    $uidnumber = null;
    
    foreach($provisioningData['Identifier'] as $m) {
      if(isset($m['type'])
         && $m['status'] == StatusEnum::Active) {
        switch($m['type']) {
          case 'gidNumber':
            $gidnumber = $m['identifier'];
            break;
          case 'homeDirectory':
            $homedir = $m['identifier'];
            break;
          case 'uidNumber':
            $uidnumber = $m['identifier'];
            break;
        }
      }
    }
    
    if(!$gidnumber || !$homedir || !$uidnumber) {
      throw new RuntimeException(_txt('er.homedirprovisioner.attributes'));
    }
    
    switch($op) {
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        // We won't ordinarily have permissions to execute this
        mkdir($homedir, '0777');
        chown($homedir, $uidnumber);
        chgrp($homedir, $gidnumber);
        break;
    }
    
    /* future:
     *  archive on expire? delete on delete?
     *  "Managing Unix Accounts" wiki page?
     */
    
    // For now, always return true
    return true;
  }
}
