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
  
  public $hasMany = array(
    "CoLdapProvisionerDn" => array(
      'className' => 'LdapProvisioner.CoLdapProvisionerDn',
      'dependent' => true
    ),
    "CoLdapProvisionerAttribute" => array(
      'className' => 'LdapProvisioner.CoLdapProvisionerAttribute',
      'dependent' => true
    ),
    "CoLdapProvisionerAttrGrouping" => array(
      'className' => 'LdapProvisioner.CoLdapProvisionerAttrGrouping',
      'dependent' => true
    )
  );
  
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
    'dnattr' => array(
      'rule' => 'notEmpty'
    ),
    'basedn' => array(
      'rule' => 'notEmpty'
    ),
    'oc_person' => array(
      'rule' => 'boolean'
    ),
    'oc_orgperson' => array(
      'rule' => 'boolean'
    ),
    'oc_inetorgperson' => array(
      'rule' => 'boolean'
    ),
    'oc_eduperson' => array(
      'rule' => 'boolean'
    )
  );
  
  /**
   * Assemble attributes for an LDAP record.
   *
   * @since  COmanage Registry v0.8
   * @param  Array CO Provisioning Target data
   * @param  Array CO Person or CO Group Data used for provisioning
   * @param  Boolean Whether or not this will be for a modify operation
   * @param  Array Attributes used to generate the DN for this person, as returned by CoLdapProvisionerDn::dnAttributes
   * @return Array Attribute data suitable for passing to ldap_add, etc
   * @throws UnderflowException
   */
  
  protected function assembleAttributes($coProvisioningTargetData, $provisioningData, $modify, $dnAttributes) {
    // First see if we're working with a Group record or a Person record
    $person = isset($provisioningData['CoPerson']['id']);
    $group = isset($provisioningData['CoGroup']['id']);
    
    // Pull the attribute configuration
    $args = array();
    $args['conditions']['CoLdapProvisionerAttribute.co_ldap_provisioner_target_id'] = $coProvisioningTargetData['CoLdapProvisionerTarget']['id'];
    $args['contain'] = false;
    
    $cAttrs = $this->CoLdapProvisionerAttribute->find('all', $args);
    
    // Rekey the attributes array on attribute name
    $configuredAttributes = array();
    
    foreach($cAttrs as $a) {
      if(!empty($a['CoLdapProvisionerAttribute']['attribute'])) {
        $configuredAttributes[ $a['CoLdapProvisionerAttribute']['attribute'] ] = $a['CoLdapProvisionerAttribute'];
      }
    }
    
    // Pull the attribute groupings
    $args = array();
    $args['conditions']['CoLdapProvisionerAttrGrouping.co_ldap_provisioner_target_id'] = $coProvisioningTargetData['CoLdapProvisionerTarget']['id'];
    $args['contain'] = false;
    
    $cAttrGrs = $this->CoLdapProvisionerAttrGrouping->find('all', $args);
    
    // Rekey the attributes array on attribute name
    $configuredAttributeGroupings = array();
    
    foreach($cAttrGrs as $g) {
      if(!empty($g['CoLdapProvisionerAttrGrouping']['grouping'])) {
        $configuredAttributeGroupings[ $g['CoLdapProvisionerAttrGrouping']['grouping'] ] = $g['CoLdapProvisionerAttrGrouping'];
      }
    }
    
    // Marshalled attributes ready for export
    $attributes = array();
    
    // Full set of supported attributes (not what's configured)
    $supportedAttributes = $this->supportedAttributes();
    
    // Note we don't need to check for inactive status where relevant since
    // ProvisionerBehavior will remove those from the data we get.
    
    foreach(array_keys($supportedAttributes) as $oc) {
      // Skip objectclasses that aren't relevant for the sort of data we're working with
      if(($person && $oc == 'groupOfNames')
         || ($group && !in_array($oc, array('groupOfNames','eduMember')))) {
        continue;
      }
      
      // Iterate across objectclasses, looking for those that are required or enabled
      
      if($supportedAttributes[$oc]['objectclass']['required']
         || (isset($coProvisioningTargetData['CoLdapProvisionerTarget']['oc_' . strtolower($oc)])
             && $coProvisioningTargetData['CoLdapProvisionerTarget']['oc_' . strtolower($oc)])) {
        $attributes['objectclass'][] = $oc;
        
        // Within the objectclass, iterate across the supported attributes looking
        // for required or enabled attributes
        
        foreach(array_keys($supportedAttributes[$oc]['attributes']) as $attr) {
          if($supportedAttributes[$oc]['attributes'][$attr]['required']
             || (isset($configuredAttributes[$attr]['export'])
                 && $configuredAttributes[$attr]['export'])) {
            // Does this attribute support multiple values?
            $multiple = (isset($supportedAttributes[$oc]['attributes'][$attr]['multiple'])
                         && $supportedAttributes[$oc]['attributes'][$attr]['multiple']);
            
            // Is a type specified for this attribute via a grouping?
            $targetType = null;
            
            if(!empty($supportedAttributes[$oc]['attributes'][$attr]['grouping'])) {
              $grouping = $supportedAttributes[$oc]['attributes'][$attr]['grouping'];
              
              if(!empty($configuredAttributeGroupings[$grouping]['type'])) {
                $targetType = $configuredAttributeGroupings[$grouping]['type'];
              }
            }
            
            // Or explicitly?
            if(!$targetType && !empty($configuredAttributes[$attr]['type'])) {
              $targetType = $configuredAttributes[$attr]['type'];
            }
            
            switch($attr) {
              // Name attributes
              case 'cn':
                if($person) {
                  // Currently only preferred name supported (CO-333)
                  $attributes[$attr] = generateCn($provisioningData['PrimaryName']);
                } else {
                  $attributes[$attr] = $provisioningData['CoGroup']['name'];
                }
                break;
              case 'givenName':
                // Currently only preferred name supported (CO-333)
                $attributes[$attr] = $provisioningData['PrimaryName']['given'];
                break;
              case 'sn':
                // Currently only preferred name supported (CO-333)
                $attributes[$attr] = $provisioningData['PrimaryName']['family'];
                break;
              // Attributes from CO Person Role
              case 'eduPersonAffiliation':
              case 'o':
              case 'ou':
              case 'title':
                // Map the attribute to the column
                $cols = array(
                  'eduPersonAffiliation' => 'affiliation',
                  'o' => 'o',
                  'ou' => 'ou',
                  'title' => 'title'
                );
                
                // Walk through each role
                $found = false;
                
                foreach($provisioningData['CoPersonRole'] as $r) {
                  if(!empty($r[ $cols[$attr] ])) {
                    if($attr == 'eduPersonAffiliation') {
                      // Map back to the controlled vocabulary
                      $attributes[$attr][] = _txt('en.affil', null, $r[ $cols[$attr] ]);
                    } else {
                      $attributes[$attr][] = $r[ $cols[$attr] ];
                    }
                    
                    $found = true;
                  }
                  
                  if(!$multiple && $found) {
                    break;
                  }
                }
                
                if(!$found && $modify) {
                  $attributes[$attr] = array();
                }
                break;
              // Attributes from models attached to CO Person
              case 'eduPersonPrincipalName':
              case 'employeeNumber':
              case 'mail':
              case 'uid':
                // Map the attribute to the model and column
                $mods = array(
                  'eduPersonPrincipalName' => 'Identifier',
                  'employeeNumber' => 'Identifier',
                  'mail' => 'EmailAddress',
                  'uid' => 'Identifier'
                );
                
                $cols = array(
                  'eduPersonPrincipalName' => 'identifier',
                  'employeeNumber' => 'identifier',
                  'mail' => 'mail',
                  'uid' => 'identifier'
                );
                
                $modelList = null;
                
                if(isset($configuredAttributes[$attr]['use_org_value'])
                   && $configuredAttributes[$attr]['use_org_value']) {
                  // Use organizational identity value for this attribute
                  
                  // It's unclear what to do here if there is more than one CoOrgIdentityLink...
                  // which identity do we choose? For now, the first one.
                  
                  if(isset($provisioningData['CoOrgIdentityLink'][0]['OrgIdentity'][ $mods[$attr] ])) {
                    // Don't use =& syntax here, it changes $provisioningData
                    $modelList = $provisioningData['CoOrgIdentityLink'][0]['OrgIdentity'][ $mods[$attr] ];
                  }
                } elseif(isset($provisioningData[ $mods[$attr] ])) {
                  // Use CO Person value for this attribute
                  $modelList = $provisioningData[ $mods[$attr] ];
                }
                
                // Walk through each model instance
                $found = false;
                
                if(isset($modelList)) {
                  foreach($modelList as $m) {
                    // If a type is set, make sure it matches
                    if(empty($targetType) || ($targetType == $m['type'])) {
                      // And finally that the attribute itself is set
                      if(!empty($m[ $cols[$attr] ])) {
                        $attributes[$attr][] = $m[ $cols[$attr] ];
                        $found = true;
                      }
                    }
                    
                    if(!$multiple && $found) {
                      break;
                    }
                  }
                  
                  if(!$multiple && $found) {
                    break;
                  }
                }
                
                if(!$found && $modify) {
                  $attributes[$attr] = array();
                }
                break;
              // Attributes from models attached to CO Person Role
              case 'facsimileTelephoneNumber':
              case 'l':
              case 'mail':
              case 'mobile':
              case 'postalCode':
              case 'st':
              case 'street':
              case 'telephoneNumber':
                // Map the attribute to the model and column
                $mods = array(
                  'facsimileTelephoneNumber' => 'TelephoneNumber',
                  'l' => 'Address',
                  'mail' => 'EmailAddress',
                  'mobile' => 'TelephoneNumber',
                  'postalCode' => 'Address',
                  'st' => 'Address',
                  'street' => 'Address',
                  'telephoneNumber' => 'TelephoneNumber'
                );
                
                $cols = array(
                  'facsimileTelephoneNumber' => 'number',
                  'l' => 'locality',
                  'mail' => 'mail',
                  'mobile' => 'number',
                  'postalCode' => 'postal_code',
                  'st' => 'state',
                  'street' => 'line1',
                  'telephoneNumber' => 'number'
                );
                
                // Walk through each role, each of which can have more than one
                $found = false;
                
                foreach($provisioningData['CoPersonRole'] as $r) {
                  if(isset($r[ $mods[$attr] ])) {
                    foreach($r[ $mods[$attr] ] as $m) {
                      // If a type is set, make sure it matches
                      if(empty($targetType) || ($targetType == $m['type'])) {
                        // And finally that the attribute itself is set
                        if(!empty($m[ $cols[$attr] ])) {
                          $attributes[$attr][] = $m[ $cols[$attr] ];
                          $found = true;
                        }
                      }
                      
                      if(!$multiple && $found) {
                        break;
                      }
                    }
                    
                    if(!$multiple && $found) {
                      break;
                    }
                  }
                }
                
                if(!$found && $modify) {
                  $attributes[$attr] = array();
                }
                break;
              // Group attributes (cn is covered above)
              case 'description':
                $attributes[$attr] = $provisioningData['CoGroup']['description'];
                break;
              // hasMember and isMember of are both part of the eduMember objectclass, which can apply
              // to both people and group entries. Check what type of data we're working with for both.
              case 'hasMember':
                if($group) {
                  $members = $this->CoLdapProvisionerDn
                                  ->CoGroup
                                  ->CoGroupMember
                                  ->mapCoGroupMembersToIdentifiers($provisioningData['CoGroupMember'],
                                                                   $targetType);
                  
                  if(!empty($members)) {
                    // Unlike member, hasMember is not required. However, like owner, we can't have
                    // an empty list.
                    
                    $attributes[$attr] = $members;
                  } elseif($modify) {
                    // Unless we're modifying an entry, in which case an empty list
                    // says to remove any previous entry
                    $attributes[$attr] = array();
                  }
                }
                break;
              case 'isMemberOf':
                if($person) {
                  foreach($provisioningData['CoGroupMember'] as $gm) {
                    if(isset($gm['member']) && $gm['member']
                       && !empty($gm['CoGroup']['name'])) {
                      $attributes['isMemberOf'][] = $gm['CoGroup']['name'];
                    }
                  }
                  
                  if($modify && empty($attributes[$attr])) {
                    $attributes[$attr] = array();
                  }
                }
                break;
              case 'member':
                $attributes[$attr] = $this->CoLdapProvisionerDn->dnsForMembers($provisioningData['CoGroupMember']);
                if(empty($attributes[$attr])) {
                  // groupofnames requires at least one member
                  throw new UnderflowException('member');
                }
                break;
              case 'owner':
                $owners = $this->CoLdapProvisionerDn->dnsForOwners($provisioningData['CoGroupMember']);
                if(!empty($owners)) {
                  // Can't have an empty owners list (it should either not be present
                  // or have at least one entry)
                  $attributes[$attr] = $owners;
                } elseif($modify) {
                  // Unless we're modifying an entry, in which case an empty list
                  // says to remove any previous entry
                  $attributes[$attr] = array();
                }
                break;
              default:
                throw new InternalErrorException("Unknown attribute: " . $attr);
                break;
            }
          } elseif($modify) {
            // In case this attribute is no longer being exported (but was previously),
            // set an empty value to indicate delete
            $attributes[$attr] = array();
          }
        }
      }
    }
    
    // Make sure the DN values are in the list (check case insensitively, in case
    // the user-entered case used to build the DN doesn't match). First, map the
    // outbound attributes to lowercase.
    
    $lcattributes = array();
    
    foreach(array_keys($attributes) as $a) {
      $lcattributes[strtolower($a)] = $a;
    }
    
    // Now walk through each DN attribute, but only multivalued ones.
    // At the moment we don't check, say cn (which is single valued) even though
    // we probably should.
    
    foreach(array_keys($dnAttributes) as $a) {
      if(is_array($dnAttributes[$a])) {
        // Lowercase the attribute for comparison purposes
        $lca = strtolower($a);
        
        if(isset($lcattributes[$lca])) {
          // Map back to the mixed case version
          $mca = $lcattributes[$lca];
          
          if(empty($attributes[$mca])
             || !in_array($dnAttributes[$a], $attributes[$mca])) {
            // Key isn't set, so store the value
            $attributes[$a][] = $dnAttributes[$a];
          }
        } else {
          // Key isn't set, so store the value
          $attributes[$a][] = $dnAttributes[$a];
        }
      }
    }
    
    return $attributes;
  }
  
  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */
  
  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    // First figure out what to do
    $assigndn = false;
    $delete   = false;
    $add      = false;
    $modify   = false;
    $rename   = false;
    $person   = false;
    $group    = false;
    
    switch($op) {
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonUnexpired:
        // Currently, unexpiration is treated the same as add, but that is subject to change
        $assigndn = true;
        $delete = false;  // Arguably, this should be true to clear out any prior debris
        $add = true;
        $person = true;
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
      case ProvisioningActionEnum::CoPersonExpired:
        // Currently, expiration is treated the same as delete, but that is subject to change
        $assigndn = false;
        $delete = true;
        $add = false;
        $person = true;
        break;
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
        $assigndn = true;
        $delete = true;
        $add = true;
        $person = true;
        break;
      case ProvisioningActionEnum::CoPersonUpdated:
        // An update may cause an existing person to be written to LDAP for the first time
        // or for an unexpectedly removed entry to be replaced
        $assigndn = true;  
        $modify = true;
        $person = true;
        break;
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
        // We don't do anything on grace period
        $person = true;
        break;
      case ProvisioningActionEnum::CoGroupAdded:
        $assigndn = true;
        $delete = false;  // Arguably, this should be true to clear out any prior debris
        $add = true;
        $group = true;
        break;
      case ProvisioningActionEnum::CoGroupDeleted:
        $delete = true;
        $group = true;
        break;
      case ProvisioningActionEnum::CoGroupUpdated:
        $assigndn = true;
        $modify = true;
        $group = true;
        break;
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
        $assigndn = true;
        $delete = true;
        $add = true;
        $group = true;
        break;
      default:
        throw new RuntimeException("Not Implemented");
        break;
    }
    
    if($group) {
      // If this is a group action and no Group DN is defined, or oc_groupofnames is false,
      // then don't try to do anything.
      
      if(!isset($coProvisioningTargetData['CoLdapProvisionerTarget']['group_basedn'])
         || empty($coProvisioningTargetData['CoLdapProvisionerTarget']['group_basedn'])
         || !$coProvisioningTargetData['CoLdapProvisionerTarget']['oc_groupofnames']) {
        return true;
      }
    }
    
    // Next, obtain a DN for this person or group
    
    try {
      $dns = $this->CoLdapProvisionerDn->obtainDn($coProvisioningTargetData,
                                                  $provisioningData,
                                                  $person ? 'person' : 'group',
                                                  $assigndn);
    }
    catch(RuntimeException $e) {
      throw new RuntimeException($e->getMessage());
    }
    
    $dn = $dns['newdn'];
    
    // We might have to handle a rename if the DN changed
    
    if($dns['olddn'] && $dns['newdn'] && ($dns['olddn'] != $dns['newdn'])) {
      $rename = true;
    }
    
    if($add || $modify) {
      // Find out what attributes went into the DN to make sure they got populated into
      // the attribute array
      
      try {
        $dnAttributes = $this->CoLdapProvisionerDn->dnAttributes($coProvisioningTargetData, $dn, $person ? 'person' : 'group');
      }
      catch(RuntimeException $e) {
        throw new RuntimeException($e->getMessage());
      }
      
      // Assemble an LDAP record
      
      try {
        $attributes = $this->assembleAttributes($coProvisioningTargetData, $provisioningData, $modify, $dnAttributes);
      }
      catch(UnderflowException $e) {
        // We have a group with no members. Convert to a delete operation since
        // groupOfNames requires at least one member.
        
        if($group) {
          $add = false;
          $modify = false;
          $delete = true;
        }
      }
    }
    
    // Bind to the server
    
    $cxn = ldap_connect($coProvisioningTargetData['CoLdapProvisionerTarget']['serverurl']);
    
    if(!$cxn) {
      throw new RuntimeException(_txt('er.ldapprovisioner.connect'), 0x5b /*LDAP_CONNECT_ERROR*/);
    }
    
    // Use LDAP v3 (this could perhaps become an option at some point), although note
    // that ldap_rename (used below) *requires* LDAP v3.
    ldap_set_option($cxn, LDAP_OPT_PROTOCOL_VERSION, 3);
    
    if(!@ldap_bind($cxn,
                   $coProvisioningTargetData['CoLdapProvisionerTarget']['binddn'],
                   $coProvisioningTargetData['CoLdapProvisionerTarget']['password'])) {
      throw new RuntimeException(ldap_error($cxn), ldap_errno($cxn));
    }
    
    if($delete) {
      // Delete any previous entry. For now, ignore any error.
      
      if($rename) {
        // If we're also renaming, make sure to delete using the old DN
        @ldap_delete($cxn, $dns['olddn']);
      } else {
        if(!$dns['newdn']) {
          $dn = $dns['olddn'];
        }
        
        @ldap_delete($cxn, $dn);
      }
    }
    
    if($rename
       // Skip this if we're doing a delete and an add, which is basically a rename
       && !($delete && $add)) {
      // Perform the rename operation before we try to do anything else. Note that
      // the old DN is complete while the new DN is relative.
      
      if($person) {
        $basedn = $coProvisioningTargetData['CoLdapProvisionerTarget']['basedn'];
      } else {
        $basedn = $coProvisioningTargetData['CoLdapProvisionerTarget']['group_basedn'];
      }
      
      $newrdn = rtrim(str_replace($basedn, "", $dns['newdn']), " ,");
      
      if(!@ldap_rename($cxn, $dns['olddn'], $newrdn, null, true)) {
        // XXX We should probably try to reset CoLdapProvisionerDn here since we're
        // now inconsistent with LDAP
        
        throw new RuntimeException(ldap_error($cxn), ldap_errno($cxn));
      }
    }
    
    if($modify) {
      if(!$dn) {
        throw new RuntimeException(_txt('er.ldapprovisioner.dn.none', array($provisioningData[($person ? 'CoPerson' : 'CoGroup')]['id'])));
      }
      
      if(!@ldap_mod_replace($cxn, $dn, $attributes)) {
        if(ldap_errno($cxn) == 0x20 /*LDAP_NO_SUCH_OBJECT*/) {
          // Change to an add operation. We call ourselves recursively because
          // we need to recalculate $attributes. Modify wants array() to indicate
          // an empty attribute, whereas Add throws an error if that is the case.
          // As a side effect, we'll rebind to the LDAP server, but this should
          // be a pretty rare event.
          
          $this->provision($coProvisioningTargetData,
                           ($person
                            ? ProvisioningActionEnum::CoPersonAdded
                            : ProvisioningActionEnum::CoGroupAdded),
                           $provisioningData);
        } else {
          throw new RuntimeException(ldap_error($cxn), ldap_errno($cxn));
        }
      }
    }
    
    if($add) {
      // Write a new entry
      
      if(!$dn) {
        throw new RuntimeException(_txt('er.ldapprovisioner.dn.none', array($provisioningData[($person ? 'CoPerson' : 'CoGroup')]['id'])));
      }
      
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
      throw new RuntimeException(ldap_error($cxn) . " (" . $baseDn . ")", ldap_errno($cxn));
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
   * @param  Integer CO Person ID (null if CO Group ID is specified)
   * @param  Integer CO Group ID (null if CO Person ID is specified)
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */
  
  public function status($coProvisioningTargetId, $coPersonId, $coGroupId=null) {
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
          /* We don't use the LDAP timestamp anymore because another process
           * such as Grouper may have updated it (see CO-642).
           * 
          if(!empty($ldapRecord[0]['modifytimestamp'][0])) {
            // Timestamp is formatted 20130223145645Z and needs to be converted
            $ret['timestamp'] = strtotime($ldapRecord[0]['modifytimestamp'][0]);
          }*/
          
          // Get the last provision time from the parent status function
          $pstatus = parent::status($coProvisioningTargetId, $coPersonId, $coGroupId);
          
          if($pstatus['status'] == ProvisioningStatusEnum::Provisioned) {
            $ret['timestamp'] = $pstatus['timestamp'];
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
   * Obtain the list of attributes supported for export.
   *
   * @since  COmanage Registry v0.8
   * @return Array Array of supported attributes
   */
  
  public function supportedAttributes() {
    // Attributes should be listed in the order they are to be rendered in.
    // The outermost key is the object class. If the objectclass is flagged
    // as required => false, it MUST have a corresponding column oc_FOO in
    // the cm_co_ldap_provisioner_targets.
    
    $attributes = array(
      'person' => array(
        'objectclass' => array(
          'required'    => true
        ),
        // RFC4519 requires sn and cn for person
        // For now, CO Person is always attached to preferred name (CO-333)
        'attributes' => array(
          'sn' => array(
            'required'    => true,
            'multiple'    => false
//            'multiple'    => true,
//            'typekey'     => 'en.name',
//            'defaulttype' => NameEnum::Official
          ),
          'cn' => array(
            'required'    => true,
            'multiple'    => false
//            'multiple'    => true,
//            'typekey'     => 'en.name',
//            'defaulttype' => NameEnum::Official
          )
        )
      ),
      'organizationalPerson' => array(
        'objectclass' => array(
          'required'    => true
        ),
        'attributes' => array(
          'title' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'ou' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'telephoneNumber' => array(
            'required'    => false,
            'multiple'    => true,
            'typekey'     => 'en.contact.phone',
            'defaulttype' => ContactEnum::Office
          ),
          'facsimileTelephoneNumber' => array(
            'required'    => false,
            'multiple'    => true,
            'typekey'     => 'en.contact.phone',
            'defaulttype' => ContactEnum::Fax
          ),
          'street' => array(
            'required'    => false,
            'grouping'    => 'address'
          ),
          'l' => array(
            'required'    => false,
            'grouping'    => 'address'
          ),
          'st' => array(
            'required'    => false,
            'grouping'    => 'address'
          ),
          'postalCode' => array(
            'required'    => false,
            'grouping'    => 'address'
          )
        ),
        'groupings' => array(
          'address'     => array (
            'label'       => _txt('fd.address'),
            'multiple'    => true,
            'typekey'     => 'en.contact.address',
            'defaulttype' => ContactEnum::Office
          )
        ),
      ),
      'inetOrgPerson' => array(
        'objectclass' => array(
          'required'    => true
        ),
        'attributes' => array(
          // For now, CO Person is always attached to preferred name (CO-333)
          'givenName' => array(
            'required'    => false,
            'multiple'    => false
//            'multiple'    => true,
//            'typekey'     => 'en.name',
//            'defaulttype' => NameEnum::Official
          ),
          // And since there is only one name, there's no point in supporting displayName
          /* 'displayName' => array(
            'required'    => false,
            'multiple'    => false,
            'typekey'     => 'en.name',
            'defaulttype' => NameEnum::Preferred
          ),*/
          'o' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'mail' => array(
            'required'    => false,
            'multiple'    => true,
            'typekey'     => 'en.contact.mail',
            'defaulttype' => EmailAddressEnum::Official
          ),
          'mobile' => array(
            'required'    => false,
            'multiple'    => true,
            'typekey'     => 'en.contact.phone',
            'defaulttype' => ContactEnum::Mobile
          ),
          'employeeNumber' => array(
            'required'    => false,
            'multiple'    => false,
            'extendedtype' => 'identifier_types',
            'typekey'     => 'en.identifier',
            'defaulttype' => IdentifierEnum::ePPN
          ),
          'uid' => array(
            'required'    => false,
            'multiple'    => false,
            'alloworgvalue' => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::UID
          )
        )
      ),
      'eduPerson' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'eduPersonAffiliation' => array(
            'required'  => false,
            'multiple'  => true
          ),
          'eduPersonPrincipalName' => array(
            'required'  => false,
            'multiple'  => false,
            'alloworgvalue' => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::ePPN
          )
        )
      ),
      'groupOfNames' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'cn' => array(
            'required'    => true,
            'multiple'    => false
          ),
          'member' => array(
            'required'    => true,
            'multiple'    => true
          ),
          'owner' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'description' => array(
            'required'    => false,
            'multiple'    => false
          )
        )
      ),
      'eduMember' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'isMemberOf' => array(
            'required'  => false,
            'multiple'  => true,
            'description' => _txt('pl.ldapprovisioner.attr.ismemberof.desc')
          ),
          'hasMember' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::UID,
            'description' => _txt('pl.ldapprovisioner.attr.hasmember.desc')
          )
        )
      )
    );
    
    return $attributes;
  }
  
  /**
   * Test an LDAP server to verify that the connection available is valid.
   *
   * @since  COmanage Registry v0.8
   * @param  String Server URL
   * @param  String Bind DN
   * @param  String Password
   * @param  String Base DN (People)
   * @param  String Base DN (Group)
   * @return Boolean True if parameters are valid
   * @throws RuntimeException
   */
  
  public function verifyLdapServer($serverUrl, $bindDn, $password, $baseDn, $groupDn=null) {
    $results = $this->queryLdap($serverUrl, $bindDn, $password, $baseDn, "(objectclass=*)", array("dn"));
    
    if(count($results) < 1) {
      throw new RuntimeException(_txt('er.ldapprovisioner.basedn'));
    }
    
    // Check for a Group DN if one is configured
    
    if($groupDn && $groupDn != "") {
      $results = $this->queryLdap($serverUrl, $bindDn, $password, $groupDn, "(objectclass=*)", array("dn"));
      
      if(count($results) < 1) {
        throw new RuntimeException(_txt('er.ldapprovisioner.basedn.gr.none'));
      }
    }
    
    return true;
  }
}
