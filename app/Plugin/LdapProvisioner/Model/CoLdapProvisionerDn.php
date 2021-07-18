<?php
/**
 * COmanage Registry CO LDAP Provisioner DN Model
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

class CoLdapProvisionerDn extends AppModel {
  // Define class name for cake
  public $name = "CoLdapProvisionerDn";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "LdapProvisioner.CoLdapProvisionerTarget",
    "CoPerson",
    "CoGroup"
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
      'required' => false,
      'allowEmpty' => true
    ),
    'co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'dn' => array(
      'rule' => 'notBlank'
    )
  );
  
  /**
   * Assign a DN for a CO Group.
   *
   * @since  COmanage Registry v0.8.2
   * @param  Array CO Provisioning Target data
   * @param  Array CO Group data
   * @return String DN
   * @throws RuntimeException
   */
  
  public function assignGroupDn($coProvisioningTargetData, $coGroupData) {
    $dn = "";
    
    // For now, we always construct the DN using cn.
    
    if(empty($coGroupData['CoGroup']['name'])) {
      throw new RuntimeException(_txt('er.ldapprovisioner.dn.component', 'cn'));
    }
    
    if(empty($coProvisioningTargetData['CoLdapProvisionerTarget']['group_basedn'])) {
      // Throw an exception... this should be defined
      throw new RuntimeException(_txt('er.ldapprovisioner.dn.config'));
    }
    
    $dn = "cn=" . $coGroupData['CoGroup']['name']
        . "," . $coProvisioningTargetData['CoLdapProvisionerTarget']['group_basedn'];
      
    return $dn;
  }
  
  /**
   * Assign a DN for a CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Array CO Provisioning Target data
   * @param  Array CO Person data
   * @return String DN
   * @throws RuntimeException
   */
  
  public function assignPersonDn($coProvisioningTargetData, $coPersonData) {
    // Start by checking the DN configuration
    
    if(empty($coProvisioningTargetData['CoLdapProvisionerTarget']['dn_attribute_name'])
       || empty($coProvisioningTargetData['CoLdapProvisionerTarget']['dn_identifier_type'])) {
      // Throw an exception... these should be defined
      throw new RuntimeException(_txt('er.ldapprovisioner.dn.config'));
    }
    
    // Walk through available identifiers looking for a match
    
    $dn = "";
    
    if(!empty($coPersonData['Identifier'])) {
      foreach($coPersonData['Identifier'] as $identifier) {
        if(!empty($identifier['type'])
           && $identifier['type'] == $coProvisioningTargetData['CoLdapProvisionerTarget']['dn_identifier_type']
           && !empty($identifier['identifier'])
           && $identifier['status'] == StatusEnum::Active) {
          // Match. We'll use the first active row found... it's undefined how to behave
          // if multiple active identifiers of a given type are found. (We don't actually
          // need to check for Status=Active since ProvisionerBehavior will filter out
          // non-Active status.)
          
          $dn = $coProvisioningTargetData['CoLdapProvisionerTarget']['dn_attribute_name']
              . "=" . $identifier['identifier']
              . "," . $coProvisioningTargetData['CoLdapProvisionerTarget']['basedn'];
          
          break;
        }
      }
    }
    
    if($dn == "") {
      // We can't proceed without a DN
      throw new RuntimeException(_txt('er.ldapprovisioner.dn.component',
                                      array($coProvisioningTargetData['CoLdapProvisionerTarget']['dn_identifier_type'])));
    }
    
    return $dn;
  }
  
  /**
   * Determine the attributes used to generate a DN.
   *
   * @since  COmanage Registry v0.8
   * @param  Array CO Provisioning Target data
   * @param  String DN
   * @param  String Mode ('group' or 'person')
   * @return Array Attribute/value pairs used to generate the DN, not including the base DN
   * @throws RuntimeException
   */
  
  public function dnAttributes($coProvisioningTargetData, $dn, $mode) {
    // We assume dn is of the form attr1=val1, attr2=val2, basedn
    // where based matches $coProvisioningTargetData. Strip off basedn
    // and then split up the remaining string. Note we'll fail if the
    // base DN changes. Currently, that would require manual cleanup.
    
    $ret = array();
    
    $basedn = $coProvisioningTargetData['CoLdapProvisionerTarget']['basedn'];
    
    if($mode == 'group') {
      $basedn = $coProvisioningTargetData['CoLdapProvisionerTarget']['group_basedn'];
    }
    
    $attrs = explode(",", rtrim(str_replace($basedn, "", $dn), " ,"));
    
    foreach($attrs as $a) {
      $av = explode("=", $a, 2);
      
      $ret[ $av[0] ] = $av[1];
    }
    
    return $ret;
  }
  
  /**
   * Map a set of CO Group Members to their DNs.
   *
   * @since  COmanage Registry v0.8.2
   * @param  Array CO Group Members
   * @return Array Array of DNs found -- note this array is not in any particular order, and may have fewer entries
   */
  
  public function dnsForMembers($coGroupMembers) {
    return $this->mapCoGroupMembersToDns($coGroupMembers);
  }
  
  /**
   * Map a set of CO Group Member owners to their DNs.
   *
   * @since  COmanage Registry v0.8.2
   * @param  Array CO Group Members
   * @return Array Array of DNs found -- note this array is not in any particular order, and may have fewer entries
   */
  
  public function dnsForOwners($coGroupMembers) {
    return $this->mapCoGroupMembersToDns($coGroupMembers, true);
  }
  
  /**
   * Map a set of CO Group Members to their DNs. A similar function is in CoGroupMember.php.
   *
   * @since  COmanage Registry v0.8.2
   * @param  Array CO Group Members
   * @param  Boolean True to map owners, false to map members
   * @return Array Array of DNs found -- note this array is not in any particular order, and may have fewer entries
   */
  
  private function mapCoGroupMembersToDns($coGroupMembers, $owners=false) {
    // Walk through the members and pull the CO Person IDs
    
    $coPeopleIds = array();
    
    foreach($coGroupMembers as $m) {
      if(($owners && $m['CoGroupMember']['owner'])
         || (!$owners && $m['CoGroupMember']['member'])) {
        $coPeopleIds[] = $m['CoGroupMember']['co_person_id'];
      }
    }
    
    if(!empty($coPeopleIds)) {
      // Now perform a find to get the list. Note using the IN notation like this
      // may not scale to very large sets of members.
      
      $args = array();
      $args['conditions']['CoLdapProvisionerDn.co_person_id'] = $coPeopleIds;
      $args['fields'] = array('CoLdapProvisionerDn.co_person_id', 'CoLdapProvisionerDn.dn');
      
      return array_values($this->find('list', $args));
    } else {
      return array();
    }
  }
  
  /**
   * Obtain a DN for a provisioning subject, possibly assigning or reassigning one.
   *
   * @since  COmanage Registry v0.8.2
   * @param  Array CO Provisioning Target data
   * @param  Array CO Provisioning data
   * @param  String Mode: 'group' or 'person'
   * @param  Boolean Whether to assign a DN if one is not found and reassign if the DN should be changed
   * @return Array An array of the following:
   *               - olddn: Old (current) DN (may be null)
   *               - olddnid: Database row ID of old dn (may be null, to facilitate delete)
   *               - newdn: New DN (may be null)
   *               - newdnerr: Error message if new in cannot be assigned
   * @throws RuntimeException
   */
  
  public function obtainDn($coProvisioningTargetData, $provisioningData, $mode, $assign=true) {
    $curDn = null;
    $curDnId = null;
    $newDn = null;
    $newDnErr = null;
    
    // First see if we have already assigned a DN
    
    $args = array();
    $args['conditions']['CoLdapProvisionerDn.co_ldap_provisioner_target_id'] = $coProvisioningTargetData['CoLdapProvisionerTarget']['id'];
    if($mode == 'person') {
      $args['conditions']['CoLdapProvisionerDn.co_person_id'] = $provisioningData['CoPerson']['id'];
    } else {
      $args['conditions']['CoLdapProvisionerDn.co_group_id'] = $provisioningData['CoGroup']['id'];
    }
    $args['contain'] = false;
    
    $dnRecord = $this->find('first', $args);
    
    if(!empty($dnRecord)) {
      $curDn = $dnRecord['CoLdapProvisionerDn']['dn'];
      $curDnId = $dnRecord['CoLdapProvisionerDn']['id'];
    }
    
    // We always try to (re)calculate the DN, but only store it if $assign is true.
    
    try {
      if($mode == 'person') {
        $newDn = $this->assignPersonDn($coProvisioningTargetData, $provisioningData);
      } else {
        $newDn = $this->assignGroupDn($coProvisioningTargetData, $provisioningData);
      }
    }
    catch(Exception $e) {
      // Rather than throw an exception, store the error in the return array.
      // We do this because there are many common times we will fail to assign a
      // DN (especially on user creation and deletion), so we'll pass the error
      // up the stack and let the calling function decide what to do.
      
      $newDnErr = $e->getMessage();
    }
    
    if($assign) {
      // If the the DN doesn't match the existing DN (including if there is no
      // existing DN), update it
      
      if($newDn && ($curDn != $newDn)) {
        $newDnRecord = array();
        $newDnRecord['CoLdapProvisionerDn']['co_ldap_provisioner_target_id'] = $coProvisioningTargetData['CoLdapProvisionerTarget']['id'];
        if($mode == 'person') {
          $newDnRecord['CoLdapProvisionerDn']['co_person_id'] = $provisioningData['CoPerson']['id'];
        } else {
          $newDnRecord['CoLdapProvisionerDn']['co_group_id'] = $provisioningData['CoGroup']['id'];
        }
        $newDnRecord['CoLdapProvisionerDn']['dn'] = $newDn;
        
        if(!empty($dnRecord)) {
          $newDnRecord['CoLdapProvisionerDn']['id'] = $dnRecord['CoLdapProvisionerDn']['id'];
        }

        $this->clear();
        if(!$this->save($newDnRecord)) {
          throw new RuntimeException(_txt('er.db.save'));
        }
      }
    }
    
    return array('olddn'    => $curDn,
                 'olddnid'  => $curDnId,
                 'newdn'    => $newDn,
                 'newdnerr' => $newDnErr);
  }
}
