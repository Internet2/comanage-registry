<?php
/**
 * COmanage Registry CO LDAP Provisioner Target Model
 *
 * Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("CoProvisionerPluginTarget", "Model");

class CoLdapProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoLdapProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");
  
  public $hasMany = array("LdapProvisioner.CoLdapProvisionerDn");
  
  // Default display field for cake generated views
  public $displayField = "serverurl";
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Provisioning Target ID must be provided'
    ),
    'serverurl' => array(
      'rule' => array('custom', '/^ldaps?:\/\/.*/'),
      'required' => true,
      'allowEmpty' => false,
      'message' => 'Please enter a valid ldap or ldaps URL'
    ),
    'binddn' => array(
      'rule' => 'notEmpty'
    ),
    'password' => array(
      'rule' => 'notEmpty'
    ),
    'basedn' => array(
      'rule' => 'notEmpty'
    )
  );
  
  /**
   * Query an LDAP server.
   *
   * @since  COmanage Registry v0.8
   * @param  String Server URL
   * @param  String Bind DN
   * @param  String Password
   * @param  String Base DN
   * @param  String Search filter
   * @param  Array Attributes to return (or null for all)
   * @return Array Search results
   * @throws RuntimeException
   */
  
  protected function queryLdap($serverUrl, $bindDn, $password, $baseDn, $filter, $attributes=array()) {
    $ret = array();
    
    $cxn = ldap_connect($serverUrl);
    
    if(!$cxn) {
      throw new RuntimeException(_txt('er.ldapprovisioner.connect'), LDAP_CONNECT_ERROR);
    }
    
    // Use LDAP v3 (this could perhaps become an option at some point)
    ldap_set_option($cxn, LDAP_OPT_PROTOCOL_VERSION, 3);
    
    if(!@ldap_bind($cxn, $bindDn, $password)) {
      throw new RuntimeException(ldap_error($cxn), ldap_errno($cxn));
    }
    
    // Try to search using base DN; look for any matching object under the base DN
    
    $s = @ldap_search($cxn, $baseDn, $filter, $attributes);
    
    if(!$s) {
      throw new RuntimeException(ldap_error($cxn), ldap_errno($cxn));
    }
    
    $ret = ldap_get_entries($cxn, $s);
    
    ldap_unbind($cxn);
    
    return $ret;
  }
  
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
      'status'    => ProvisioningStatusEnum::Unknown,
      'timestamp' => null,
      'comment'   => ""
    );
    
    // Pull the DN for this person, if we have one. Cake appears to correctly interpret
    // these conditions into a JOIN.
    $args = array();
    $args['conditions']['CoLdapProvisionerTarget.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['conditions']['CoLdapProvisionerDn.co_person_id'] = $coPersonId;
    
    $dnRecord = $this->CoLdapProvisionerDn->find('first', $args);
    
    if(!empty($dnRecord)) {
      // Query LDAP and see if there is a record
      try {
        $ldapRecord = $this->queryLdap($dnRecord['CoLdapProvisionerTarget']['serverurl'],
                                       $dnRecord['CoLdapProvisionerTarget']['binddn'],
                                       $dnRecord['CoLdapProvisionerTarget']['password'],
                                       $dnRecord['CoLdapProvisionerDn']['dn'],
                                       "(objectclass=*)",
                                       array('modifytimestamp'));
        
        if(!empty($ldapRecord)) {
          if(!empty($ldapRecord[0]['modifytimestamp'][0])) {
            // Timestamp is formatted 20130223145645Z and needs to be converted
            $ret['timestamp'] = strtotime($ldapRecord[0]['modifytimestamp'][0]);
          }
          
          $ret['status'] = ProvisioningStatusEnum::Provisioned;
          $ret['comment'] = $dnRecord['CoLdapProvisionerDn']['dn'];
        } else {
          $ret['status'] = ProvisioningStatusEnum::NotProvisioned;
          $ret['comment'] = $dnRecord['CoLdapProvisionerDn']['dn'];
        }
      }
      catch(RuntimeException $e) {
        if($e->getCode() == 32) { // LDAP_NO_SUCH_OBJECT
          $ret['status'] = ProvisioningStatusEnum::NotProvisioned;
          $ret['comment'] = $dnRecord['CoLdapProvisionerDn']['dn'];
        } else {
          $ret['status'] = ProvisioningStatusEnum::Unknown;
          $ret['comment'] = $e->getMessage();
        }
      }
    } else {
      // No DN on file
      
      $ret['status'] = ProvisioningStatusEnum::NotProvisioned;
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
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */
  
  public function provision($coProvisioningTargetData, $op, $coPersonData) {
    // First figure out what to do
    $assigndn = false;
    $delete   = false;
    $add      = false;
    
    // XXX CO-548 - Implement the other ProvisioningActions
    switch($op) {
      case ProvisioningActionEnum::CoPersonAdded:
        $assigndn = true;
        $delete = false;  // Arguably, this should be true to clear out any prior debris
        $add = true;
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
        $assigndn = false;
        $delete = true;
        $add = false;
        break;
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
        $assigndn = true;
        $delete = true;
        $add = true;
        break;
      case ProvisioningActionEnum::CoPersonUpdated:
        $assigndn = true;  // An update may cause an existing person to be written to LDAP for the first time
        // XXX This should really become a $modify
        $delete = true;
        $add = true;
        break;
      default:
        throw new RuntimeException("Not Implemented");
        break;
    }
    
    // Next, see if we already have a DN for this person
    
    $dn = null;
    
    $args = array();
    $args['conditions']['CoLdapProvisionerDn.co_ldap_provisioner_target_id'] = $coProvisioningTargetData['CoLdapProvisionerTarget']['id'];
    $args['conditions']['CoLdapProvisionerDn.co_person_id'] = $coPersonData['CoPerson']['id'];
    $args['contain'] = false;
    
    $dnRecord = $this->CoLdapProvisionerDn->find('first', $args);
    
    if(empty($dnRecord)) {
      if($assigndn) {
        // If we don't have a DN, assign one
        
        $dn = $this->CoLdapProvisionerDn->assignDn($coProvisioningTargetData, $coPersonData);
      }
    } else {
      $dn = $dnRecord['CoLdapProvisionerDn']['dn'];
    }
    
    if(!$dn) {
      throw new RuntimeException(_txt('er.ldapprovisioner.dn.none', array($coPersonData['CoPerson']['id'])));
    }
    
    // Assemble an LDAP record
    
    // XXX make this configurable, at least as per (CO-549)
    // multi-valued attributes can be set via $attributes['mail'][0]
    $attributes = array();
    $attributes['objectclass'][] = 'top';
    $attributes['objectclass'][] = 'person';
    $attributes['objectclass'][] = 'organizationalperson';
    $attributes['objectclass'][] = 'inetorgperson';
    // Note: RFC4519 requires sn and cn for person
    $attributes['cn'] = generateCn($coPersonData['Name']);
    $attributes['sn'] = $coPersonData['Name']['family'];
    $attributes['givenname'] = $coPersonData['Name']['given'];
    $attributes['uid'] = $coPersonData['CoPerson']['id'];
    if(!empty($coPersonData['CoPersonRole'][0]['title'])) {
      $attributes['title'] = $coPersonData['CoPersonRole'][0]['title'];
    }
    if(!empty($coPersonData['CoPersonRole'][0]['Address'][0]['line1'])) {
      // XXX should concatenate line2, or implement CO-539 and convert newlines to $
      $attributes['street'] = $coPersonData['CoPersonRole'][0]['Address'][0]['line1'];
    }
    if(!empty($coPersonData['CoPersonRole'][0]['Address'][0]['locality'])) {
      $attributes['l'] = $coPersonData['CoPersonRole'][0]['Address'][0]['locality'];
    }
    if(!empty($coPersonData['CoPersonRole'][0]['Address'][0]['state'])) {
      $attributes['st'] = $coPersonData['CoPersonRole'][0]['Address'][0]['state'];
    }
    if(!empty($coPersonData['CoPersonRole'][0]['Address'][0]['postal_code'])) {
      $attributes['postalcode'] = $coPersonData['CoPersonRole'][0]['Address'][0]['postal_code'];
    }
    if(!empty($coPersonData['CoPersonRole'][0]['TelephoneNumber'])) {
      foreach($coPersonData['CoPersonRole'][0]['TelephoneNumber'] as $t) {
        $attributes['telephonenumber'][] = $t['number'];
      }
    }
    if(!empty($coPersonData['EmailAddress'][0]['mail'])) {
      $attributes['mail'] = $coPersonData['EmailAddress'][0]['mail'];
    }
    
    // Bind to the server
    
    $cxn = ldap_connect($coProvisioningTargetData['CoLdapProvisionerTarget']['serverurl']);
    
    if(!$cxn) {
      throw new RuntimeException(_txt('er.ldapprovisioner.connect'), LDAP_CONNECT_ERROR);
    }
    
    // Use LDAP v3 (this could perhaps become an option at some point)
    ldap_set_option($cxn, LDAP_OPT_PROTOCOL_VERSION, 3);
    
    if(!@ldap_bind($cxn,
                   $coProvisioningTargetData['CoLdapProvisionerTarget']['binddn'],
                   $coProvisioningTargetData['CoLdapProvisionerTarget']['password'])) {
      throw new RuntimeException(ldap_error($cxn), ldap_errno($cxn));
    }
    
    if($delete) {
      // Delete any previous entry. For now, ignore any error.
      @ldap_delete($cxn, $dn);
    }
    
    if($add) {
      // Write a new entry
      if(!@ldap_add($cxn, $dn, $attributes)) {
        throw new RuntimeException(ldap_error($cxn), ldap_errno($cxn));
      }
    }
    
    // Drop the connection
    ldap_unbind($cxn);
    
    // We rely on the LDAP server to manage last modify time
    
    return true;
  }
  
  /**
   * Test an LDAP server to verify that the connection available is valid.
   *
   * @since  COmanage Registry v0.8
   * @param  String Server URL
   * @param  String Bind DN
   * @param  String Password
   * @param  String Base DN
   * @return Boolean True if parameters are valid
   * @throws RuntimeException
   */
  
  public function verifyLdapServer($serverUrl, $bindDn, $password, $baseDn) {
    $results = $this->queryLdap($serverUrl, $bindDn, $password, $baseDn, "(objectclass=*)", array("dn"));
    
    if(count($results) < 1) {
      throw new RuntimeException(_txt('er.ldapprovisioner.basedn'));
    }
    
    return true;
  }
}
