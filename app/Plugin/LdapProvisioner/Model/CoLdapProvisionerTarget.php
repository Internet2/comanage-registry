<?php
/**
 * COmanage Registry CO LDAP Provisioner Target Model
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
  
  public $duplicatableModels = array(
    // Must explicitly list this model in the order it should be duplicated
    "CoLdapProvisionerTarget" => array(
      "parent" => "CoProvisioningTarget",
      "fk"     => "co_provisioning_target_id"
    ),
    "CoLdapProvisionerAttribute" => array(
      "parent" => "CoLdapProvisionerTarget",
      "fk"     => "co_ldap_provisioner_target_id"
    ),
    "CoLdapProvisionerAttrGrouping" => array(
      "parent" => "CoLdapProvisionerTarget",
      "fk"     => "co_ldap_provisioner_target_id"
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
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'password' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'dn_attribute_name' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'dn_identifier_type' => array(
      // XXX This should really use a dynamically generated inList
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'basedn' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'group_basedn' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'person_ocs' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'group_ocs' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'scope_suffix' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'unconf_attr_mode' => array(
      'rule' => array('inList', array(LdapProvUnconfAttrEnum::Ignore,
                                      LdapProvUnconfAttrEnum::Remove)),
      'required' => true,
      'allowEmpty' => false
    ),
    'attr_opts' => array(
      'rule' => 'boolean'
    ),
    'oc_eduperson' => array(
      'rule' => 'boolean'
    ),
    'oc_edumember' => array(
      'rule' => 'boolean'
    ),
    'oc_groupofnames' => array(
      'rule' => 'boolean'
    ),
    'oc_posixaccount' => array(
      'rule' => 'boolean'
    ),
    'oc_posixgroup' => array(
      'rule' => 'boolean'
    ),
    'oc_ldappublickey' => array(
      'rule' => 'boolean'
    ),
    'oc_voperson' => array(
      'rule' => 'boolean'
    ),
    'oc_voposixaccount' => array(
      'rule' => 'boolean'
    ),
    'oc_voposixgroup' => array(
      'rule' => 'boolean'
    )
  );
  
  // Cache of schema plugins, populated by supportedAttributes
  protected $plugins = array();
  
  // List of schemas used for groups
  private $groupSchemas = array(
    'eduMember',
    'groupOfNames',
    'posixGroup',
    'voPosixGroup'
  );
  
  /**
   * Assemble attributes for an LDAP record.
   *
   * @since  COmanage Registry v0.8
   * @param  Array                  $coProvisioningTargetData CO Provisioning Target data
   * @param  Array                  $provisioningData         CO Person or CO Group Data used for provisioning
   * @param  Boolean                $modify                   Whether or not this will be for a modify operation
   * @param  Array                  $dns                      DNs as obtained from CoLdapProvisionerDn::obtainDn
   * @param  Array                  $dnAttributes             Attributes used to generate the DN for this person, as returned by CoLdapProvisionerDn::dnAttributes
   * @param  LdapProvUnconfAttrEnum $uam                      How to handle unconfigured attributes
   * @return Array Attribute data suitable for passing to ldap_add, etc
   * @throws UnderflowException
   * @todo   This function is getting a bit long and could use some refactoring
   */
  
  protected function assembleAttributes($coProvisioningTargetData, 
                                        $provisioningData,
                                        $modify,
                                        $dns,
                                        $dnAttributes,
                                        $uam) {
    // First see if we're working with a Group record or a Person record
    $person = isset($provisioningData['CoPerson']['id']);
    $group = isset($provisioningData['CoGroup']['id']);
    
    // Make it easier to see if attribute options are enabled
    $attropts = $coProvisioningTargetData['CoLdapProvisionerTarget']['attr_opts'];
    
    // Pull the attribute configuration
    $args = array();
    $args['conditions']['CoLdapProvisionerAttribute.co_ldap_provisioner_target_id'] = $coProvisioningTargetData['CoLdapProvisionerTarget']['id'];
    $args['contain'] = false;
    
    $cAttrs = $this->CoLdapProvisionerAttribute->find('all', $args);
    
    // Rekey the attributes array on object class and attribute name
    $configuredAttributes = array();
    
    foreach($cAttrs as $a) {
      if(!empty($a['CoLdapProvisionerAttribute']['attribute'])
         && !empty($a['CoLdapProvisionerAttribute']['objectclass'])) {
        $configuredAttributes[ $a['CoLdapProvisionerAttribute']['objectclass'] ][ $a['CoLdapProvisionerAttribute']['attribute'] ] = $a['CoLdapProvisionerAttribute'];
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
    
    // Cached group membership, interim solution for CO-1348 (see below)
    $groupMembers = array();
    
    // Note we don't need to check for inactive status where relevant since
    // ProvisionerBehavior will remove those from the data we get.
    
    foreach(array_keys($supportedAttributes) as $oc) {
      // First see if this objectclass is handled by a plugin
      if(!empty($supportedAttributes[$oc]['plugin'])) {
        if(!$person) {
          // Currently, only CO Person objects are supported for LDAP Schema Plugins.
          // In theory we could support other objects, but so far there hasn't been
          // a use case for this.
          continue;
        }
        
        // Ask the plugin to assemble the attributes for this objectclass for us.
        // First, get a pointer to the plugin model.
        $pmodel = $this->plugins[ $supportedAttributes[$oc]['plugin'] ];
        
        $pattrs = $pmodel->assemblePluginAttributes($configuredAttributes[$oc], $provisioningData);
        
        // Filter out any attributes in $pattrs that are not defined in $configuredAttributes.
        $pattrs = array_intersect_key($pattrs, $configuredAttributes[$oc]);

        // If this is not a modify operation then filter out any array() values.        
        if(!$modify) {
          $pattrs = array_filter($pattrs, function ($attrValue) {
            return !(is_array($attrValue) && empty($attrValue));
          });
        }

        // Merge into the marshalled attributes.
        $attributes = array_merge($attributes, $pattrs);

        // Insert an objectclass
        $attributes['objectclass'][] = $oc;
        
        // Continue the loop (skip the standard processing)
        continue;
      }
      
      // Skip objectclasses that aren't relevant for the sort of data we're working with
      // We special case eduMember since it applies to both people and groups
      if(($person && $oc != 'eduMember' && in_array($oc, $this->groupSchemas))
         || ($group && !in_array($oc, $this->groupSchemas))) {
        continue;
      }
      
      // Skip voPosixAccount and voPosixGroup if attribute options are not enabled
      if(in_array($oc, array('voPosixAccount', 'voPosixGroup')) && !$attropts) {
        continue;
      }
      
      if($group && empty($groupMembers) && in_array($oc, $this->groupSchemas)) {
        // As an interim solution to CO-1348 we'll pull all group members here (since we no longer get them)
        
        $args = array();
        $args['conditions']['CoGroupMember.co_group_id'] = $provisioningData['CoGroup']['id'];
        $args['conditions']['AND'][] = array(
          'OR' => array(
            'CoGroupMember.valid_from IS NULL',
            'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
          )
        );
        $args['conditions']['AND'][] = array(
          'OR' => array(
            'CoGroupMember.valid_through IS NULL',
            'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
          )
        );
        $args['contain'] = false;
        
        $groupMembers = $this->CoLdapProvisionerDn->CoGroup->CoGroupMember->find('all', $args);
      }
      
      // Iterate across objectclasses, looking for those that are required or enabled.
      // While we're doing this, build a list of enabled attributes for crosschecking later.
      $enabledAttributes = array();
      
      if($supportedAttributes[$oc]['objectclass']['required']
         || (isset($coProvisioningTargetData['CoLdapProvisionerTarget']['oc_' . strtolower($oc)])
             && $coProvisioningTargetData['CoLdapProvisionerTarget']['oc_' . strtolower($oc)])) {
        // Within the objectclass, iterate across the supported attributes looking
        // for required or enabled attributes. We need to add at least one $attr
        // before we add $oc to the list of objectclasses.
        
        $attrEmitted = false;
        
        foreach(array_keys($supportedAttributes[$oc]['attributes']) as $attr) {
          // Note we use the same test below in the final cleanup before returning $attributes
          if($supportedAttributes[$oc]['attributes'][$attr]['required']
             || (isset($configuredAttributes[$oc][$attr]['export'])
                 && $configuredAttributes[$oc][$attr]['export'])) {
            $enabledAttributes[] = $attr;
            
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
            if(!$targetType && !empty($configuredAttributes[$oc][$attr]['type'])) {
              $targetType = $configuredAttributes[$oc][$attr]['type'];
            }
            
            // Labeled attribute, used to construct attribute options
            $lattr = $attr;
            $lsattr = null;
            
            switch($attr) {
              // Name attributes
              case 'cn':
                if($group) {
                  // We don't use a switch here for improved readability
                  if($oc == 'groupOfNames') {
                    // Simply use the CO Group name as the cn. Note that because
                    // the DN for the group is always constructed using the
                    // CO Group Name, this value for cn will always be present.
                    $attributes[$lattr] = $provisioningData['CoGroup']['name'];
                  } elseif($oc == 'posixGroup') {
                    // Create a single cn using the Cluster configured for posixAccount,
                    // similar to gidNumber below
                    if($coProvisioningTargetData['CoLdapProvisionerTarget']['oc_posixaccount']
                       && $coProvisioningTargetData['CoLdapProvisionerTarget']['cluster_id']) {
                      // For posixGroup, we require posixAccount to be enabled and a Cluster to be set
                      // (We only really require posixAccount because in the UI that's where Cluster is set)
                      
                      // Find the configured cluster so we know what identifier type to pull
                      $cluster = Hash::extract($provisioningData['UnixClusterGroup'],
                                               '{n}.UnixCluster[cluster_id='.$coProvisioningTargetData['CoLdapProvisionerTarget']['cluster_id'].']');
                      
                      if(!empty($cluster[0]['groupname_type'])) {
                        // Now map the groupname type to an identifier
                        
                        $nameIdentifier = Hash::extract($provisioningData['Identifier'], '{n}[type='.$cluster[0]['groupname_type'].']');
                        
                        if($nameIdentifier) {
                          $attributes[$lattr] = $nameIdentifier[0]['identifier'];
                        }
                      }
                    }
                  } elseif($oc == 'voPosixGroup') {
                    // We've already checked that attribute options are enabled, above,
                    // so for each cluster this group is attached to emit a cn record
                    // corresponding to groupname_type
                    foreach($provisioningData['UnixClusterGroup'] as $ug) {
                      $lsattr = $lattr;
                      
                      // Map cluster to short label via CO Service.
                      $label = $this->CoProvisioningTarget
                                    ->Co
                                    ->CoService
                                    ->mapClusterToLabel($provisioningData['CoGroup']['co_id'],
                                                        $ug['UnixCluster']['cluster_id']);
                      
                      if($label) {
                        $lsattr = $lattr . ";scope-" . $label;
                        
                        // Find the appropriate identifier to use as the group name
                        $nameIdentifier = Hash::extract($provisioningData['Identifier'], '{n}[type='.$ug['UnixCluster']['groupname_type'].']');
                        
                        if($nameIdentifier) {
                          $attributes[$lsattr] = $nameIdentifier[0]['identifier'];
                        }
                      }
                      // XXX else?
                    }
                  }
                  break;
                }
                // else $person, fall through
              case 'givenName':
              case 'sn':
                // Currently only preferred name supported (CO-333)
                
                if($attropts && !empty($provisioningData['PrimaryName']['language'])) {
                  $lattr = $lattr . ";lang-" . $provisioningData['PrimaryName']['language'];
                }
                
                if($attr == 'cn') {
                  $attributes[$lattr] = generateCn($provisioningData['PrimaryName']);
                } else {
                  $f = ($attr == 'givenName' ? 'given' : 'family');
                  
                  // Registry doesn't permit given to be blank, so we can safely
                  // assume we're going to populate it. However, Registry does not
                  // require a family name. The person schema DOES require sn to be
                  // populated, so if we don't have one we have to insert a default
                  // value, which for now will just be a dot (.).
                  
                  if(!empty($provisioningData['PrimaryName'][$f])) {
                    $attributes[$lattr] = $provisioningData['PrimaryName'][$f];
                  } else {
                    $attributes[$lattr] = ".";
                  }
                }
                break;
              case 'displayName':
              case 'eduPersonNickname':
              case 'voPersonAuthorName':
                // Walk through each name
                foreach($provisioningData['Name'] as $n) {
                  $llattr = $lattr;
                  
                  if($attropts && !empty($n['language'])) {
                    $llattr .= ";lang-" . $n['language'];
                  }
                  
                  if(empty($targetType) || ($targetType == $n['type'])) {
                    $attributes[$llattr][] = generateCn($n);
                    
                    if(!$multiple) {
                      // We're only allowed one name in the attribute
                      break;
                    }
                  }
                }
                break;
              // Attributes from CO Person Role
              case 'eduPersonAffiliation':
              case 'eduPersonScopedAffiliation':
              case 'employeeType':
              case 'o':
              case 'ou':
              case 'title':
              case 'voPersonAffiliation':
                // Map the attribute to the column
                $cols = array(
                  'eduPersonAffiliation' => 'affiliation',
                  'eduPersonScopedAffiliation' => 'affiliation',
                  'employeeType' => 'affiliation',
                  'o' => 'o',
                  'ou' => 'ou',
                  'title' => 'title',
                  'voPersonAffiliation' => 'affiliation',
                );
                
                // Walk through each role
                $found = false;
                
                foreach($provisioningData['CoPersonRole'] as $r) {
                  if(!empty($r[ $cols[$attr] ])) {
                    $lrattr = $lattr;
                    
                    if($attropts) {
                      $lrattr = $lattr . ";role-" . $r['id'];
                    }
                    
                    if($attr == 'eduPersonAffiliation'
                       || $attr == 'eduPersonScopedAffiliation') {
                      $affilmap = $this->CoProvisioningTarget->Co->CoExtendedType->affiliationMap($provisioningData['Co']['id']);
                      
                      if(!empty($affilmap[ $r[ $cols[$attr] ]])) {
                        // Append scope, if so configured
                        $scope = '';
                        
                        if($attr == 'eduPersonScopedAffiliation') {
                          if(!empty($coProvisioningTargetData['CoLdapProvisionerTarget']['scope_suffix'])) {
                            $scope = '@' . $coProvisioningTargetData['CoLdapProvisionerTarget']['scope_suffix'];
                          } else {
                            // Don't add this attribute since we don't have a scope
                            continue;
                          }
                        }
                        
                        $attributes[$lrattr][] = $affilmap[ $r[ $cols[$attr] ] ] . $scope;
                      }
                    } else {
                      $attributes[$lrattr][] = $r[ $cols[$attr] ];
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
              case 'eduPersonOrcid':
              case 'eduPersonPrincipalName':
              case 'eduPersonPrincipalNamePrior':
              case 'eduPersonUniqueId':
              case 'employeeNumber':
              case 'labeledURI':
              case 'mail':
              case 'uid':
              case 'voPersonApplicationUID':
              case 'voPersonExternalID':
              case 'voPersonID':
              case 'voPersonSoRID':
                // Map the attribute to the model and column
                $mods = array(
                  'eduPersonOrcid' => 'Identifier',
                  'eduPersonPrincipalName' => 'Identifier',
                  'eduPersonPrincipalNamePrior' => 'Identifier',
                  'eduPersonUniqueId' => 'Identifier',
                  'employeeNumber' => 'Identifier',
                  'labeledURI' => 'Url',
                  'mail' => 'EmailAddress',
                  'uid' => 'Identifier',
                  'voPersonApplicationUID' => 'Identifier',
                  'voPersonExternalID' => 'Identifier',
                  'voPersonID' => 'Identifier',
                  'voPersonSoRID' => 'Identifier'
                );
                
                $cols = array(
                  'eduPersonOrcid' => 'identifier',
                  'eduPersonPrincipalName' => 'identifier',
                  'eduPersonPrincipalNamePrior' => 'identifier',
                  'eduPersonUniqueId' => 'identifier',
                  'employeeNumber' => 'identifier',
                  'labeledURI' => 'url',
                  'mail' => 'mail',
                  'uid' => 'identifier',
                  'voPersonApplicationUID' => 'identifier',
                  'voPersonExternalID' => 'identifier',
                  'voPersonID' => 'identifier',
                  'voPersonSoRID' => 'identifier'
                );
                
                if($attr == 'eduPersonOrcid') {
                  // Force target type to Orcid. Note we don't validate that the value is in
                  // URL format (http://orcid.org/0000-0001-2345-6789) but perhaps we should.
                  $targetType = IdentifierEnum::ORCID;
                }
                
                $scope = '';
                
                if($attr == 'eduPersonUniqueId') {
                  // Append scope if set, skip otherwise
                  if(!empty($coProvisioningTargetData['CoLdapProvisionerTarget']['scope_suffix'])) {
                    $scope = '@' . $coProvisioningTargetData['CoLdapProvisionerTarget']['scope_suffix'];
                  } else {
                    // Don't add this attribute since we don't have a scope
                    break;
                  }
                }
                
                $modelList = null;
                
                if(isset($configuredAttributes[$oc][$attr]['use_org_value'])
                   && $configuredAttributes[$oc][$attr]['use_org_value']) {
                  // Use organizational identity value for this attribute
                  
                  // If there is more than one CoOrgIdentityLink, for attributes
                  // that support multiple values (mail, uid) push them all onto $modelList.
                  // For the others, it's unclear what to do. For now, we'll just
                  // pick the first one.
                  
                  if($attr == 'mail'
                     || $attr == 'uid'
                     || $attr == 'eduPersonOrcid'
                     || $attr == 'eduPersonPrincipalNamePrior') {
                    // Multi-valued
                    
                    // The structure is something like
                    // $provisioningData['CoOrgIdentityLink'][0]['OrgIdentity']['Identifier'][0][identifier]
                    
                    if(isset($provisioningData['CoOrgIdentityLink'])) {
                      foreach($provisioningData['CoOrgIdentityLink'] as $lnk) {
                        if(isset($lnk['OrgIdentity'][ $mods[$attr] ])) {
                          foreach($lnk['OrgIdentity'][ $mods[$attr] ] as $x) {
                            $modelList[] = $x;
                          }
                        }
                      }
                    }
                  } else {
                    // Single valued
                    
                    if(isset($provisioningData['CoOrgIdentityLink'][0]['OrgIdentity'][ $mods[$attr] ])) {
                      // Don't use =& syntax here, it changes $provisioningData
                      $modelList = $provisioningData['CoOrgIdentityLink'][0]['OrgIdentity'][ $mods[$attr] ];
                    }
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
                        // Check for attribute options
                        if($attropts && $attr == 'voPersonApplicationUID') {
                          // Map the identifier type to a service short label.
                          // There can be more than one service linked to a given
                          // identifier type, so we may insert more than one copy
                          // of the attribute (which is fine, as long as the app
                          // labels are different).
                          
                          // XXX it'd be better to pass this with the provisioning data
                          // rather than call it once per identifer, or at least to pull
                          // a map once
                          $labels = $this->CoProvisioningTarget
                                         ->Co
                                         ->CoGroup
                                         ->CoService
                                         ->mapIdentifierToLabels($provisioningData['Co']['id'],
                                                                 $m['type']);
                          
                          if(!empty($labels)) {
                            foreach($labels as $id => $sl) {
                              $lrattr = $lattr . ';app-' . $sl;
                              
                              $attributes[$lrattr][] = $m[ $cols[$attr] ] . $scope;
                            }
                          } else {
                            // There was no matching label, so we won't export the identifier.
                            // $attributes[$attr][] = $m[ $cols[$attr] ] . $scope;
                          }
                        } elseif($attr == 'labeledURI' && !empty($m['description'])) {
                          // Special case for labeledURI, which permits a description to be appended
                          $attributes[$attr][] = $m[ $cols[$attr] ] . " " . $m['description'];
                        } else {
                          $attributes[$attr][] = $m[ $cols[$attr] ] . $scope;
                        }
                        
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
              case 'voPersonApplicationPassword':
                if($attropts && !empty($provisioningData['Password'])) {
                  // We only support application specific passwords when
                  // attribute options are enabled, since it's not clear what
                  // an application specific password means when not tied to
                  // an application.
                  
                  // XXX it'd be better to pass this with the provisioning data
                  // rather than call it once per identifer, or at least to pull
                  // a map once
                  $mappings = $this->CoProvisioningTarget
                                   ->Co
                                   ->CoGroup
                                   ->CoService
                                   ->mapAuthenticators($provisioningData['Co']['id']);
                  
                  if($modify) {
                    // Start with an empty list in case no active passwords
                    $attributes[$attr] = array();
                  }
                  
                  if(!empty($mappings)) {
                    // What groups is this CO Person a member of?
                    $entGroupIds = Hash::extract($provisioningData['CoGroupMember'], '{n}.co_group_id');

                    foreach($provisioningData['Password'] as $up) {
                      // Skip locked passwords
                      if(!isset($up['AuthenticatorStatus']['locked']) || !$up['AuthenticatorStatus']['locked']) {
                        // Walk through the mappings and look for any that match
                        // our current password_authenticator_id. Note that we
                        // might have more than one service for each password.
                        
                        foreach($mappings as $authenticatorId => $m) {
                          // Do we have a short label?
                          if(!empty($m['serviceCfg']['short_label']) 
                             // Is this a PasswordAuthenticator?
                             && $m['pluginType'] == 'PasswordAuthenticator'
                             // Does this PasswordAuthenticator match the one associated with this Password?
                             && $m['cfg']['PasswordAuthenticator']['id'] == $up['password_authenticator_id']
                             // Is there a Group associated with this service?
                             && (!$m['serviceCfg']['co_group_id']
                                 // Is the user in the service group, if there is one?
                                 || in_array($m['serviceCfg']['co_group_id'], $entGroupIds))) {
                            $lrattr = $lattr . ';app-' . $m['serviceCfg']['short_label'];
                          
                          switch($up['password_type']) {
                              // XXX we can't use PasswordAuthenticator's enums in case the plugin isn't installed
                              case 'CR':
                                $attributes[$lrattr][] = '(CRYPT)' . $up['password'];
                                break;
                              case 'SH':
                                $attributes[$lrattr][] = '{SSHA}' . $up['password'];
                                break;
                              default:
                                $attributes[$lrattr][] = $up['password'];
                                break;
                            }
                          }
                        }
                      }
                    }
                  }
                }
                break;   
              case 'voPersonPolicyAgreement':
                if(!$attropts) {
                  $attributes[$attr] = array();
                }

                foreach($provisioningData['CoTAndCAgreement'] as $tc) {
                  if(!empty($tc['agreement_time'])
                     && !empty($tc['CoTermsAndConditions']['url'])
                     && $tc['CoTermsAndConditions']['status'] == SuspendableStatusEnum::Active) {
                    if($attropts) {
                      $lrattr = $lattr . ";time-" . strtotime($tc['agreement_time']);
                      $attributes[$lrattr][] = $tc['CoTermsAndConditions']['url'];
                    } else {
                      $attributes[$attr][] = $tc['CoTermsAndConditions']['url'];
                    }
                  }
                }
                
                if(!$attropts && empty($attributes[$attr]) && !$modify) {
                  // This is the same as the approach using $found, but without an extra variable
                  unset($attributes[$attr]);
                }
                break;
              case 'voPersonStatus':
                $attributes[$attr] = StatusEnum::$to_api[ $provisioningData['CoPerson']['status'] ];
                
                if($attropts) {
                  // If attribute options are enabled, emit person role status as well
                  
                  foreach($provisioningData['CoPersonRole'] as $r) {
                    $lrattr = $lattr . ";role-" . $r['id'];
                    
                    $attributes[$lrattr] = StatusEnum::$to_api[ $r['status'] ];
                  }
                }
                break;
              // Authenticators
              case 'sshPublicKey':
                if($modify) {
                  // Start with an empty list in case no active keys
                  $attributes[$attr] = array();
                }
                foreach($provisioningData['SshKey'] as $sk) {
                  $attributes[$attr][] = $sk['type'] . " " . $sk['skey'] . " " . $sk['comment'];
                }
                break;
              case 'userPassword':
                if($modify) {
                  // Start with an empty list in case no active passwords
                  $attributes[$attr] = array();
                }
                foreach($provisioningData['Password'] as $up) {
                  // Skip locked passwords
                  if(!isset($up['AuthenticatorStatus']['locked']) || !$up['AuthenticatorStatus']['locked']) {
                    // There's probably a better place for this (an enum somewhere?)
                    switch($up['password_type']) {
                      // XXX we can't use PasswordAuthenticator's enums in case the plugin isn't installed
                      // For now we only support Salted SHA 1 as the "least bad" out of the
                      // box option for OpenLDAP.
                      case 'SH':
                        $attributes[$attr][] = '{SSHA}' . $up['password'];
                        break;
                      default:
                        // Silently ignore other types
                        break;
                    }
                  }
                }
                break;
              case 'voPersonCertificateDN':
              case 'voPersonCertificateIssuerDN':
                if(!$attropts) {
                  $attributes[$attr] = array();
                }
                
                foreach($provisioningData['Certificate'] as $cr) {
                  // Skip locked certs
                  if(!isset($cr['AuthenticatorStatus']['locked']) || !$cr['AuthenticatorStatus']['locked']) {
                    $f = ($attr == 'voPersonCertificateDN' ? 'subject_dn' : 'issuer_dn');
                    
                    if($attropts) {
                      $lrattr = $lattr . ";scope-" . $cr['id'];
                      
                      $attributes[$lrattr][] = $cr[$f];
                    } else {
                      $attributes[$attr][] = $cr[$f];
                    }
                  }
                }
                
                if(!$attropts && empty($attributes[$attr]) && !$modify) {
                  // This is the same as the approach using $found, but without an extra variable
                  unset($attributes[$attr]);
                }
                break;
              // Attributes from models attached to CO Person Role
              case 'facsimileTelephoneNumber':
              case 'l':
              case 'mobile':
              case 'postalCode':
              case 'roomNumber':
              case 'st':
              case 'street':
              case 'telephoneNumber':
                // Map the attribute to the model and column
                $mods = array(
                  'facsimileTelephoneNumber' => 'TelephoneNumber',
                  'l' => 'Address',
                  'mobile' => 'TelephoneNumber',
                  'postalCode' => 'Address',
                  'roomNumber' => 'Address',
                  'st' => 'Address',
                  'street' => 'Address',
                  'telephoneNumber' => 'TelephoneNumber'
                );
                
                $cols = array(
                  'facsimileTelephoneNumber' => 'number',
                  'l' => 'locality',
                  'mobile' => 'number',
                  'postalCode' => 'postal_code',
                  'roomNumber' => 'room',
                  'st' => 'state',
                  'street' => 'street',
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
                          // Check for attribute options
                          $lrattr = $lattr;
                          
                          if($attropts) {
                            $lrattr .= ";role-" . $r['id'];
                            
                            if(!empty($m['language'])) {
                              $lrattr .= ";lang-" . $m['language'];
                            }
                          }
                          
                          if($mods[$attr] == 'TelephoneNumber') {
                            // Handle these specially... we want to format the number
                            // from the various components of the record
                            $attributes[$lrattr][] = formatTelephone($m);
                          } else {
                            $attributes[$lrattr][] = $m[ $cols[$attr] ];
                          }
                          
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
                  $attributes[$lattr] = array();
                }
                break;
              // Group attributes (cn is covered above)
              case 'description':
                // A blank description is invalid, so don't populate if empty
                if(!empty($provisioningData['CoGroup']['description'])) {
                  $attributes[$attr] = $provisioningData['CoGroup']['description'];
                }
                break;
              // hasMember and isMember of are both part of the eduMember objectclass, which can apply
              // to both people and group entries. Check what type of data we're working with for both.
              case 'hasMember':
                if($group && !empty($provisioningData['CoGroup']['id'])) {
                  $members = $this->CoLdapProvisionerDn
                                  ->CoGroup
                                  ->CoGroupMember
                                  ->mapCoGroupMembersToIdentifiers($groupMembers, $targetType);
                  
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
                  if(!empty($provisioningData['CoGroupMember'])) {
                    foreach($provisioningData['CoGroupMember'] as $gm) {
                      if(isset($gm['member']) && $gm['member']
                         && !empty($gm['CoGroup']['name'])) {
                        $attributes['isMemberOf'][] = $gm['CoGroup']['name'];
                      }
                    }
                  }
                  
                  if($modify && empty($attributes[$attr])) {
                    $attributes[$attr] = array();
                  }
                }
                break;
              case 'member':
                $attributes[$attr] = $this->CoLdapProvisionerDn->dnsForMembers($groupMembers);
                
                if(empty($attributes[$attr])) {
                  // groupofnames requires at least one member
                  // XXX seems like a better option would be to deprovision the group?
                  throw new UnderflowException('member');
                }
                break;
              case 'owner':
                $owners = $this->CoLdapProvisionerDn->dnsForOwners($groupMembers);
                
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
              // eduPersonEntitlement is based on Group memberships
              case 'eduPersonEntitlement':
                if(!empty($provisioningData['CoGroupMember'])) {
                  $entGroupIds = Hash::extract($provisioningData['CoGroupMember'], '{n}.co_group_id');
                  
                  $attributes[$attr] = $this->CoProvisioningTarget
                                            ->Co
                                            ->CoGroup
                                            ->CoService
                                            ->mapCoGroupsToEntitlements($provisioningData['Co']['id'], $entGroupIds);
                }
                
                if(!$modify && empty($attributes[$attr])) {
                  // Can't have empty values on add
                  unset($attributes[$attr]);
                }
                break;
              // posixAccount and posixGroup attributes
              // As of Registry v3.3.0 we pull these attributes from the configured
              // UnixCluster, or if Attribute Options are enabled potentially from
              // all UnixClusters (if so configured).
              case 'gidNumber':
              case 'voPosixAccountGidNumber':
                if($group) {
                  // This is sort of similar logic to cn for $group.
                  
                  if($oc == 'voPosixGroup') {
                    // We won't get here if !$attropts, so just jump into the loop.
                    foreach($provisioningData['UnixClusterGroup'] as $ug) {
                      $lsattr = $lattr;
                      
                      // Map cluster to short label via CO Service.
                      $label = $this->CoProvisioningTarget
                                    ->Co
                                    ->CoService
                                    ->mapClusterToLabel($provisioningData['CoGroup']['co_id'],
                                                        $ug['UnixCluster']['cluster_id']);
                      
                      if($label) {
                        $lsattr = $lattr . ";scope-" . $label;
                        
                        // Find the appropriate identifier to use as the group id
                        $gidIdentifier = Hash::extract($provisioningData['Identifier'], '{n}[type='.$ug['UnixCluster']['gid_type'].']');
                        
                        if($gidIdentifier) {
                          $attributes[$lsattr] = $gidIdentifier[0]['identifier'];
                        }
                      }
                      // XXX else?
                    }
                  } elseif($coProvisioningTargetData['CoLdapProvisionerTarget']['oc_posixaccount']
                           && $coProvisioningTargetData['CoLdapProvisionerTarget']['cluster_id']) {
                    // For posixGroup, we require posixAccount to be enabled and a Cluster to be set
                    // (We only really require posixAccount because in the UI that's where Cluster is set)
                    
                    // Find the configured cluster so we know what identifier type to pull
                    $cluster = Hash::extract($provisioningData['UnixClusterGroup'],
                                             '{n}.UnixCluster[cluster_id='.$coProvisioningTargetData['CoLdapProvisionerTarget']['cluster_id'].']');
                    
                    if(!empty($cluster[0]['gid_type'])) {
                      // Now map the gid type to an identifier
                      
                      $gidIdentifier = Hash::extract($provisioningData['Identifier'], '{n}[type='.$cluster[0]['gid_type'].']');
                      
                      if($gidIdentifier) {
                        $attributes[$lattr] = $gidIdentifier[0]['identifier'];
                      }
                    }
                  }
                  break;
                }
                // else fall through
              case 'gecos':
              case 'homeDirectory':
              case 'loginShell':
              case 'uidNumber':
              case 'voPosixAccountGecos':
              case 'voPosixAccountHomeDirectory':
              case 'voPosixAccountLoginShell':
              case 'voPosixAccountUidNumber':
                // For voPosixAccount, attribute options must be enabled, and
                // all clusters will be written. For posixAccount, only the
                // requested cluster is written, and attribute options are not used.
                
                // For wacky Cake data model reasons (see construction in ProvisionerBehavior), this is correct
                // $provisioningData['UnixClusterAccount'][0]['UnixClusterAccount']['foo']
                foreach($provisioningData['UnixClusterAccount'] as $ua) {
                  $lsattr = $lattr;
                  
                  if($oc == 'voPosixAccount'
                     || ($coProvisioningTargetData['CoLdapProvisionerTarget']['cluster_id'] 
                         == $ua['UnixCluster']['cluster_id'])) {
                    if($attropts && $oc == 'voPosixAccount') {
                      // Map cluster to short label via CO Service.
                      $label = $this->CoProvisioningTarget
                                    ->Co
                                    ->CoService
                                    ->mapClusterToLabel($provisioningData['Co']['id'],
                                                        $ua['UnixCluster']['cluster_id']);
                      
                      if($label) {
                        $lsattr = $lattr . ";scope-" . $label;
                      }
                    }
                    
                    // A switch within a switch...
                    switch($attr) {
                      case 'gecos':
                      case 'voPosixAccountGecos':
                        $attributes[$lsattr] = $ua['UnixClusterAccount']['gecos'];
                        break;
                      case 'gidNumber':
                      case 'voPosixAccountGidNumber':
                        // Find the Identifier of primary_co_group_id
                        $gidIdentifier = Hash::extract($provisioningData['CoGroupMember'], '{n}.CoGroup[id='.$ua['UnixClusterAccount']['primary_co_group_id'].'].Identifier.{n}[type='.$ua['UnixCluster']['gid_type'].']');
                        $attributes[$lsattr] = $gidIdentifier[0]['identifier'];
                        break;
                      case 'homeDirectory':
                      case 'voPosixAccountHomeDirectory':
                        $attributes[$lsattr] = $ua['UnixClusterAccount']['home_directory'];
                        break;
                      case 'uidNumber':
                      case 'voPosixAccountUidNumber':
                        $attributes[$lsattr] = $ua['UnixClusterAccount']['uid'];
                        break;
                      case 'loginShell':
                      case 'voPosixAccountLoginShell':
                        $attributes[$lsattr] = $ua['UnixClusterAccount']['login_shell'];
                        break;
                    }
                    
                    // If attribute options are not enabled, do not emit more than
                    // one record to avoid interleaving different accounts.
                    if(!$attropts)
                      break;
                  }
                }
                break;
              // posixGroup attributes
              case 'memberUid':
                if($oc == 'posixGroup') {
                  // As above for cn and posixAccount, we use the uid_type associated
                  // with the configured Cluster
                  if($coProvisioningTargetData['CoLdapProvisionerTarget']['oc_posixaccount']
                     && $coProvisioningTargetData['CoLdapProvisionerTarget']['cluster_id']) {
                    // For posixGroup, we require posixAccount to be enabled and a Cluster to be set
                    // (We only really require posixAccount because in the UI that's where Cluster is set)
                    
                    // Find the configured cluster so we know what identifier type to pull
                    $cluster = Hash::extract($provisioningData['UnixClusterGroup'],
                                             '{n}.UnixCluster[cluster_id='.$coProvisioningTargetData['CoLdapProvisionerTarget']['cluster_id'].']');
                    
                    if(!empty($cluster[0]['uid_type'])) {
                      // Now pull the set of identifiers for this UID type
                      $members = $this->CoLdapProvisionerDn
                                      ->CoGroup
                                      ->CoGroupMember
                                      ->mapCoGroupMembersToIdentifiers($groupMembers, $cluster[0]['uid_type']);
                      
                      if(!empty($members)) {
                        // Unlike member, memberUid is not required. However, like owner, we can't have
                        // an empty list.
                        
                        $attributes[$attr] = $members;
                      } elseif($modify) {
                        // Unless we're modifying an entry, in which case an empty list
                        // says to remove any previous entry
                        $attributes[$attr] = array();
                      }
                    }
                  }                  
                } elseif($oc == 'voPosixGroup') {
                  // We've already checked that attribute options are enabled, above,
                  // so for each cluster this group is attached to emit memberUid records
                  // corresponding to uid_type
                  foreach($provisioningData['UnixClusterGroup'] as $ug) {
                    $lsattr = $lattr;
                    
                    // Map cluster to short label via CO Service.
                    $label = $this->CoProvisioningTarget
                                  ->Co
                                  ->CoService
                                  ->mapClusterToLabel($provisioningData['CoGroup']['co_id'],
                                                      $ug['UnixCluster']['cluster_id']);
                    
                    if($label) {
                      $lsattr = $lattr . ";scope-" . $label;
                      
                      // Now pull the set of identifiers for this UID type
                      $members = $this->CoLdapProvisionerDn
                                      ->CoGroup
                                      ->CoGroupMember
                                      ->mapCoGroupMembersToIdentifiers($groupMembers, $ug['UnixCluster']['uid_type']);
                      
                      if(!empty($members)) {
                        // Unlike member, memberUid is not required. However, like owner, we can't have
                        // an empty list.
                        
                        $attributes[$lsattr] = $members;
                      } elseif($modify) {
                        // Unless we're modifying an entry, in which case an empty list
                        // says to remove any previous entry
                        $attributes[$lsattr] = array();
                      }
                    }
                    // XXX else?
                  }
                }
                break;
              // Internal attributes
              case 'pwdAccountLockedTime':
                // Our initial support is simple: set to 000001010000Z for
                // expired or suspended Person status
                if($provisioningData['CoPerson']['status'] == StatusEnum::Expired
                   || $provisioningData['CoPerson']['status'] == StatusEnum::Suspended) {
                  $attributes[$attr] = '000001010000Z';
                } elseif($modify) {
                  $attributes[$attr] = array();
                }
                break;
              default:
                throw new InternalErrorException("Unknown attribute: " . $attr);
                break;
            } // end of attribute switch
          } elseif($modify && $uam == LdapProvUnconfAttrEnum::Remove) {
            // In case this attribute is probably no longer being exported (but was previously),
            // set an empty value to indicate delete. Note there are use cases where this isn't
            // desirable, such as when an attribute is externally managed, or when a server is
            // using an older schema definition, so we let the admin configure this behavior.
            
            // If set to Remove, don't do this for serverInternal attributes since they may not
            // actually be enabled on a given server (we don't currently have a good way to know).
            
            if(!isset($supportedAttributes[$oc]['attributes'][$attr]['serverInternal'])
               || !$supportedAttributes[$oc]['attributes'][$attr]['serverInternal']) {
              $attributes[$attr] = array();
            }
          }
          
          // Check if we emitted anything by comparing the keys for marshalled attributes
          // to the attribute just considered, knowing that we may have added
          // an attribute with a name appended with an option like ';prior'.
          
          if(array_filter($attributes, function ($k) use ($attr, $attributes, $modify) {
             // CO-1914 On modify of multivalued attribute, this could be an empty array
             // indicating no value to emit
                            return (strpos($k, $attr) === 0  && (!$modify || !empty($attributes[$k])));
                          }, ARRAY_FILTER_USE_KEY)) {
            $attrEmitted = true;
          }
        }
        
        // Add $oc to the list of objectclasses if an attribute was emitted, or if
        // the objectclass is required (in which case the LDAP server will likely
        // throw an error if a required attribute is missing).
        
        if($attrEmitted || $supportedAttributes[$oc]['objectclass']['required']) {
          $attributes['objectclass'][] = $oc;
        }
      }
    }
    
    // Add additionally configured objectclasses
    if($group && !empty($coProvisioningTargetData['CoLdapProvisionerTarget']['group_ocs'])) {
      $attributes['objectclass'] = array_merge($attributes['objectclass'],
                                               explode(',', $coProvisioningTargetData['CoLdapProvisionerTarget']['group_ocs']));
    }
    
    if($person && !empty($coProvisioningTargetData['CoLdapProvisionerTarget']['person_ocs'])) {
      $attributes['objectclass'] = array_merge($attributes['objectclass'],
                                               explode(',', $coProvisioningTargetData['CoLdapProvisionerTarget']['person_ocs']));
    }
    
    // Now that we have the attributes, perform some sanity checks and cleanup.
    
    $lcattributes = array();
    
    // Note we're currently expecting $lcattributes to have attr options (step 4, below)
    foreach(array_keys($attributes) as $a) {
      $lcattributes[strtolower($a)] = $a;
    }
    
    // (1) Make sure the DN values are in the list (check case insensitively, in case
    // the user-entered case used to build the DN doesn't match). First, map the
    // outbound attributes to lowercase.
    
    // Walk through each DN attribute, but only multivalued ones.
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
    
    // (2) We can't send the same value twice for multi-valued attributes. For example,
    // eduPersonAffiliation can't have two entries for "staff", though it can have
    // one for "staff" and one for "employee". We'll walk through the multi-valued
    // attributes and remove any duplicate values. (We wouldn't have to do this here
    // if we checked before inserting each value, above, but that would require a
    // fairly large refactoring.)
    
    // (3) While we're here, convert newlines to $ so the attribute doesn't end up
    // base-64 encoded, and also trim leading and trailing whitespace. While
    // normalization will typically handle this, in some cases (normalization
    // disabled, some attributes that are not normalized) we can still end up
    // with extra whitespace, which can be confusing/problematic in LDAP.
    
    foreach(array_keys($attributes) as $a) {
      if(is_array($attributes[$a])) {
        // Multi-valued. The easiest thing to do is reconstruct the array. We can't
        // just use array_unique since we have to compare case-insensitively.
        // (Strictly speaking, we should set case-sensitivity based on the attribute
        // definition.)
        
        // This array is what we'll put back -- we need to preserve case.
        $newa = array();
        
        // This hash is what we'll use to see if there are existing values.
        $h = array();
        
        foreach($attributes[$a] as $v) {
          // Clean up the attribute before checking
          $tv = str_replace("\r\n", "$", trim($v));
          
          if(!isset($h[ strtolower($tv) ])) {
            $newa[] = $tv;
            $h[ strtolower($tv) ] = true;
          }
        }
        
        $attributes[$a] = $newa;
      } else {
        $attributes[$a] = str_replace("\r\n", "$", $attributes[$a]);
      }
    }
    
    // (4) If this is a modify operation and attribute options are enabled,
    // it's more complicated to clean our no longer valid values. For example,
    // if someone adds a language tag to an attribute that didn't previously
    // have one, the attribute is effectively renamed from, say, "cn" to "cn;lang-es",
    // To deal with this, we pull the current LDAP record.
    
    // Lowercase the list of attributes configured for export
    $sattributes = array_map('strtolower', $enabledAttributes);
    
    if($modify && $attropts) {
      try {
        $currec = $this->queryLdap($coProvisioningTargetData['CoLdapProvisionerTarget']['serverurl'],
                                   $coProvisioningTargetData['CoLdapProvisionerTarget']['binddn'],
                                   $coProvisioningTargetData['CoLdapProvisionerTarget']['password'],
                                   // We need the DN, which provision() already assemebled. Specifically,
                                   // if there is a rename in progress we need the old dn since that's still
                                   // what physically in the LDAP server. (The rename hasn't happened yet.)
                                   $dns['olddn'],
                                   "(objectclass=*)");
      }
      catch(Exception $e) {
        if($e->getCode() == 32) { // LDAP_NO_SUCH_OBJECT
          // No such object, maybe it was manually removed?
          // We'll continue processing as if !$modify
        } else {
          // Rethrow the exception
          throw new RuntimeException($e->getMessage());
        }
      }
      
      if(!empty($currec)) {
        if($currec['count'] != 1) {
          throw new RuntimeException(_txt('er.ldapprovisioner.basedn'));
        }
        
        for($i = 0;$i < $currec[0]['count'];$i++) {
          $fattr = $currec[0][$i]; // eg: cn;lang-es
          $cattr = substr($fattr, 0, strpos($fattr, ';')); // eg: cn
          // If there is no ; in $fattr, substr will return an empty string
          if(!$cattr) { $cattr = $fattr; }
          
          // Is this attribute (currently in LDAP) not in our export?
          if(!isset($lcattributes[strtolower($fattr)])
             // And is it an attribute we are configured to manage/export?
             && in_array(strtolower($cattr), $sattributes)) {
            // Insert a blank record for this attribute to delete it
            $attributes[$fattr] = array();
          }
        }
      }
    }
    
    return $attributes;
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.2.0
   */

  public function beforeSave($options = array()) {
    // Verify that a scope is defined (scope_suffix) if an attribute requiring scope
    // is defined (eduPersonScopedAffiliation, eduPersonUniqueId).
    
    // Only do this check if no scope is set.
    if(empty($this->data['CoLdapProvisionerTarget']['scope_suffix'])) {
      $attrs = $this->supportedAttributes();
      
      // Walk through the list of attributes looking for those that require scope.
      // If we find any that are enabled for export, throw an error.
      
      foreach($attrs as $oc => $occfg) {
        // Check that the objectclass is enabled
        $occol = "oc_" . strtolower($oc);
        
        if($occfg['objectclass']['required']
           // If !isset, it's probably a plugin schema, which is always enabled
           || !isset($this->data['CoLdapProvisionerTarget'][$occol])
           || $this->data['CoLdapProvisionerTarget'][$occol]) {
          foreach($occfg['attributes'] as $attr => $acfg) {
            if(isset($acfg['requirescope']) && $acfg['requirescope']) {
              // This attribute requires scope, see if it is required or enabled
              $dbCfg = Hash::extract($this->data['CoLdapProvisionerAttribute'], '{n}.CoLdapProvisionerAttribute[attribute='.$attr.']');
              
              if($acfg['required']
                 || (isset($dbCfg[0]['export']) && $dbCfg[0]['export'])) {
                throw new InvalidArgumentException(_txt('er.ldapprovisioner.scope', array($attr)));
              }
            }
          }
        }
      }
    }
    
    // Don't permit groupOfNames and posixGroup to both be enabled, since they're
    // both STRUCTURAL, which means the LDAP server will complain
    if($this->data['CoLdapProvisionerTarget']['oc_groupofnames']
       && $this->data['CoLdapProvisionerTarget']['oc_posixgroup']) {
      throw new InvalidArgumentException(_txt('er.ldapprovisioner.oc.conflict', array('groupOfNames', 'posixGroup')));
    }
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
    $deletedn = false;
    $add      = false;
    $modify   = false;
    $rename   = false;
    $person   = false;
    $group    = false;
    
    if(!empty($provisioningData['CoGroup']['id'])) {
      $group = true;
    }
    
    if(!empty($provisioningData['CoPerson']['id'])) {
      $person = true;
    }
    
    switch($op) {
      case ProvisioningActionEnum::CoPersonAdded:
        // On add, we issue a delete (for housekeeping purposes, it will mostly fail)
        // and then an add. Note that various other operations will be promoted from
        // modify to add if there is no record in LDAP, so don't make this modify.
        $assigndn = true;
        $delete = true;
        $add = true;
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
        // Because of the complexity of how related models are deleted and the
        // provisioner behavior invoked, we do not allow dependent=true to delete
        // the DN. Instead, we manually delete it
        $deletedn = true;
        $assigndn = false;
        $delete = true;
        $add = false;
        $person = true;
        break;
      case ProvisioningActionEnum::AuthenticatorUpdated:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
        // For these actions, there may be an existing record with externally managed
        // attributes that we don't want to change. Treat them all as modifies.
        $assigndn = true;
        $modify = true;
        break;
      case ProvisioningActionEnum::CoPersonExpired:
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        // An update may cause an existing person to be written to LDAP for the first time
        // or for an unexpectedly removed entry to be replaced
        $assigndn = true;  
        $modify = true;
        break;
      case ProvisioningActionEnum::CoGroupAdded:
        $assigndn = true;
        $delete = false;  // Arguably, this should be true to clear out any prior debris
        $add = true;
        break;
      case ProvisioningActionEnum::CoGroupDeleted:
        $delete = true;
        $deletedn = true;
        $group = true;
        break;
      case ProvisioningActionEnum::CoGroupUpdated:
        $assigndn = true;
        $modify = true;
        break;
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
        $assigndn = true;
        $delete = true;
        $add = true;
        break;
      default:
        // Ignore all other actions
        return true;
        break;
    }
    
    if($person) {
      if(!empty($provisioningData['CoPerson']['status'])
         && !in_array($provisioningData['CoPerson']['status'],
                      array(StatusEnum::Active,
                            StatusEnum::Expired,
                            StatusEnum::GracePeriod,
                            StatusEnum::Suspended))) {
        // Convert this to a delete operation. Basically we (may) have a record in LDAP,
        // but the person is no longer active. Don't delete the DN though, since
        // the underlying person was not deleted.
        
        $delete = true;
        $add = false;
        $assigndn = false;
        $modify = false;
      }
    }
    
    if($group) {
      // If this is a group action and no Group Base DN is defined, or no appropriate
      // schema is enabled, then don't try to do anything.
      
      if(!isset($coProvisioningTargetData['CoLdapProvisionerTarget']['group_basedn'])
         || empty($coProvisioningTargetData['CoLdapProvisionerTarget']['group_basedn'])) {
        return true;
      }
      
      $found = false;
      
      foreach($this->groupSchemas as $s) {
        if($coProvisioningTargetData['CoLdapProvisionerTarget']['oc_'.strtolower($s)]) {
          // We have at least one schema enabled
          $found = true;
          break;
        }
      }
      
      if(!$found) {
        return false;
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
      // This mostly never matches because $dns['newdnerr'] will usually be set
      throw new RuntimeException($e->getMessage());
    }
    
    if($person
       && $assigndn
       && !$dns['newdn']
       && (!isset($provisioningData['CoPerson']['status'])
           || $provisioningData['CoPerson']['status'] != StatusEnum::Active)) {
      // If a Person is not active and we were unable to create a new DN (or recalculate
      // what it should be), fail silently. This will typically happen when a new Petition
      // is created and the Person is not yet Active (and therefore has no identifiers assigned).
      
      return true;
    }
    
    // We might have to handle a rename if the DN changed
    
    if($dns['olddn'] && $dns['newdn'] && ($dns['olddn'] != $dns['newdn'])) {
      $rename = true;
    }
    
    if($dns['newdn'] && ($add || $modify)) {
      // Find out what attributes went into the DN to make sure they got populated into
      // the attribute array
      
      try {
        $dnAttributes = $this->CoLdapProvisionerDn->dnAttributes($coProvisioningTargetData,
                                                                 $dns['newdn'],
                                                                 $person ? 'person' : 'group');
      }
      catch(RuntimeException $e) {
        throw new RuntimeException($e->getMessage());
      }
      
      // Assemble an LDAP record
      
      try {
        // What is our unconfigured attribute mode?
        $uam = !empty($coProvisioningTargetData['CoLdapProvisionerTarget']['unconf_attr_mode'])
               ? $coProvisioningTargetData['CoLdapProvisionerTarget']['unconf_attr_mode']
               : LdapProvUnconfAttrEnum::Remove;
        
        $attributes = $this->assembleAttributes($coProvisioningTargetData,
                                                $provisioningData,
                                                $modify,
                                                $dns,
                                                $dnAttributes,
                                                $uam);
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
      // Let other errors bubble up the stack
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
      
      if($rename || !$dns['newdn']) {
        // Use the old DN if we're renaming or if there is no new DN
        // (which should be the case for a delete operation).
        @ldap_delete($cxn, $dns['olddn']);
      } else {
        // It's actually not clear when we'd get here -- perhaps cleaning up
        // a record that exists in LDAP even though it's new to Registry?
        @ldap_delete($cxn, $dns['newdn']);
      }
      
      if($deletedn) {
        // Delete the old DN from the database. (It's not done via dependency to ensure
        // we have it when we finally delete the record.)
        
        if($dns['olddnid']) {
          $this->CoLdapProvisionerDn->delete($dns['olddnid']);
        }
      }
    }
    
    if($rename
       // Skip this if we're doing a delete and an add, which is basically a rename
       && !($delete && $add)) {
      if(!$dns['newdn']) {
        throw new RuntimeException(_txt('er.ldapprovisioner.dn.none',
                                        array($person ? _txt('ct.co_people.1') : _txt('ct.co_groups.1'),
                                              $provisioningData[($person ? 'CoPerson' : 'CoGroup')]['id'],
                                              $dns['newdnerr'])));
      }
      
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
      if(!$dns['newdn']) {
        throw new RuntimeException(_txt('er.ldapprovisioner.dn.none',
                                        array($person ? _txt('ct.co_people.1') : _txt('ct.co_groups.1'),
                                              $provisioningData[($person ? 'CoPerson' : 'CoGroup')]['id'],
                                              $dns['newdnerr'])));
      }

      if(!@ldap_mod_replace($cxn, $dns['newdn'], $attributes)) {
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
      
      if(!$dns['newdn']) {
        throw new RuntimeException(_txt('er.ldapprovisioner.dn.none',
                                        array($provisioningData[($person ? 'CoPerson' : 'CoGroup')]['id'],
                                              $provisioningData[($person ? 'CoPerson' : 'CoGroup')]['id'],
                                              $dns['newdnerr'])));
      }

      if(!@ldap_add($cxn, $dns['newdn'], $attributes)) {
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
   * Determine the provisioning status of this target.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer $coProvisioningTargetId CO Provisioning Target ID
   * @param  Model   $Model                  Model being queried for status (eg: CoPerson, CoGroup, CoEmailList)
   * @param  Integer $id                     $Model ID to check status for
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   * @throws InvalidArgumentException If $id not found
   * @throws RuntimeException For other errors
   */
  
  public function status($coProvisioningTargetId, $Model, $id) {
    // We currently only support CoPerson and CoGroup
    
    if($Model->name != 'CoPerson' && $Model->name != 'CoGroup') {
      throw new InvalidArgumentException(_txt('er.notimpl'));
    }
    
    $ret = array(
      'status'    => ProvisioningStatusEnum::Unknown,
      'timestamp' => null,
      'comment'   => ""
    );
    
    // Pull the DN for this person, if we have one.
    // Cake appears to correctly figure out the join (because no contain?)
    $args = array();
    $args['conditions']['CoLdapProvisionerDn.' . Inflector::underscore($Model->name) . '_id'] = $id;
    $args['conditions']['CoLdapProvisionerTarget.co_provisioning_target_id'] = $coProvisioningTargetId;
    
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
          $pstatus = parent::status($coProvisioningTargetId, $Model, $id);
          
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
//            'typekey'     => 'en.name.type',
//            'defaulttype' => NameEnum::Official
          ),
          'cn' => array(
            'required'    => true,
            'multiple'    => false
//            'multiple'    => true,
//            'typekey'     => 'en.name.type',
//            'defaulttype' => NameEnum::Official
          ),
          'userPassword' => array(
            'required'    => false,
            'multiple'    => true
          ),
          // This isn't actually defined in an object class, it's part of the
          // server internal schema (if supported), but we don't have a better
          // place to put it
          'pwdAccountLockedTime' => array(
            'required'       => false,
            'multiple'       => false,
            'serverInternal' => true
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
            'extendedtype' => 'telephone_number_types',
            'defaulttype' => ContactEnum::Office
          ),
          'facsimileTelephoneNumber' => array(
            'required'    => false,
            'multiple'    => true,
            'extendedtype' => 'telephone_number_types',
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
            'extendedtype' => 'address_types',
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
          // This isn't true anymore (CO-716)
          'givenName' => array(
            'required'    => false,
            'multiple'    => false
//            'multiple'    => true,
//            'typekey'     => 'en.name.type',
//            'defaulttype' => NameEnum::Official
          ),
          // And since there is only one name, there's no point in supporting displayName
          'displayName' => array(
            'required'    => false,
            'multiple'    => false,
            'typekey'     => 'en.name.type',
            'defaulttype' => NameEnum::Preferred
          ),
          'o' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'labeledURI' => array(
            'required'    => false,
            'multiple'    => true,
            'extendedtype' => 'url_types',
            'defaulttype' => UrlEnum::Official
          ),
          'mail' => array(
            'required'    => false,
            'multiple'    => true,
            'extendedtype' => 'email_address_types',
            'defaulttype' => EmailAddressEnum::Official
          ),
          'mobile' => array(
            'required'    => false,
            'multiple'    => true,
            'extendedtype' => 'telephone_number_types',
            'defaulttype' => ContactEnum::Mobile
          ),
          'employeeNumber' => array(
            'required'    => false,
            'multiple'    => false,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::ePPN
          ),
          'employeeType' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'roomNumber' => array(
            'description' => _txt('pl.ldapprovisioner.attr.roomnumber.desc'),
            'required'    => false,
            'grouping'    => 'address'
          ),
          'uid' => array(
            'required'    => false,
            'multiple'    => true,
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
          'eduPersonEntitlement' => array(
            'required'  => false,
            'multiple'  => true
          ),
          'eduPersonNickname' => array(
            'required'    => false,
            'multiple'    => true,
            'typekey'     => 'en.name.type',
            'defaulttype' => NameEnum::Preferred
          ),
          'eduPersonOrcid' => array(
            'required'  => false,
            'multiple'  => true,
            'alloworgvalue' => true
          ),
          'eduPersonPrincipalName' => array(
            'required'  => false,
            'multiple'  => false,
            'alloworgvalue' => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::ePPN
          ),
          'eduPersonPrincipalNamePrior' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::ePPN
          ),
          'eduPersonScopedAffiliation' => array(
            'required'  => false,
            'multiple'  => true,
            'requirescope' => true
          ),
          'eduPersonUniqueId' => array(
            'required'  => false,
            'multiple'  => false,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::Enterprise,
            'requirescope' => true
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
      ),
      'posixAccount' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'uidNumber' => array(
            'required'   => true,
            'multiple'   => false
          ),
          'gidNumber' => array(
            'required'   => true,
            'multiple'   => false
          ),
          'homeDirectory' => array(
            'required'   => true,
            'multiple'   => false
          ),
          'loginShell' => array(
            'required'   => false,
            'multiple'   => false
          ),
          'gecos' => array(
            'required'   => false,
            'multiple'   => false
          )
        )
      ),
      'posixGroup' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'cn' => array(
            'required'   => true,
            'multiple'   => false
          ),
          'gidNumber' => array(
            'required'   => true,
            'multiple'   => false
          ),
          'memberUid' => array(
            'required'   => false,
            'multiple'   => true
          )
        )
      ),
      'ldapPublicKey' => array(
        'objectclass' => array(
          'required'     => false
        ),
        'attributes' => array(
          'sshPublicKey' => array(
            'required'   => true,
            'multiple'   => true
          )
        )
      ),
      'voPerson' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'voPersonAffiliation' => array(
            'required'   => false,
            'multiple'   => true
          ),
          'voPersonApplicationPassword' => array(
            'required'   => false,
            'multiple'   => true
          ),
          'voPersonApplicationUID' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::UID
          ),
          'voPersonAuthorName' => array(
            'required'    => false,
            'multiple'    => true,
            'typekey'     => 'en.name.type',
            'defaulttype' => NameEnum::Author
          ),
          'voPersonCertificateDN' => array(
            'required'   => false,
            'multiple'   => true
          ),
          'voPersonCertificateIssuerDN' => array(
            'required'   => false,
            'multiple'   => true
          ),
          'voPersonExternalID' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::ePPN
          ),
          'voPersonID' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::Enterprise
          ),
          'voPersonPolicyAgreement' => array(
            'required'   => false,
            'multiple'   => true
          ),
          'voPersonSoRID' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::SORID
          ),
          'voPersonStatus' => array(
            'required'   => false,
            'multiple'   => true
          )
        )
      ),
      'voPosixAccount' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'voPosixAccountUidNumber' => array(
            'required'   => true,
            'multiple'   => true
          ),
          'voPosixAccountGidNumber' => array(
            'required'   => true,
            'multiple'   => true
          ),
          'voPosixAccountHomeDirectory' => array(
            'required'   => true,
            'multiple'   => true
          ),
          'voPosixAccountLoginShell' => array(
            'required'   => false,
            'multiple'   => true
          ),
          'voPosixAccountGecos' => array(
            'required'   => false,
            'multiple'   => true
          )
        )
      ),
      'voPosixGroup' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'cn' => array(
            'required'   => true,
            'multiple'   => true
          ),
          'voPosixAccountGidNumber' => array(
            'required'   => true,
            'multiple'   => true
          ),
          'memberUid' => array(
            'required'   => false,
            'multiple'   => true
          )
        )
      )
    );
    
    // Now check for any schema plugins, and add them to the attribute array.
    // We don't have a concept of ordering these plugins (especially since unlike
    // eg provisioners these plugins aren't explicitly instantiated), so we'll
    // sort them alphabetically for now.
    
    $this->plugins = $this->loadAvailablePlugins('ldapschema');
    
    foreach($this->plugins as $name => $p) {
      // Inject the plugin name into the attribute array so that we know
      // which plugin to call during attribute assembly
      
      $pattrs = $p->attributes;
      
      foreach(array_keys($pattrs) as $pschema) {
        $pattrs[$pschema]['plugin'] = $name;
      }
      
      $attributes = array_merge($attributes, $pattrs);
    }
    
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
