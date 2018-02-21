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

App::uses("Server", "Model");
App::uses("OrgIdentitySourceBackend", "Model");
App::uses('HttpSocket', 'Network/Http');

class OrcidSourceBackend extends OrgIdentitySourceBackend {
  public $name = "OrcidSourceBackend";

  // Cache the Http connection and server configuration
  protected $Http = null;
  protected $server = null;

  /**
   * Generate an ORCID callback URL. This is used for authenticated ORCID linking
   * (Authorization Code flow), unlike the Oauth2Server token, which is used for
   * administrative searching.
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
   * Establish a connection to the ORCID API.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $serverId Server ID
   * @return Boolean True on success
   * @throws InvalidArgumentException
   */

  protected function orcidConnect($serverId) {
    // Pull the server config
    
    $Server = new Server();
    
    $args = array();
    $args['conditions']['Server.id'] = $serverId;
    $args['contain'] = array('Oauth2Server');
    
    $this->server = $Server->find('first', $args);
    
    if(!$this->server) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.servers.1'), $serverId)));
    }
    
    // Grab the access token from the config, or throw an error if not found
    if(empty($this->server['Oauth2Server']['access_token'])) {
      throw new InvalidArgumentException(_txt('er.orcidsource.token.none'));
    }
    
    $this->Http = new HttpSocket(array(
      // ORCID uses a wildcard cert (*.orcid.org) that trips up hostname validation
      // on PHP <= ~5.5.6. See CO-1428 for more details.
      'ssl_verify_host' => version_compare(PHP_VERSION, '5.6.0', '>=')
    ));
    
    return true;
  }

  /**
   * Make a request to the ORCID API.
   *
   * @since  COmanage Registry v3.2.0
   * @param  String  $urlPath     URL Path to request from API
   * @param  Array   $data        Array of query paramaters
   * @param  String  $action      HTTP action
   * @return Array   Decoded json message body
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function orcidRequest($urlPath, $data=array(), $action="get") {
    $options = array(
      'header' => array(
        'Accept'        => 'application/json',
        'Authorization' => 'Bearer ' . $this->server['Oauth2Server']['access_token'],
        'Content-Type'  => 'application/json'
      )
    );

    $results = $this->Http->$action($this->orcidUrl() . $urlPath,
                                    ($action == 'get' ? $data : json_encode($data)),
                                    $options);

    if($results->code == 404) {
      // Most likely retrieving an invalid ORCID
      throw new InvalidArgumentException(_txt('er.orcidsource.search', array($results->code)));
    }

    if($results->code != 200) {
      // This is probably an RDF blob, which is slightly annoying to parse.
      // Rather than do it properly since we don't parse RDF anywhere else,
      // we return a generic error.
      throw new RuntimeException(_txt('er.orcidsource.search', array($results->code)));
    }

    return json_decode($results->body);
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
   * @param  Array $orcid       ORCID ID
   * @param  Array $orcidresult ORCID Search Result (/person)
   * @return Array Org Identity and related models, in the usual format
   */

  protected function resultToOrgIdentity($orcid, $personresult) {
    // We assume results from /v2.1/ORCID/person. There's potentially a bunch of
    // stuff available in /activities (both /person and /activities can be
    // obtained via /v2.1/ORCID/record) that could potentially be used for
    // groupable attributes, but we don't have a use case for that yet.
    
    $orgdata = array();
// XXX should map these
// XXX what if more than one attribute?
    $orgdata['OrgIdentity'] = array();

    // Until we have some rules, everyone is a member
    $orgdata['OrgIdentity']['affiliation'] = AffiliationEnum::Member;
    
    // XXX document
    $orgdata['Name'] = array();

    if(!empty($personresult->{'name'}->{'given-names'}->value))
      $orgdata['Name'][0]['given'] = (string)$personresult->{'name'}->{'given-names'}->value;
    if(!empty($personresult->{'name'}->{'family-name'}->value))
      $orgdata['Name'][0]['family'] = (string)$personresult->{'name'}->{'family-name'}->value;
// Populate primary_name and type in the caller instead of here?
    $orgdata['Name'][0]['primary_name'] = true;
// XXX this should be configurable
    $orgdata['Name'][0]['type'] = NameEnum::Official;
    
    // Although implied by various attributes in the /person record, the ORCID
    // itself is not an explicit field. The caller already knows it, though, so
    // we just expect it as a parameter.
    $orgdata['Identifier'] = array();
    $orgdata['Identifier'][0]['identifier'] = $orcid;
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
      $this->orcidConnect($this->pluginCfg['server_id']);

      $orcidbio = $this->orcidRequest("/v2.1/" . $id . "/person");
    }
    catch(InvalidArgumentException $e) {
      throw new InvalidArgumentException(_txt('er.id.unk-a', array($id)));
    }
    
    return array(
      'raw' => json_encode($orcidbio),
      'orgidentity' => $this->resultToOrgIdentity($id, $orcidbio)
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
    
    // We just let search exceptions pop up the stack
    
    $this->orcidConnect($this->pluginCfg['server_id']);
    
    $records = $this->orcidRequest("/v2.1/search/", $attributes);
    
    // We can control pagination with query params, but the OIS search capability
    // doesn't currently understand pagination.
    
    if(isset($records->{'num-found'}) && $records->{'num-found'} > 0) {
      foreach($records->result as $rec) {
        if(!empty($rec->{'orcid-identifier'}->{'path'})) {
          $orcid = $rec->{'orcid-identifier'}->{'path'};

          $orcidbio = $this->orcidRequest("/v2.1/" . $orcid . "/person");
          
          if(!empty($orcidbio)) {
            $ret[ $orcid ] = $this->resultToOrgIdentity($orcid, $orcidbio);
          }
        }
      }
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