<?php
/**
 * COmanage Registry Env OrgIdentitySource Backend Model
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("OrgIdentitySourceBackend", "Model");

class EnvSourceBackend extends OrgIdentitySourceBackend {
  public $name = "EnvSourceBackend";
  
  /**
   * Generate the set of attributes for the IdentitySource that can be used to map
   * to group memberships. The returned array should be of the form key => label,
   * where key is meaningful to the IdentitySource (eg: a number or a field name)
   * and label is the localized string to be displayed to the user. Backends should
   * only return a non-empty array if they wish to take advantage of the automatic
   * group mapping service.
   *
   * @since  COmanage Registry v3.1.0
   * @return Array As specified
   */
  
  public function groupableAttributes() {
    return array();
  }
  
  /**
   * Obtain all available records in the IdentitySource, as a list of unique keys
   * (ie: suitable for passing to retrieve()).
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Array of unique keys
   * @throws DomainException If the backend does not support this type of requests
   */
  
  public function inventory() {
    throw new DomainException(_txt('in.ois.noinventory'));
  }
  
  /**
   * Obtain an environment variable value if set, or a default value if not.
   *
   * @since  COmanage Registry v3.1.0
   * @param  String $env Variable to examine
   * @param  String $default Default value, if $env not set
   * @return String Value or default
   */
  
  protected function maybeGetEnv($env, $default=null) {
    if(!empty($this->pluginCfg[$env])
       && getenv($this->pluginCfg[$env])) {
      return getenv($this->pluginCfg[$env]);
    }
    
    return $default;
  }
  
  /**
   * Convert a raw result, as from eg retrieve(), into an array of attributes that
   * can be used for group mapping.
   *
   * @since  COmanage Registry v3.1.0
   * @param  String $raw Raw record, as obtained via retrieve()
   * @return Array Array, where keys are attribute names and values are lists (arrays) of attributes
   */
  
  public function resultToGroups($raw) {
    return array();
  }
  
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array $result File Search Result
   * @return Array Org Identity and related models, in the usual format
   */
  
  protected function resultToOrgIdentity($result) {
    $orgdata = array();
    $orgdata['OrgIdentity'] = array();
    
    $orgdata['OrgIdentity']['affiliation'] = $result['env_affiliation'];
    
    $orgdata['OrgIdentity']['o'] = $result['env_o'];
    $orgdata['OrgIdentity']['ou'] = $result['env_ou'];
    $orgdata['OrgIdentity']['title'] = $result['env_title'];
    
    $orgdata['Name'] = array();
    
    $orgdata['Name'][0]['honorific'] = $result['env_name_honorific'];
    $orgdata['Name'][0]['given'] = $result['env_name_given'];
    $orgdata['Name'][0]['middle'] = $result['env_name_middle'];
    $orgdata['Name'][0]['family'] = $result['env_name_family'];
    $orgdata['Name'][0]['suffix'] = $result['env_name_suffix'];
    $orgdata['Name'][0]['primary_name'] = true;
    $orgdata['Name'][0]['type'] = NameEnum::Official;
    
    // We need a Name in order to save an OrgIdentity, but we may not get one since
    // some IdPs don't release meaningful attributes. So we create default values.
    
    if(empty($orgdata['Name'][0]['given'])) {
      // For now we only check given, though it's possible we only received a
      // given name but the current configuration requires both given and family.
    
      // The only thing we can guarantee is SORID
      $orgdata['Name'][0]['given'] = $result['env_identifier_sorid'];
    
      // Populate a default last name in case it's required.
      $orgdata['Name'][0]['family'] = _txt('pl.envsource.name.unknown');
    }
    
    $orgdata['EmailAddress'] = array();
    
    if($result['env_mail']) {
      $orgdata['EmailAddress'][0]['mail'] = $result['env_mail'];
      $orgdata['EmailAddress'][0]['type'] = EmailAddressEnum::Official;
      $orgdata['EmailAddress'][0]['verified'] = true;
    }
    
    $orgdata['Address'] = array();
    
    if(!empty($result['env_address_street'])) {
      $orgdata['Address'][0]['street'] = $result['env_address_street'];
      $orgdata['Address'][0]['locality'] = $result['env_address_locality'];
      $orgdata['Address'][0]['state'] = $result['env_address_state'];
      $orgdata['Address'][0]['postal_code'] = $result['env_address_postalcode'];
      $orgdata['Address'][0]['country'] = $result['env_address_country'];
      $orgdata['Address'][0]['type'] = ContactEnum::Office;
    }
    
    $orgdata['TelephoneNumber'] = array();
    
    if($result['env_telephone_number']) {
      $orgdata['TelephoneNumber'][0]['number'] = $result['env_telephone_number'];
      $orgdata['TelephoneNumber'][0]['type'] = ContactEnum::Office;
    }
    
    // Collect some identifiers
    
    $idTypes = array(
      'sorid'   => IdentifierEnum::SORID,
      'eppn'    => IdentifierEnum::ePPN,
      'eptid'   => IdentifierEnum::ePTID,
      'epuid'   => IdentifierEnum::ePUID,
      'orcid'   => IdentifierEnum::ORCID,
      'network' => IdentifierEnum::Network
    );
    
    foreach($idTypes as $l => $t) {
      if($result['env_identifier_'.$l]) {
        $orgdata['Identifier'][] = array(
          'identifier' => $result['env_identifier_'.$l],
          'login'      => (isset($result['env_identifier_'.$l.'_login']) && $result['env_identifier_'.$l.'_login']),
          'status'     => StatusEnum::Active,
          'type'       => $t
        );
      }
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
   * @throws RuntimeException on backend specific errors
   */
  
  public function retrieve($id) {
    // We need to implement retrieve so OrgIdentitySource::createOrgIdentity()
    // can call it to obtain the org identity. Since we operate on environment
    // variables, we "retrieve" the record from the environment. However, to
    // avoid confusion when an admin is trying to "retrieve" the current record,
    // we throw an error if $id doesn't match $ENV.
    
    if(empty($this->pluginCfg['env_identifier_sorid'])) {
      throw new RuntimeException(_txt('er.envsource.sorid.cfg'));
    }
    
    $sorid = getenv($this->pluginCfg['env_identifier_sorid']);
    
    if(!$sorid) {
      throw new RuntimeException(_txt('er.envsource.sorid', array($this->pluginCfg['env_identifier_sorid'])));
    }
    
    if($sorid != $id) {
      throw new RuntimeException(_txt('er.envsource.sorid.mismatch', array($this->pluginCfg['env_identifier_sorid'])));
    }
    
    // Note the controller must $use this for it to be available, apparently
    $EnvSource = ClassRegistry::init("EnvSource.EnvSource");
    
    $attrs = $EnvSource->availableAttributes();
    $values = array();
    
    foreach(array_keys($attrs) as $a) {
      $d = null;
      
      if($a == 'env_affiliation') {
        // Default value for affiliation
        $d = AffiliationEnum::Member;
      }
      
      $values[$a] = $this->maybeGetEnv($a, $d);
      
      if(strncmp($a, 'env_identifier_', 15)==0) {
        // Also grab the _login value, which is configured per plugin
        $values[$a.'_login'] = $this->pluginCfg[$a.'_login'];
      }
    }
    
    $ret = array();
    $ret['raw'] = json_encode($values);
    $ret['orgidentity'] = $this->resultToOrgIdentity($values);
    
    return $ret;
  }
  
  /**
   * Perform a search against the IdentitySource. The returned array should be of
   * the form uniqueId => attributes, where uniqueId is a persistent identifier
   * to obtain the same record and attributes represent an OrgIdentity, including
   * related models.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array $attributes Array in key/value format, where key is the same as returned by searchAttributes()
   * @return Array Array of search results, as specified
   */
    
  public function search($attributes) {
    return array();
  }
  
  /**
   * Generate the set of searchable attributes for the IdentitySource.
   * The returned array should be of the form key => label, where key is meaningful
   * to the IdentitySource (eg: a number or a field name) and label is the localized
   * string to be displayed to the user.
   *
   * @since  COmanage Registry v3.1.0
   * @return Array As specified
   */
  
  public function searchableAttributes() {
    return array();
  }
}
