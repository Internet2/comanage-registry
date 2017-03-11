<?php
/**
 * COmanage Registry netFORUM OrgIdentitySource Backend Model
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
App::uses("NetForumEnterprise", "NetForumSource.Model");
App::uses("NetForumPro", "NetForumSource.Model");

class NetForumSourceBackend extends OrgIdentitySourceBackend {
  public $name = "NetForumSourceBackend";
  
  protected $groupAttrs = array(
    'EventProductCode' => 'Event Product Code',
    'ProductCode' => 'Product Code'
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
   * Instantiate a netFORUM backend.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String $serverUrl netFORUM Server URL
   * @return Object netFORUM backend class
   */
  
  protected function instantiateNetForum($serverUrl) {
    // Instantiate a netforum backend based on the configuration. Currently, the way we
    // do this is by checking the serverurl. If it ends in netforumpro.com, we assume Pro,
    // otherwise enterprise.
    
    $NF = null;
    
    if(preg_match('/.*netforumpro.com$/', $serverUrl)
       || preg_match('/.*netforum.avectra.com$/', $serverUrl)) {
      $NF = new NetForumPro();
    } else {
      $NF = new NetForumEnterprise();
    }
    
    return $NF;
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
    // Not clear if we can implement this... (or should...)
    throw new DomainException("NOT IMPLEMENTED");
  }
  
  /**
   * Query a netFORUM server.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $attributes Attributes to query (ie: searchableAttributes())
   * @return Array Search results
   * @throws RuntimeException
   */
  
  protected function queryNetForum($attributes) {
    $ret = array();
    
    // Establish a connection and authenticate
    
    $NF = $this->instantiateNetForum($this->pluginCfg['serverurl']);
    $NF->connect($this->pluginCfg['serverurl'], $this->pluginCfg['username'], $this->pluginCfg['password']);
    
    // If more than one search attribute was provided, we'll OR the results
    
    if(!empty($attributes['cstkey'])) {
      $ret = array_merge($ret, $NF->queryByCustomerKey($attributes['cstkey'], $this->pluginCfg['query_events']));
    }
    
    if(!empty($attributes['mail'])) {
      // This appears to be an "exact" search
      
      // The UAT server injects a . into email addresses to make them undeliverable.
      // As a convenience, if we detect that we are configured against the UAT server
      // we'll inject the dot into the search string so the user doesn't need to.
      
      $searchEmail = $attributes['mail'];
      
      if($this->pluginCfg['serverurl'] == 'https://uat.netforumpro.com') {
        $searchEmail = str_replace('@', '@.', $attributes['mail']);
      }
      
      $ret = array_merge($ret, $NF->queryByEmail($searchEmail));
    }
    
    if(!empty($attributes['cn'])) {
      $ret = array_merge($ret, $NF->queryByName($attributes['cn']));
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
    
    // Convert the raw string back to an XML object
    $attrs = simplexml_load_string($raw);
    
    foreach(array_keys($this->groupAttrs) as $gAttr) {
      if(!empty($attrs->$gAttr)) {
        $ret[$gAttr][] = (string)$attrs->$gAttr;
      }
    }
    
    // Also check Events, if not empty
    if(!empty($attrs->Events->EventProductCode)) {
      $ret['EventProductCode'] = $attrs->Events->EventProductCode;
    }
    
    return $ret;
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
    $ret = $this->queryNetForum(array('cstkey' => $id));
    
    if(empty($ret)) {
      throw new InvalidArgumentException(_txt('er.id.unk-a', array($id)));
    }
    
    // The returned value should always be keyed on the ID we sent
    return $ret[$id];
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
    return $this->queryNetForum($attributes);
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
      'cn'        => _txt('pl.netforumsource.name.sort'),
      'mail'      => _txt('fd.email_address.mail')
    );
  }

  /**
   * Verify that the connection information is valid.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String Server URL
   * @param  String Username
   * @param  String Password
   * @return Boolean True if parameters are valid
   * @throws RuntimeException
   */
  
  public function verifyNetForumServer($serverUrl, $username, $password) {
    // Based on similar code in CoLdapProvisionerTarget
    
    $NF = $this->instantiateNetForum($serverUrl);
    $NF->connect($serverUrl, $username, $password);
    
    return true;
  }
}
