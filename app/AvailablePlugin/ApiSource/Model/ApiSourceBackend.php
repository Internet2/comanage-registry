<?php
/**
 * COmanage Registry API OrgIdentitySource Backend Model
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("OrgIdentitySourceBackend", "Model");

class ApiSourceBackend extends OrgIdentitySourceBackend {
  public $name = "ApiSourceBackend";
  
  /**
   * Obtain a list of records changed since $lastStart, through $curStart.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Integer $lastStart Time of start of last request, or 0 if no previous request
   * @param  Integer $curStart  Time of start of current request
   * @return Mixed              Array of SORIDs, or false if not supported
   * @throws RuntimeException
   */

  public function getChangeList($lastStart, $curStart) {
    // We could implement this via last_update, but in practice there's not
    // much use for this call in push mode. In pull mode this might be more useful.
    
    return false;
  }
  
  /**
   * Generate the set of attributes for the IdentitySource that can be used to map
   * to group memberships. The returned array should be of the form key => label,
   * where key is meaningful to the IdentitySource (eg: a number or a field name)
   * and label is the localized string to be displayed to the user. Backends should
   * only return a non-empty array if they wish to take advantage of the automatic
   * group mapping service.
   *
   * @since  COmanage Registry v3.3.0
   * @return Array As specified
   */
  
  public function groupableAttributes() {
    return array();
  }
  
  /**
   * Obtain all available records in the IdentitySource, as a list of unique keys
   * (ie: suitable for passing to retrieve()).
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Array of unique keys
   * @throws DomainException If the backend does not support this type of requests
   */
  
  public function inventory() {
    // We could return all cached records for Push Mode, but then how do we
    // handle Pull Mode, where inventory() could plausibly be a meaningful
    // operation? For now we won't support Push Mode.
    
    throw DomainException('NOT IMPLEMENTED');
  }
  
  /**
   * Convert a raw result, as from eg retrieve(), into an array of attributes that
   * can be used for group mapping.
   *
   * @since  COmanage Registry v3.3.0
   * @param  String $raw Raw record, as obtained via retrieve()
   * @return Array Array, where keys are attribute names and values are lists (arrays) of attributes
   */
  
  public function resultToGroups($raw) {
    return array();
  }
  
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array $result File Search Result
   * @return Array Org Identity and related models, in the usual format
   * @throws InvalidArgumentException
   */
  
  protected function resultToOrgIdentity($result) {
    // First convert the json blob to an array
    $orgdata = array();
    
    $attrs = json_decode($result['ApiSourceRecord']['source_record'], true);
    
    // We don't specifically sanity check types below since if an invalid type
    // is presented validation will fail.
    
    $orgdata['Name'] = array();
    
    if(!empty($attrs['sorAttributes']['names'])) {
      // If no name is provided, OrgIdentitySource::validateOISRecord will complain
      
      foreach($attrs['sorAttributes']['names'] as $name) {
        $n = array();
        
        if(!empty($name['prefix']))
          $n['prefix'] = $name['prefix'];
        if(!empty($attrs['sorAttributes']['names'][0]['given']))
          $n['given'] = $name['given'];
        if(!empty($attrs['sorAttributes']['names'][0]['family']))
          $n['family'] = $name['family'];
        if(!empty($attrs['sorAttributes']['names'][0]['suffix']))
          $n['suffix'] = $name['suffix'];
        if(!empty($attrs['sorAttributes']['names'][0]['type']))
          $n['type'] = $name['type'];
        else
          $n['type'] = NameEnum::Official;
        if(!empty($name['language']))
          $n['language'] = $name['language'];
        $n['primary_name'] = true;
        
        $orgdata['Name'][] = $n;
      }
    }
    
    if(!empty($attrs['sorAttributes']['addresses'])) {
      foreach($attrs['sorAttributes']['addresses'] as $address) {
        $a = array();
        
        if(!empty($address['streetAddress'])) {
          $a['street'] = $address['streetAddress'];
          
          if(!empty($address['room']))
            $a['room'] = $address['room'];
          
          if(!empty($address['locality']))
            $a['locality'] = $address['locality'];
          
          if(!empty($address['region']))
            $a['state'] = $address['region'];
          
          if(!empty($address['postalCode']))
            $a['postal_code'] = $address['postalCode'];
            
          if(!empty($address['country']))
            $a['country'] = $address['country'];
            
          if(!empty($address['type']))
            $a['type'] = $address['type'];
          else
            $a['type'] = ContactEnum::Office;
          
          if(!empty($address['language']))
            $a['language'] = $address['language'];
          
          $orgdata['Address'][] = $a;
        }
      }
    }
    
    if(!empty($attrs['sorAttributes']['affiliation']))
      $orgdata['OrgIdentity']['affiliation'] = $attrs['sorAttributes']['affiliation'];
    else
      $orgdata['OrgIdentity']['affiliation'] = AffiliationEnum::Member;
    
    if(!empty($attrs['sorAttributes']['dateOfBirth'])) {
      // The CIFER spec defines date as ISO 8601 (YYYY-MM-DD) format, which we
      // assume we receive here although we don't immediately try to validate it
      $orgdata['OrgIdentity']['date_of_birth'] = $attrs['sorAttributes']['dateOfBirth'];
    }
    
    if(!empty($attrs['sorAttributes']['department'])) {
      $orgdata['OrgIdentity']['ou'] = $attrs['sorAttributes']['department'];
    }
    
    if(!empty($attrs['sorAttributes']['emailAddresses'])) {
      foreach($attrs['sorAttributes']['emailAddresses'] as $email) {
        $m = array();
        
        if(!empty($email['address'])) {
          $m['mail'] = $email['address'];
          
          if(!empty($email['type']))
            $m['type'] = $email['type'];
          else
            $m['type'] = EmailAddressEnum::Official;
          
          if(!empty($email['verified']) && $email['verified'])
            $m['verified'] = true;
          else
            $m['verified'] = false;
          
          $orgdata['EmailAddress'][] = $m;
        }
      }
    }
    
    if(!empty($attrs['sorAttributes']['identifiers'])) {
      foreach($attrs['sorAttributes']['identifiers'] as $id) {
        if(!empty($id['identifier']) && $id['type']) {
          $orgdata['Identifier'][] = array(
            'identifier' => $id['identifier'],
            'login'      => false,
            'status'     => StatusEnum::Active,
            'type'       => $id['type']
          );
        }
      }
    }
    
    if(!empty($attrs['sorAttributes']['organization'])) {
      $orgdata['OrgIdentity']['o'] = $attrs['sorAttributes']['organization'];
    }
    
    if(!empty($attrs['sorAttributes']['telephoneNumbers'])) {
      foreach($attrs['sorAttributes']['telephoneNumbers'] as $phone) {
        $p = array();
        
        if(!empty($phone['number'])) {
          $p['number'] = $phone['number'];
          
          if(!empty($phone['type']))
            $p['type'] = $phone['type'];
          else
            $p['type'] = ContactEnum::Office;
          
          $orgdata['TelephoneNumber'][] = $p;
        }
      }
    }
    
    if(!empty($attrs['sorAttributes']['title'])) {
      $orgdata['OrgIdentity']['title'] = $attrs['sorAttributes']['title'];
    }
    
    if(!empty($attrs['sorAttributes']['validFrom']))
      $orgdata['OrgIdentity']['valid_from'] = strftime("%F %T", strtotime($attrs['sorAttributes']['validFrom']));
    
    if(!empty($attrs['sorAttributes']['validThrough']))
      $orgdata['OrgIdentity']['valid_through'] = strftime("%F %T", strtotime($attrs['sorAttributes']['validThrough']));
    
    if(!empty($attrs['sorAttributes']['urls'])) {
      foreach($attrs['sorAttributes']['urls'] as $url) {
        if(!empty($url['url']) && $url['type']) {
          $orgdata['Url'][] = array(
            'url' => $url['url'],
            'type' => $url['type']
          );
        }
      }
    }
    
    return $orgdata;
  }
  
  /**
   * Retrieve a single record from the IdentitySource. The return array consists
   * of two entries: 'raw', a string containing the raw record as returned by the
   * IdentitySource backend, and 'orgidentity', the data in OrgIdentity format.
   *
   * @since  COmanage Registry v3.3.0
   * @param  String $id Unique key to identify record
   * @return Array As specified
   * @throws InvalidArgumentException if not found
   * @throws OverflowException if more than one match
   * @throws RuntimeException on backend specific errors
   */
  
  public function retrieve($id) {
    $ret = array();
    
    // We search the cache of existing records (push), but not (currently) a
    // remote URL (pull).
    $ApiSource = ClassRegistry::init('ApiSource.ApiSource');
    $ApiSourceRecord = ClassRegistry::init('ApiSource.ApiSourceRecord');
    
    // Map OIS ID to ApiSource ID
    $apiSourceId = $ApiSource->field('id', array('ApiSource.org_identity_source_id' => $this->pluginCfg['org_identity_source_id']));
    
    if(!$apiSourceId) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.api_sources.1'))));
    }
    
    $args = array();
    $args['conditions']['ApiSourceRecord.sorid'] = $id;
    $args['conditions']['ApiSourceRecord.api_source_id'] = $apiSourceId;
    $args['contain'] = false;

    $result = $ApiSourceRecord->find('first', $args);
    
    if(empty($result)) {
      throw new InvalidArgumentException(_txt('er.id.unk-a', array($id)));
    }
    
    $ret['raw'] = json_encode($result);
    $ret['orgidentity'] = $this->resultToOrgIdentity($result);
    
    return $ret;
  }
  
  /**
   * Perform a search against the IdentitySource. The returned array should be of
   * the form uniqueId => attributes, where uniqueId is a persistent identifier
   * to obtain the same record and attributes represent an OrgIdentity, including
   * related models.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array $attributes Array in key/value format, where key is the same as returned by searchAttributes()
   * @return Array Array of search results, as specified
   */
    
  public function search($attributes) {
    $ret = array();
    
    // We search the cache of existing records (push), but not (currently) a
    // remote URL (pull).
    $ApiSource = ClassRegistry::init('ApiSource.ApiSource');
    $ApiSourceRecord = ClassRegistry::init('ApiSource.ApiSourceRecord');
    
    // Map OIS ID to ApiSource ID
    $apiSourceId = $ApiSource->field('id', array('ApiSource.org_identity_source_id' => $this->pluginCfg['org_identity_source_id']));
    
    if(!$apiSourceId) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.api_sources.1'))));
    }
    
    $args = array();
    $args['conditions']['ApiSourceRecord.sorid'] = $attributes['SORID'];
    $args['conditions']['ApiSourceRecord.api_source_id'] = $apiSourceId;
    $args['contain'] = false;
    
    $results = $ApiSourceRecord->find('all', $args);
    
    foreach($results as $r) {
      $ret[ $r['ApiSourceRecord']['sorid'] ] = $this->resultToOrgIdentity($r);
    }
    
    return $ret;
  }
  
  /**
   * Generate the set of searchable attributes for the IdentitySource.
   * The returned array should be of the form key => label, where key is meaningful
   * to the IdentitySource (eg: a number or a field name) and label is the localized
   * string to be displayed to the user.
   *
   * @since  COmanage Registry v3.3.0
   * @return Array As specified
   */
  
  public function searchableAttributes() {
    // For now we only search on SORID, and only on cached records.

    // We could search the json blob (especially if we could make the RDBMS do it)
    // or call out to the source system (for Pull Mode)...
    
    return array(
      'SORID' => _txt('en.identifier.type', null, IdentifierEnum::SORID)
    );
  }
}
