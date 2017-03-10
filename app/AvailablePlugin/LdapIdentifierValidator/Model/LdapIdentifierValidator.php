<?php
/**
 * COmanage Registry Ldap Identifier Validator Model
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

class LdapIdentifierValidator extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "identifiervalidator";
  
  public $cmPluginInstantiate = true;
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Association rules from this model to other models
  public $belongsTo = array("CoIdentifierValidator");
  
  // Default display field for cake generated views
  public $displayField = "serverurl";
  
  // Validation rules for table elements
  public $validate = array(
    'co_identifier_validator_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
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
      'required' => false,
      'allowEmpty' => true
    ),
    'basedn' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'filter' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @ since COmanage Registry v2.0.0
   * @ return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Query an LDAP server.
   *
   * @since  COmanage Registry v2.0.0
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
    // Based on similar code in CoLdapProvisionerTarget (CO-1320)
    $ret = array();
    
    $cxn = ldap_connect($serverUrl);
    
    if(!$cxn) {
      throw new RuntimeException(_txt('er.ldapsource.connect'), LDAP_CONNECT_ERROR);
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
   * Validate an identifier (which could also be an email address).
   *
   * @since  COmanage Registry v2.0.0
   * @param  String  $identifier            The identifier (or email address) to be validated
   * @param  Array   $coIdentifierValidator CO Identifier Validator configuration
   * @param  Array   $coExtendedType        CO Extended Type configuration describing $identifier
   * @param  Array   $pluginCfg             Configuration information for this plugin, if instantiated
   * @return Boolean True if $identifier is valid and available
   * @throws InvalidArgumentException If $identifier is not of the correct format
   * @throws OverflowException If $identifier is already in use
   */
  
  public function validate($identifier, $coIdentifierValidator, $coExtendedType, $pluginCfg) {
    $filter = sprintf($pluginCfg['filter'], $identifier);
    
    $res = $this->queryLdap($pluginCfg['serverurl'],
                            $pluginCfg['binddn'],
                            $pluginCfg['password'],
                            $pluginCfg['basedn'],
                            $filter);
    
    if(isset($res['count'])) {
      if($res['count'] > 0) {
        throw new OverflowException($coIdentifierValidator['description']);
      }
    } else {
      // throw some error
      throw new RuntimeException(_txt('er.ldapidentifier.nocount'));
    }
    
    // If we made it here we have no objection
    return true;
  }
  
  /**
   * Test an LDAP server to verify that the connection available is valid.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String Server URL
   * @param  String Bind DN
   * @param  String Password
   * @param  String Base DN (People)
   * @return Boolean True if parameters are valid
   * @throws RuntimeException
   */
  
  public function verifyLdapServer($serverUrl, $bindDn, $password, $baseDn) {
    // Based on similar code in CoLdapProvisionerTarget (CO-1320)
    
    $results = $this->queryLdap($serverUrl, $bindDn, $password, $baseDn, "(objectclass=*)", array("dn"));
    
    if(count($results) < 1) {
      throw new RuntimeException(_txt('er.ldapsource.basedn'));
    }
    
    return true;
  }
}
