<?php
/**
 * COmanage Registry CO LDAP Provisioner DN Model
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

class CoLdapProvisionerDn extends AppModel {
  // Define class name for cake
  public $name = "CoLdapProvisionerDn";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "LdapProvisioner.CoLdapProvisionerTarget",
    "CoPerson"
  );
    
  // Default display field for cake generated views
  public $displayField = "dn";
  
  // Validation rules for table elements
  public $validate = array(
    'co_ldap_provisioner_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO LDAP Provisioning Target ID must be provided'
    ),
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Person ID must be provided'
    ),
    'dn' => array(
      'rule' => 'notEmpty'
    )
  );
  
  /**
   * Assign (and save) a DN for a CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Array CO Provisioning Target data
   * @param  Array CO Person data
   * @return String DN
   * @throws RuntimeException
   */
  
  public function assignDn($coProvisioningTargetData, $coPersonData) {
// XXX make this configurable (or cut a ticket)
    
    if(!isset($coPersonData['CoPerson']['id'])) {
      throw new RuntimeException(_txt('er.ldapprovisioner.dn.component', array("co_person_id")));
    }
    
    $dn = "uid=" . $coPersonData['CoPerson']['id'] . "," . $coProvisioningTargetData['CoLdapProvisionerTarget']['basedn'];
    
    $dnRecord = array();
    $dnRecord['CoLdapProvisionerDn']['co_ldap_provisioner_target_id'] = $coProvisioningTargetData['CoLdapProvisionerTarget']['id'];
    $dnRecord['CoLdapProvisionerDn']['co_person_id'] = $coPersonData['CoPerson']['id'];
    $dnRecord['CoLdapProvisionerDn']['dn'] = $dn;
    
    if($this->save($dnRecord)) {
      return $dn;
    } else {
      throw new RuntimeException(_txt('er.db.save'));
    }
  }
}
