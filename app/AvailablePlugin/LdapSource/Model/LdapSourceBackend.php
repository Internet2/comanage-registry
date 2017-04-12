<?php
/**
 * COmanage Registry Ldap OrgIdentitySource Backend Model
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

App::uses("OrgIdentitySourceBackend", "Model");

class LdapSourceBackend extends OrgIdentitySourceBackend {
  public $name = "LdapSourceBackend";
  
  protected $groupAttrs = array(
    'ismemberof' => 'ismemberof',
    'memberof' => 'memberof',
    'o' => 'o',
    'ou' => 'ou',
    'title' => 'title'
  );
  
  /**
   * Generate the set of attributes for the IdentitySource that can be used to map
   * to group memberships. The returned array should be of the form key => label,
   * where key is meaningful to the IdentitySource (eg: a number or a field name)
   * and label is the localized string to be displayed to the user. Backends should
   * only return a non-empty array if they wish to take advantage of the automatic
   * group mapping service.
   *
   * @since  COmanage Registry v2.0.0
   * @return Array As specified
   */
  
  public function groupableAttributes() {
    return $this->groupAttrs;
  }
  
  /**
   * Obtain all available records in the IdentitySource, as a list of unique keys
   * (ie: suitable for passing to retrieve()).
   *
   * @since  COmanage Registry v2.0.0
   * @return Array Array of unique keys
   * @throws DomainException If the backend does not support this type of requests
   */
  
  public function inventory() {
    // For now, we simply try to obtain all records of objectclass person.
    // We'll most likely hit a search limit for larger deployments.
    
    $ret = array();
    
    $filter = "(&";
    
    if(!empty($this->pluginCfg['search_filter'])) {
      // Constrain the search with the configured filter
      $filter .= $this->pluginCfg['search_filter'];
    }
    
    $filter .= "(objectclass=person))";
    
// XXX can we reduce queryLdap down to just $filter, and append search_filter there?
    $res = $this->queryLdap($this->pluginCfg['serverurl'],
                            $this->pluginCfg['binddn'],
                            $this->pluginCfg['password'],
                            $this->pluginCfg['basedn'],
                            $filter,
                            array($this->pluginCfg['key_attribute']));
    
    if(!empty($res) && $res['count'] > 0) {
      // Pull out the keys
      
      if($this->pluginCfg['key_attribute'] == 'dn') {
        $ret = Hash::extract($res, '{n}'.'.dn');
      } else {
        $ret = Hash::extract($res, '{n}.'.strtolower($this->pluginCfg['key_attribute']).'.0');
      }
    }
    
    return $ret;
  }
  
  /**
   * Convert a raw result, as from eg retrieve(), into an array of attributes that
   * can be used for group mapping.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String $raw Raw record, as obtained via retrieve()
   * @return Array Array, where keys are attribute names and values are lists (arrays) of attributes
   */
  
  public function resultToGroups($raw) {
    $ret = array();
    
    // Convert the raw string back to an array
    $attrs = json_decode($raw, true);
    
    foreach(array_keys($this->groupAttrs) as $gAttr) {
      if(!empty($attrs[$gAttr])) {
        $ret[$gAttr] = $attrs[$gAttr];
        unset($ret[$gAttr]['count']);
      }
    }
    
    return $ret;
  }
  
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $result LDAP Search Result
   * @return Array Org Identity and related models, in the usual format
   */
  
  protected function resultToOrgIdentity($result) {
    $orgdata = array();
    $orgdata['OrgIdentity'] = array();
    
    if(!empty($result['edupersonaffiliation'][0]))
      $orgdata['OrgIdentity']['affiliation'] = $result['edupersonaffiliation'][0];
    if(!empty($result['o'][0]))
      $orgdata['OrgIdentity']['o'] = $result['o'][0];
    if(!empty($result['ou'][0]))
      $orgdata['OrgIdentity']['ou'] = $result['ou'][0];
    if(!empty($result['title'][0]))
      $orgdata['OrgIdentity']['title'] = $result['title'][0];
    
    $orgdata['Name'] = array();
    
    if(!empty($result['givenname'][0]))
      $orgdata['Name'][0]['given'] = $result['givenname'][0];
    if(!empty($result['sn'][0]))
      $orgdata['Name'][0]['family'] = $result['sn'][0];
    $orgdata['Name'][0]['primary_name'] = true;
    $orgdata['Name'][0]['type'] = NameEnum::Official;
    
    $orgdata['EmailAddress'] = array();
    
    if(!empty($result['mail'][0])) {
      $orgdata['EmailAddress'][0]['mail'] = $result['mail'][0];
      $orgdata['EmailAddress'][0]['type'] = EmailAddressEnum::Official;
      $orgdata['EmailAddress'][0]['verified'] = true;
    }
    
    $orgdata['TelephoneNumber'] = array();
    
    if(!empty($result['telephonenumber'][0])) {
      // XXX should really break this up into components, at least when area code = 1
      // (and in other plugins too -- add a function to split number? RFE?)
      $orgdata['TelephoneNumber'][0]['number'] = $result['telephonenumber'][0];
      $orgdata['TelephoneNumber'][0]['type'] = ContactEnum::Office;
    }
    
    $orgdata['Address'] = array();
    
    if(!empty($result['street'][0])) {
      $orgdata['Address'][0]['street'] = $result['street'][0];
      if(!empty($result['l'][0]))
        $orgdata['Address'][0]['locality'] = $result['l'][0];
      if(!empty($result['st'][0]))
        $orgdata['Address'][0]['state'] = $result['st'][0];
      if(!empty($result['postalcode'][0]))
        $orgdata['Address'][0]['postal_code'] = $result['postalcode'][0];
      $orgdata['Address'][0]['type'] = ContactEnum::Office;
    }
    
    // Collect some identifiers
    if(!empty($result['employeenumber'][0])) {
      $orgdata['Identifier'][] = array(
        'identifier' => $result['employeenumber'][0],
        'login'      => false,
        'status'     => StatusEnum::Active,
        'type'       => IdentifierEnum::Enterprise
      );
    }
    
    // Until CO-1346, we offer an option to pick the attribute that maps to UID
    $uidattr = 'uid';
    
    if(!empty($this->pluginCfg['uid_attr'])) {
      $uidattr = strtolower($this->pluginCfg['uid_attr']);
    }
    
    $ulogin = (isset($this->pluginCfg['uid_attr_login'])
               && $this->pluginCfg['uid_attr_login']);
    
    if(!empty($result[ $uidattr ][0])) {
      $orgdata['Identifier'][] = array(
        'identifier' => $result[ $uidattr ][0],
        'login'      => $ulogin,
        'status'     => StatusEnum::Active,
        'type'       => IdentifierEnum::UID
      );
    }
    
    return $orgdata;
  }
  
  /**
   * Retrieve a single record from the IdentitySource. The return array consists
   * of two entries: 'raw', a string containing the raw record as returned by the
   * IdentitySource backend, and 'orgidentity', the data in OrgIdentity format.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String $id Unique key to identify record
   * @return Array As specified
   * @throws InvalidArgumentException if not found
   * @throws OverflowException if more than one match
   */
  
  public function retrieve($id) {
    // We support dn as a unique key, but it requires different handling.
    $filter = null;
    $dn = null;
    
    if(strtolower($this->pluginCfg['key_attribute']) == 'dn') {
      // It shouldn't be necessary to filter DN
      $dn = $id;
      
      if(!empty($this->pluginCfg['search_filter'])) {
        $filter = $this->pluginCfg['search_filter'];
      }
    } else {
      $filter = "(" . $this->pluginCfg['key_attribute'] . "=" . ldap_escape($id, null, LDAP_ESCAPE_FILTER) . ")";
      
      if(!empty($this->pluginCfg['search_filter'])) {
        // Constrain the search with the configured filter
        $filter = "(&" . $filter . $this->pluginCfg['search_filter'] . ")";
      }
    }
    
    $res = $this->queryLdap($this->pluginCfg['serverurl'],
                            $this->pluginCfg['binddn'],
                            $this->pluginCfg['password'],
                            $this->pluginCfg['basedn'],
                            $filter,
                            null,
                            $dn);
    
    $nres = array();
    $attributes = array();
    
    if(!empty($res['count']) && $res['count'] > 0) {
      if($res['count'] > 1) {
        throw new OverflowException(_txt('er.multiple'));
      }
      
      // Normalize the result array to make it diff'able. We'll drop the numeric
      // indices and sort the array alphabetically.
      
      for($i = 0;$i < $res[0]['count'];$i++) {
        $attr = $res[0][$i];
        
        $nres[$attr] = $res[0][$attr];
      }
      
      $nres['dn'] = $res[0]['dn'];
      
      ksort($nres);
    } else {
      throw new InvalidArgumentException(_txt('er.id.unk-a', array($id)));
    }
    
    return array(
      // JSON_PRETTY_PRINT requires PHP 5.4+ but that's the minimum supported version in 2.0.0
      'raw'         => json_encode($nres, JSON_PRETTY_PRINT),
      'orgidentity' => $this->resultToOrgIdentity($res[0]),
    );
  }
  
  /**
   * Perform a search against the IdentitySource. The returned array should be of
   * the form uniqueId => attributes, where uniqueId is a persistent identifier
   * to obtain the same record and attributes represent an OrgIdentity, including
   * related models.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $attributes Array in key/value format, where key is the same as returned by searchAttributes()
   * @return Array Array of search results, as specified
   */
    
  public function search($attributes) {
    $ret = array();
    
    // We assume multiple search queries are to be AND'd together
    $filter = "(&";
    
    if(!empty($this->pluginCfg['search_filter'])) {
      // Constrain the search with the configured filter
      $filter .= $this->pluginCfg['search_filter'];
    }
    
    foreach($attributes as $k => $v) {
      // Escaping will prevent user supplied wildcards from working.
      // As a compromise, for now we'll always perform prefix searching.
      $filter .= "(" . ldap_escape($k, null, LDAP_ESCAPE_FILTER) . "=" . ldap_escape($v, null, LDAP_ESCAPE_FILTER) . "*)";
    }
    
    $filter .= ")";
    
    $res = $this->queryLdap($this->pluginCfg['serverurl'],
                            $this->pluginCfg['binddn'],
                            $this->pluginCfg['password'],
                            $this->pluginCfg['basedn'],
                            $filter);
    
    if(!empty($res['count'])) {
      $keyAttr = strtolower($this->pluginCfg['key_attribute']);
      
      for($i = 0;$i < $res['count'];$i++) {
        // Use the configured attribute as the unique key.
        // DN is single valued, others are multi-valued.
        
        $ka = null;
        
        if($keyAttr == 'dn') {
          $ka = $res[$i]['dn'];
        } elseif(!empty($res[$i][$keyAttr][0])) {
          $ka = $res[$i][$keyAttr][0];
        }
        
        if($ka) {
          $ret[ $ka ] = $this->resultToOrgIdentity($res[$i]);
        }
        // XXX else we should probably log something here
      }
    }
    
    return $ret;
  }
  
  /**
   * Generate the set of searchable attributes for the IdentitySource.
   * The returned array should be of the form key => label, where key is meaningful
   * to the IdentitySource (eg: a number or a field name) and label is the localized
   * string to be displayed to the user.
   *
   * @since  COmanage Registry v2.0.0
   * @return Array As specified
   */
  
  public function searchableAttributes() {
    return array(
      'givenname' => _txt('fd.name.given'),
      'sn'        => _txt('fd.name.family'),
      'mail'      => _txt('fd.email_address.mail')
    );
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
   * @param  String DN to retrieve, instead of search filter
   * @return Array Search results
   * @throws RuntimeException
   */
  
  protected function queryLdap($serverUrl, $bindDn, $password, $baseDn, $filter, $attributes=null, $retrieveDn=null) {
    // XXX this is copied from CoLdapProvisionerTarget... consolidate (CO-1320)
    // though notice new param
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
    
    if($retrieveDn) {
      $s = @ldap_read($cxn, $retrieveDn, "(objectclass=*)");
      
      if(!$s) {
        throw new RuntimeException(ldap_error($cxn) . " (" . $retrieveDn . ")", ldap_errno($cxn));
      }
    } else {
      // Try to search using base DN; look for any matching object under the base DN
      
      // Why do we have to special case this and not provide $attributes if it is null
      // (vs just providing null on the parameter)? Who knows? For some mysterious reason
      // only PHP programmers are apparently privy to.
      // See https://bugs.php.net/bug.php?id=63299 to be enraptured in a world of mystery.
      
      if($attributes) {
        $s = @ldap_search($cxn, $baseDn, $filter, $attributes);        
      } else {
        $s = @ldap_search($cxn, $baseDn, $filter); //, $attributes);        
      }
      
      if(!$s) {
        throw new RuntimeException(ldap_error($cxn) . " (" . $baseDn . ")" . ldap_errno($cxn));
      }
    }
    
    $ret = ldap_get_entries($cxn, $s);
    
    ldap_unbind($cxn);
    
    return $ret;
  }
}
