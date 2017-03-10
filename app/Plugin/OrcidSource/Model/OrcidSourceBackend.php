<?php
/**
 * COmanage Registry ORCID OrgIdentitySource Backend Model
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
App::uses('HttpSocket', 'Network/Http');

class OrcidSourceBackend extends OrgIdentitySourceBackend {
  public $name = "OrcidSourceBackend";

  /**
   * Generate an ORCID callback URL.
   *
   * @since  COmanage Registry v2.0.0
   * @return Array URL, in Cake array format
   */
  
  public function callbackUrl($oisid=null) {
    return array(
      'plugin'     => 'orcid_source',
      'controller' => 'orcid_source_co_petitions',
      'action'     => 'selectOrgIdentityAuthenticate'
    );
  }
  
  /**
   * Exchange an authorization code for an access token and ORCID.
   * 
   * @since  COmanage Registry v2.0.0
   * @param  String $redirectUrl Callback URL used for initial request
   * @param  String $clientId ORCID API Client ID
   * @param  String $clientSecret ORCID API Client Secret
   * @param  String $code Access code return by call to /oauth/authorize
   * @return StdObject Object of data as returned by ORCID, including ID and access token
   * @throws RuntimeException
   */
  
  public function exchangeCode($redirectUri, $clientId, $clientSecret, $code) {
    $HttpSocket = new HttpSocket(array(
      // ORCID uses a wildcard cert (*.orcid.org) that trips up hostname validation
      // on PHP <= ~5.5.6. See CO-1428 for more details.
      'ssl_verify_host' => version_compare(PHP_VERSION, '5.6.0', '>=')
    ));

    $params = array(
      'client_id'     => $clientId,
      'client_secret' => $clientSecret,
      'grant_type'    => 'authorization_code',
      'code'          => $code,
      'redirect_uri'  => $redirectUri
    );
    
    $results = $HttpSocket->post(
      $this->orcidUrl('auth') . "/oauth/token",
      $params
    );
    
    $json = json_decode($results->body());

    // We'll get a 200 response on success or failure
    
    if(!empty($json->orcid)) {
      return $json;
    }

    // There should be an error in the response
    throw new RuntimeException(_txt('er.orcidsource.code', array($json->errorDesc->content)));
  }
  
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
    // Not currently supported
    
    return array();
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
    // Syncing all available ORCIDs is not something we should support
    throw new DomainException("NOT IMPLEMENTED");
  }
  
  /**
   * Obtain an access token from an API ID and secret.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String $clientId ORCID API Client ID
   * @param  String $clientSecret ORCID API Client Secret
   * @return String Access token
   * @throws RuntimeException
   */
  
  public function obtainAccessToken($clientId, $clientSecret) {
    $HttpSocket = new HttpSocket(array(
      // ORCID uses a wildcard cert (*.orcid.org) that trips up hostname validation
      // on PHP <= ~5.5.6. See CO-1428 for more details.
      'ssl_verify_host' => version_compare(PHP_VERSION, '5.6.0', '>=')
    ));

    $params = array(
      'client_id'     => $clientId,
      'client_secret' => $clientSecret,
      'scope'         => '/read-public',
      'grant_type'    => 'client_credentials',
    );
      
    $results = $HttpSocket->post(
      $this->orcidUrl() . "/oauth/token",
      $params
    );
    
    if($results->code != 200) {
      // This is probably a JSON blob
      $err = json_decode($results->body);
      throw new RuntimeException($err->error_description);
    }
    
    // We should now have an access token
    $response = json_decode($results->body);
    
    if(!empty($response->access_token)) {
      return $response->access_token;
    }
    
    throw new RuntimeException(_txt('er.orcidsource.token.api'));
  }

  /**
   * Obtain the root URL for the ORCID API.
   *
   * @since  COmanage Registry v2.0.0
   * @param  String $api API type: auth, public, or member
   * @param  String $tier API tier: prod or sandbox
   * @return String URL prefix
   */
  
  public function orcidUrl($api='public', $tier='prod') {
    $orcidUrls = array(
      'auth' => array(
        'prod'    => 'https://orcid.org',
        'sandbox' => 'https://sandbox.orcid.org'
      ),
      'member' => array(
        'prod'    => 'https://api.orcid.org',
        'sandbox' => 'https://api.sandbox.orcid.org'
      ),
      'public' => array(
        'prod'    => 'https://pub.orcid.org',
        'sandbox' => 'https://pub.sandbox.orcid.org'
      )
    );

    return $orcidUrls[$api][$tier];
  }
  
  /**
   * Query the ORCID API.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $attributes Search attributes
   * @return StdClass JSON decoded results
   */
  
  protected function queryOrcidApi($attributes) {
    // First we need to get a search token. Note these tokens do not expire... we could
    // obtain one and then store it in the database (clearing it out when swapping
    // between sandbox and prod, or on any save really).

    // Grab the access token from the config, or throw an error if not found
    if(empty($this->pluginCfg['access_token'])) {
      throw new InvalidArgumentException(_txt('er.orcidsource.token.none'));
    }
    
    $options = array(
      'header' => array(
        'Accept'        => 'application/json',
        'Authorization' => 'Bearer ' . $this->pluginCfg['access_token']
      )
    );
    
    $HttpSocket = new HttpSocket(array(
      // ORCID uses a wildcard cert (*.orcid.org) that trips up hostname validation
      // on PHP <= ~5.5.6. See CO-1428 for more details.
      'ssl_verify_host' => version_compare(PHP_VERSION, '5.6.0', '>=')
    ));
    
    if(isset($attributes['orcid'])) {
      // Retrieve
      
      $searchResults = $HttpSocket->get(
        $this->orcidUrl() . "/v1.2/" . $attributes['orcid'] . "/orcid-bio/",
        null,
        $options
      );
    } else {
      // Search
      
      $searchResults = $HttpSocket->get(
        $this->orcidUrl() . "/v1.2/search/orcid-bio/",
        $attributes,
        $options
      );
    }
    
    if($searchResults->code == 404) {
      // Most likely retrieving an invalid ORCID
      throw new InvalidArgumentException(_txt('er.orcidsource.search', array($searchResults->code)));
    }
    
    if($searchResults->code != 200) {
      // This is probably an RDF blob, which is slightly annoying to parse.
      // Rather than do it properly since we don't parse RDF anywhere else,
      // we return a generic error.
      throw new RuntimeException(_txt('er.orcidsource.search', array($searchResults->code)));
    }

    return json_decode($searchResults->body);
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
    // Not currently supported
    
    return array();
  }
  
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $result ORCID Search Result (orcid-profile)
   * @return Array Org Identity and related models, in the usual format
   */

  protected function resultToOrgIdentity($result) {
    $orgdata = array();
// XXX should map these
// XXX what if more than one attribute?
    $orgdata['OrgIdentity'] = array();

    // Until we have some rules, everyone is a member
    $orgdata['OrgIdentity']['affiliation'] = AffiliationEnum::Member;
    
    // XXX document
    $orgdata['Name'] = array();

    if(!empty($result->{'orcid-bio'}->{'personal-details'}->{'given-names'}->value))
      $orgdata['Name'][0]['given'] = (string)$result->{'orcid-bio'}->{'personal-details'}->{'given-names'}->value;
    if(!empty($result->{'orcid-bio'}->{'personal-details'}->{'family-name'}->value))
      $orgdata['Name'][0]['family'] = (string)$result->{'orcid-bio'}->{'personal-details'}->{'family-name'}->value;
// Populate primary_name and type in the caller instead of here?
    $orgdata['Name'][0]['primary_name'] = true;
// XXX this should be configurable
    $orgdata['Name'][0]['type'] = NameEnum::Official;
    
    $orgdata['Identifier'] = array();
    $orgdata['Identifier'][0]['identifier'] = $result->{'orcid-identifier'}->{'uri'};
    $orgdata['Identifier'][0]['type'] = IdentifierEnum::ORCID;
    $orgdata['Identifier'][0]['login'] = false;
    $orgdata['Identifier'][0]['status'] = StatusEnum::Active;

    // No other attributes supported until we can see them (maybe need member api?)

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
    try {
      $records = $this->queryOrcidApi(array('orcid' => $id));
    }
    catch(InvalidArgumentException $e) {
      throw new InvalidArgumentException(_txt('er.id.unk-a', array($id)));
    }
    
    return array(
      'raw' => json_encode($records->{'orcid-profile'}),
      'orgidentity' => $this->resultToOrgIdentity($records->{'orcid-profile'})
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
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function search($attributes) {
    $ret = array();
    
    if(!isset($attributes['q'])) {
      // For now, we only support free form search (though ORCID does support
      // search by eg email).
      
      return array();
    }
    
    $records = $this->queryOrcidApi($attributes);
    
    // The number of records is in $records->orcid-search-results->[@attributes]->num-found
    // but by default paginates 10 at a time. We can control pagination with query params
    // "start" and "rows", but the OIS search capability doesn't currently understand
    // pagination.
    
    foreach($records->{'orcid-search-results'}->{'orcid-search-result'} as $r) {
      $orcid = (string)$r->{'orcid-profile'}->{'orcid-identifier'}->{'path'};
      
      $ret[ $orcid ] = $this->resultToOrgIdentity($r->{'orcid-profile'});
    }
    
// XXX should verify that $attributes search keys are defined in searchableAttributes()?
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
    // By default, ORCID uses a free form search. It is possible to search on
    // specific fields (eg: email), though for the initial implementation we
    // won't support that.
    
    return array(
      // XXX This really isn't the right language key, we want an fd.*
      'q' => _txt('op.search')
    );
  }
}