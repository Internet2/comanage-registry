<?php
/**
 * COmanage Registry Novi OrgIdentitySource Backend Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("OrgIdentitySourceBackend", "Model");
App::uses('CoHttpClient', 'Lib');

class NoviSourceBackend extends OrgIdentitySourceBackend {
  public $name = "NoviSourceBackend";
  
  /**
   * Generate the set of attributes for the IdentitySource that can be used to map
   * to group memberships. The returned array should be of the form key => label,
   * where key is meaningful to the IdentitySource (eg: a number or a field name)
   * and label is the localized string to be displayed to the user. Backends should
   * only return a non-empty array if they wish to take advantage of the automatic
   * group mapping service.
   *
   * @since  COmanage Registry v4.1.0
   * @return Array As specified
   */
  
  public function groupableAttributes() {
    return array('group' => _txt('pl.novisource.group'));
  }
  
  /**
   * Obtain all available records in the IdentitySource, as a list of unique keys
   * (ie: suitable for passing to retrieve()).
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Array of unique keys
   * @throws DomainException If the backend does not support this type of requests
   */
  
  public function inventory() {
    // We don't current support this, but ultimately we could if the Registry
    // interface supported pagination.
    
    throw new DomainException(_txt('er.notimpl'));
  }
  
  /**
   * Convert a raw result, as from eg retrieve(), into an array of attributes that
   * can be used for group mapping.
   *
   * @since  COmanage Registry v4.1.0
   * @param  String $raw Raw record, as obtained via retrieve()
   * @return Array Array, where keys are attribute names and values are lists (arrays) of attributes
   */
  
  public function resultToGroups($raw) {
    $ret = array();
    
    $results = json_decode($raw);
    
    // We appear to get a "Groups" object in the response with an entry for
    // each group the subject is a member of.
    
    if(!empty($results->Groups)) {
      foreach($results->Groups as $g) {
        $ret['group'][] = $g->GroupName;
      }
    }
    
    return $ret;
  }
  
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Object $result JSON Search Result
   * @return Array Org Identity and related models, in the usual format
   */
  
  protected function resultToOrgIdentity($result) {
    $orgdata = array();
    $orgdata['OrgIdentity'] = array(
      'affiliation' => AffiliationEnum::Member
    );
    
    if(!empty($result->JobTitle)) {
      $orgdata['OrgIdentity']['title'] = $result->JobTitle;
    }
    
    // There does not appear to be a standard "organization" attribute
    
    $orgdata['Name'][] = array(
      'given'        => $result->FirstName,
      'family'       => $result->LastName,
      'primary_name' => true,
      'type'         => NameEnum::Official
    );
    
    if(!empty($result->Email)) {
      $orgdata['EmailAddress'][] = array(
        'mail'      => $result->Email,
        'type'      => EmailAddressEnum::Official,
        'verified'  => true
      );
    }
    
    if(!empty($result->MemberSince)) {
      $orgdata['OrgIdentity']['valid_from'] = date('Y-m-d H:i:s', strtotime($result->MemberSince));
    }
    
    if(!empty($result->MembershipExpires)) {
      $orgdata['OrgIdentity']['valid_through'] = date('Y-m-d H:i:s', strtotime($result->MembershipExpires));
    }
    
    if(!empty($result->Phone)) {
      $orgdata['TelephoneNumber'][] = array(
        'number' => $result->Phone,
        'type'   => ContactEnum::Office
      );
    }
    
    // Addresses are also available, though for now we won't pull them
    
    return $orgdata;
  }
  
  /**
   * Retrieve a single record from the IdentitySource. The return array consists
   * of two entries: 'raw', a string containing the raw record as returned by the
   * IdentitySource backend, and 'orgidentity', the data in OrgIdentity format.
   *
   * @since  COmanage Registry v4.1.0
   * @param  String $id Unique key to identify record
   * @return Array As specified
   * @throws InvalidArgumentException if not found
   * @throws OverflowException if more than one match
   */
  
  public function retrieve($id) {
    $results = $this->queryNovi(array('id' => $id));
    
    if(empty($results->UniqueID)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('fd.identifier.identifier'), $id)));
    }
    
    // If Active != true, Novi considers the record deleted
    
    if(!$results->Active) {
      throw new InvalidArgumentException(_txt('er.novisource.deleted'));
    }
    
    // If the MemberStatus is not current or grace_period, consider the record
    // expired (or not yet approved)
    
    if(!in_array($results->MemberStatus, array('current', 'grace_period'))) {
      // These messages aren't actually rendered in the UI, maybe the should be?
      // Or just wait for the refactoring in CFM-53
      throw new InvalidArgumentException(_txt('er.novisource.inactive', array($results->MemberStatus)));
    }
    
    return array(
      // JSON_PRETTY_PRINT requires PHP 5.4+ but that's the minimum supported version since 2.0.0
      'raw'         => json_encode($results, JSON_PRETTY_PRINT),
      'orgidentity' => $this->resultToOrgIdentity($results),
    );
  }
  
  /**
   * Perform a search against the IdentitySource. The returned array should be of
   * the form uniqueId => attributes, where uniqueId is a persistent identifier
   * to obtain the same record and attributes represent an OrgIdentity, including
   * related models.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Array $attributes Array in key/value format, where key is the same as returned by searchAttributes()
   * @return Array Array of search results, as specified
   */
    
  public function search($attributes) {
    $ret = array();
    
    // EF OIS uses 'mail', but Novi uses 'email'
    if(!empty($attributes['mail']) && !isset($attributes['email'])) {
      $attributes['email'] = $attributes['mail'];
      unset($attributes['mail']);
    }

    $results = $this->queryNovi($attributes);
    
    if($results->TotalCount > 0) {
      foreach($results->Results as $r) {
        // Track the same behavior as retrieve() for Active && MemberStatus
        if($r->Active && in_array($r->MemberStatus, array('current', 'grace_period'))) {
          $ret[ $r->UniqueID ] = $this->resultToOrgIdentity($r);
        }
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
   * @since  COmanage Registry v4.1.0
   * @return Array As specified
   */
  
  public function searchableAttributes() {
    return array(
      'name'      => _txt('fd.name'),
      'email'     => _txt('fd.email_address.mail')
    );
  }

  /**
   * Query the Novi AMS API.
   *
   * @since  COmanage Registry v4.1.0
   * @param  array $attributes Search query
   * @return array             Search results
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function queryNovi($attributes) {
    // Supported attributes for search are name and email (and also parent name,
    // which we don't currently support). We also support the special attribute
    // 'id' to retrieve a specific record.
    
    if(empty($this->pluginCfg['server_id'])) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.servers.1'))));
    }
    
    $args = array();
    $args['conditions']['Server.id'] = $this->pluginCfg['server_id'];
    $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = array('HttpServer');
    
    $Server = ClassRegistry::init('Server');
    
    $srvr = $Server->find('first', $args);
    
    if(empty($srvr['HttpServer']['id'])) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.servers.1'), $this->pluginCfg['server_id'])));
    }
    
    $Http = new CoHttpClient(array(
      'ssl_verify_peer' => $srvr['HttpServer']['ssl_verify_peer'],
      'ssl_verify_host' => $srvr['HttpServer']['ssl_verify_host']
    ));
    
    $Http->setBaseUrl($srvr['HttpServer']['serverurl']);
    
    $Http->setRequestOptions(array(
      'header' => array(
        // Http->configAuth() will base64 encode password, which isn't what the API expects
        'Authorization' => 'Basic ' . $srvr['HttpServer']['password'],
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json'
      )
    ));
    
    if(!empty($attributes['id'])) {
      $response = $Http->get('/members/' . $attributes['id']);
    } else {
      $response = $Http->get('/members', $attributes);
    }
    
    if($response->code != 200) {
      // XXX can we get a better error?
      throw new RuntimeException($response->reasonPhrase);
    }
    
    return json_decode($response->body);
  }
}
