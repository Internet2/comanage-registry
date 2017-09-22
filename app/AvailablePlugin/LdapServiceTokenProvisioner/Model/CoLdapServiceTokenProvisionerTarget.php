<?php
/**
 * COmanage Registry LDAP Service Token Provisioner Target Model
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoProvisionerPluginTarget", "Model");

class CoLdapServiceTokenProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoLdapServiceTokenProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget", "CoService");
  
  // Default display field for cake generated views
  public $displayField = "co_service_id";
  
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
   * @since  COmanage Registry v2.0.0
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws RuntimeException
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    $modify = false;
    
    // This may not be exactly the right handling, but this is a temporary plugin
    // that will be replaced when merged into core code
    
    switch($op) {
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
        $modify = true;
        break;
      case ProvisioningActionEnum::CoPersonExpired:
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        if(in_array($provisioningData['CoPerson']['status'],
                    array(StatusEnum::Active,
                          StatusEnum::Expired,
                          StatusEnum::GracePeriod,
                          StatusEnum::Suspended))) {
          $modify = true;
        }
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
      case ProvisioningActionEnum::CoGroupAdded:
      case ProvisioningActionEnum::CoGroupDeleted:
      case ProvisioningActionEnum::CoGroupUpdated:
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
        break;
      default:
        // Ignore anything else
        return true;
        break;
    }
    
    if(!$modify) {
      return true;
    }

    // Pull the DN for the person
    
    $CoLdapProvisionerDn = ClassRegistry::init('LdapProvisioner.CoLdapProvisionerDn');
    
    $args = array();
    $args['conditions']['CoLdapProvisionerDn.co_ldap_provisioner_target_id'] = $coProvisioningTargetData['CoLdapServiceTokenProvisionerTarget']['co_ldap_provisioner_target_id'];
    $args['conditions']['CoLdapProvisionerDn.co_person_id'] = $provisioningData['CoPerson']['id'];
    $args['fields'] = array('id', 'dn');
    $args['contain'] = false;
    
    $dn = $CoLdapProvisionerDn->find('first', $args);
    
    if(empty($dn)) {
      // Should really throw an error
      return true;
    }
    
    // Pull the LDAP configuration
    
    $CoLdapProvisionerTarget = ClassRegistry::init('LdapProvisioner.CoLdapProvisionerTarget');
    
    $args = array();
    $args['conditions']['CoLdapProvisionerTarget.id'] = $coProvisioningTargetData['CoLdapServiceTokenProvisionerTarget']['co_ldap_provisioner_target_id'];
    $args['contain'] = false;
    
    $ldapTarget = $CoLdapProvisionerTarget->find('first', $args);
    
    if(empty($ldapTarget)) {
      // Should really throw an error
      return true;
    }
    
    // Pull the desired token
    
    $CoServiceToken = ClassRegistry::init('CoServiceToken.CoServiceToken');
    
    $args = array();
    $args['conditions']['CoServiceToken.co_service_id'] = $coProvisioningTargetData['CoLdapServiceTokenProvisionerTarget']['co_service_id'];
    $args['conditions']['CoServiceToken.co_person_id'] = $provisioningData['CoPerson']['id'];
    $args['contain'] = false;
    
    $token = $CoServiceToken->find('first', $args);
    
    // Modify the LDAP entry
    
    $attributes = array();
    
    if($modify && !empty($token['CoServiceToken']['token'])) {
      $attributes['userPassword'] = $token['CoServiceToken']['token'];
    } else {
      $attributes['userPassword'] = '';
    }
    
    // Bind to the server
    
    $cxn = ldap_connect($ldapTarget['CoLdapProvisionerTarget']['serverurl']);
    
    if(!$cxn) {
      throw new RuntimeException(_txt('er.ldapprovisioner.connect'), 0x5b /*LDAP_CONNECT_ERROR*/);
    }
    
    // Use LDAP v3 (this could perhaps become an option at some point), although note
    // that ldap_rename (used below) *requires* LDAP v3.
    ldap_set_option($cxn, LDAP_OPT_PROTOCOL_VERSION, 3);
    
    if(!@ldap_bind($cxn,
                   $ldapTarget['CoLdapProvisionerTarget']['binddn'],
                   $ldapTarget['CoLdapProvisionerTarget']['password'])) {
      throw new RuntimeException(ldap_error($cxn), ldap_errno($cxn));
    }
    
    if(!@ldap_mod_replace($cxn, $dn['CoLdapProvisionerDn']['dn'], $attributes)) {
      throw new RuntimeException(ldap_error($cxn), ldap_errno($cxn));
    }
    
    // Drop the connection
    ldap_unbind($cxn);

    return true;
  }
}
