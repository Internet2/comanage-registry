<?php
/**
 * COmanage Registry Salesforce OrgIdentitySource Backend Model
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
App::uses('HttpSocket', 'Network/Http');

class SalesforceSourceBackend extends OrgIdentitySourceBackend {
  public $name = "SalesforceSourceBackend";

  /**
   * Generate a Salesforce callback URL.
   *
   * @since  COmanage Registry v3.1.0
   * @return Array URL, in Cake array format
   */
  
  public function callbackUrl() {
    return array(
      'plugin'     => 'salesforce_source',
      'controller' => 'salesforce_sources',
      'action'     => 'callback',
      $this->pluginCfg['id']
    );
  }
  
  /**
   * Exchange an authorization code for an access and refresh token.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  String $code Access code return by call to /oauth/authorize
   * @param  String $redirectUrl Callback URL used for initial request
   * @return StdObject Object of data as returned by Salesforce, including access and refresh token
   * @throws RuntimeException
   */
  
  public function exchangeCode($code, $redirectUrl) {
    $HttpSocket = new HttpSocket();

    $params = array(
      'client_id'     => $this->pluginCfg['clientid'],
      'client_secret' => $this->pluginCfg['client_secret'],
      'grant_type'    => 'authorization_code',
      'code'          => $code,
      'redirect_uri'  => $redirectUrl
    );
    
    $postUrl = $this->pluginCfg['serverurl'] . "/services/oauth2/token";
    
    $results = $HttpSocket->post($postUrl, $params);
    
    $json = json_decode($results->body());
    
    if($results->code == 200) {
      return $json;
    }

    // There should be an error in the response
    throw new RuntimeException(_txt('er.salesforcesource.code', array($json->error . ": " . $json->error_description)));
  }
  
  /**
   * Obtain a list of records changed since $lastStart, through $curStart.
   * 
   * @since  COmanage Registry v3.1.0
   * @param  Integer $lastStart Time of start of last request, or 0 if no previous request
   * @param  Integer $curStart  Time of start of current request
   * @return Mixed Array of SORIDs, or false if not supported
   * @throws RuntimeException
   */
  
  public function getChangeList($lastStart, $curStart) {
    $ids = array();
    
    // Determine which objects to query
    
    $sobjects = array();
    
    if($this->pluginCfg['search_contacts']) {
      $sobjects[] = 'Contact';
    }
    
    if($this->pluginCfg['search_users']) {
      $sobjects[] = 'User';
    }
    
    /* It doesn't appear as though we can get updates to custom objects the same way,
     * and even if we did it's not clear if we could easily map them back to the
     * parent user ID.
     *
    if(!empty($this->pluginCfg['custom_objects'])) {
      $objs = explode(',', $this->pluginCfg['custom_objects']);
      
      foreach($objs as $obj) {
        $o = explode(':', $obj);
        $sobjects[] = $o[0];
      }
    }*/

    if(empty($sobjects)) {
      return false;
    }
    
    // Salesforce only supports querying for the last 30 days, so if $lastStart is 0,
    // we need to adjust it. (We set for ~29 days to allow for various time skew issues.)
    
    $attributes = array(
      'start' => date("c", $lastStart ?: (time() - 2510000)),
      'end'   => date("c", $curStart)
    );
    
    foreach($sobjects as $sobject) {
      $attributes['sobject'] = $sobject;
      
      $res = $this->querySalesforceApi($attributes);
      
      if(isset($res->ids)) {
        // Might be empty if no records changed.
        // While we're here, prefix the object type (which we use as part of the source key).
        
        $ids = array_merge($ids, preg_replace('/^/', $sobject.'-', $res->ids));
      } else {
        throw new RuntimeException(_txt('er.reply.unk'));
      }
    }
    
    return $ids;
  }
  
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
    // Use the Salesforce describe API (handily of the same form as an object ID)
    // to obtain the available set of attributes.
    // https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_sobject_describe.htm
    
    // If we already cached them, return the cached results.
    // This cache can be cleared by obtaining a new OAuth token
    // (or manually nulling the value in the database).
    if(!empty($this->pluginCfg['groupable_attrs'])) {
      return json_decode($this->pluginCfg['groupable_attrs'], true);
    }
    
    $attrs = array();
    
    // Take the union of contact and user attributes, if enabled
    if($this->pluginCfg['search_contacts']) {
      $r = $this->querySalesforceApi(array('id' => 'Contact-describe'));
      
      if(!empty($r->fields)) {
        foreach($r->fields as $f) {
          $attrs[ $f->name ] = $f->label;
        }
      }
    }
    
    if($this->pluginCfg['search_users']) {
      $r = $this->querySalesforceApi(array('id' => 'User-describe'));
      
      if(!empty($r->fields)) {
        foreach($r->fields as $f) {
          $attrs[ $f->name ] = $f->label;
        }
      }
    }
    
    // And add any custom objects
    if(!empty($this->pluginCfg['custom_objects'])) {
      $objs = explode(',', $this->pluginCfg['custom_objects']);
      
      foreach($objs as $obj) {
        $o = explode(':', $obj);
        
        $r = $this->querySalesforceApi(array('id' => $o[0] .'-describe'));
        
        foreach($r->fields as $f) {
          $attrs[ $r->name . ':' . $f->name ] = $r->labelPlural . ": " . $f->label;
        }
      }
    }
    
    // Sort by display label
    asort($attrs);

    // Cache the results, since otherwise we consume 3 API calls each time we need this data
    
    $this->pluginCfg['groupable_attrs'] = json_encode($attrs);
    
    $SFModel = ClassRegistry::init("SalesforceSource.SalesforceSource");

    $SFModel->id = $this->pluginCfg['id'];
    $SFModel->saveField('groupable_attrs', $this->pluginCfg['groupable_attrs']);

    return $attrs;
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
    // Not supported because not in scope for initial development.
    
    throw new DomainException("NOT IMPLEMENTED");
  }
  
  /**
   * Determine current API limits and usage.
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Array of limits and values
   * @todo   Perhaps make this a standard OIS interface/call?
   */
  
  public function limits() {
    $ret = array();
    
    $sf = $this->querySalesforceApi(array('limits' => true));
    
    // Pull only the values we're currently interested in
    
    if(!empty($sf->DailyApiRequests->Max)) {
      $ret['daily']['limit'] = $sf->DailyApiRequests->Max;
      $ret['daily']['used'] = $sf->DailyApiRequests->Max - $sf->DailyApiRequests->Remaining;
    }
    
    // As a potential RFE, we can't currently access app specific usage
    // since we don't know the name of the app configured in salesforce.
    // Perhaps we can suggest setting the OIS name and the SF Oauth name
    // to be the same? (We'd need the parent config for that...
    
    return $ret;
  }
  
  /**
   * Query the Salesforce API.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array $attributes Search attributes
   * @return StdClass JSON decoded results
   */
  
  protected function querySalesforceApi($attributes) {
    // First we need to get an access token. If the access token expires,
    // we need to use the refresh token to get a new one.
    
    // Grab the access token from the config, or throw an error if not found
    if(empty($this->pluginCfg['access_token'])) {
      throw new InvalidArgumentException(_txt('er.salesforcesource.token.none'));
    }
    
    $options = array(
      'header' => array(
        'Accept'        => 'application/json',
        'Authorization' => 'Bearer ' . $this->pluginCfg['access_token'],
        'Content-Type'  => 'application/json'
      )
    );
    
    $targetUrl = $this->pluginCfg['serverurl'];
    
    if(!empty($this->pluginCfg['instance_url'])) {
      $targetUrl = $this->pluginCfg['instance_url'];
    }
    
    $HttpSocket = new HttpSocket();
    
    if(!empty($attributes['id'])) {
      // Retrieve
      
      // We expect id to be of the form ObjectType-Id
      $sfid = explode('-', $attributes['id'], 2);
      
      $searchResults = $HttpSocket->get(
        $targetUrl . "/services/data/v39.0/sobjects/" . $sfid[0] . "/" . $sfid[1],
        null,
        $options
      );
    } elseif(isset($attributes['limits'])) {
      // Obtain current limits
      
      $searchResults = $HttpSocket->get(
        $targetUrl . "/services/data/v39.0/limits",
        null,
        $options
      );
    } elseif(!empty($attributes['start']) && !empty($attributes['end'])) {
      // Obtain changelist
      
      $searchResults = $HttpSocket->get(
        $targetUrl . "/services/data/v39.0/sobjects/" . $attributes['sobject'] . "/updated/",
        $attributes,
        $options
      );
    } elseif(!empty($attributes['mail'])) {
      // Search by email. In theory we should be able to do a parameterized search with IN=EMAIL,
      // but this doesn't appear to actually constrain the search. So instead we use SOSL and
      // mock up the results to look like parameterized search.
      
      $ret = new stdClass;
      $ret->searchRecords = array();
      
      $sobjects = array();
      
      if($this->pluginCfg['search_contacts']) {
        $sobjects[] = 'Contact';
      }
      if($this->pluginCfg['search_users']) {
        $sobjects[] = 'User';
      }
      
      foreach($sobjects as $sobject) {
        $searchResults = $HttpSocket->get(
          $targetUrl . "/services/data/v39.0/query/?q="
                     . urlencode("SELECT Id from " . $sobject . " WHERE Email='" . $attributes['mail'] . "'"),
          null,
          $options
        );
        
        if($searchResults->code == 200) {
          $json = json_decode($searchResults->body);
          
          if($json->totalSize == 0) {          
            return array();
          }
          
          $ret->searchRecords[] = $this->querySalesforceApi(array('id' => $sobject.'-'.$json->records[0]->Id));
        }
        // else we should probably fall through for error handling
      }
      
      if($searchResults->code == 200) {
        return $ret;
      }
      // else fall through for error handling
    } else {
      // Search
      
      if(!empty($attributes['sobject'])) {
        // We're probably being asked to retrieve a related custom object,
        // so set the appropriate object and treat this like a hybrid search/retrieve.
        
        // According to
        // https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_relationship_traversal.htm#dome_relationship_traversal
        // we should be able to traverse the relationship in one call, but it doesn't appear to work
        /*
        $searchResults = $HttpSocket->get(
          $targetUrl . "/services/data/v39.0/sobjects/Contact/0030n000001bFWeAAM/Committee_Boards__r",
          null,
          $options
        );*/
        
        // "SELECT *" doesn't work here
        $objectIds = $HttpSocket->get(
          $targetUrl . "/services/data/v39.0/query/?q="
          . urlencode("SELECT Id FROM " . $attributes['sobject']
                      . " WHERE " . $attributes['sobject_key'] . "='" . $attributes['sobject_id'] . "'"),
          null,
          $options
        );
        
        $searchResults = array();
        
        // There could be more than one search result. Note this could be paginated, see
        // $jsr->done and $jsr->nextRecordsUrl for additional results.
        $jsr = json_decode($objectIds);
        
        if(!empty($jsr->totalSize) && $jsr->totalSize > 0) {
          foreach($jsr->records as $r) {
            // Now pull the associated object record
            $rRecord = $HttpSocket->get(
              $targetUrl . "/services/data/v39.0/sobjects/" . $attributes['sobject'] . "/" . $r->Id,
              null,
              $options
            );
            
            if($rRecord->code == 200) {
              $searchResults[] = json_decode($rRecord->body);
            }
            // else ignore
          }
        }
        
        return $searchResults;
      } else {
        // We have to use POST (and it's slightly more complex syntax) because
        // PHP (and therefore Cake) incorrectly generates URLs with indexes for
        // repeated parameters, eg: sobject[1]
        // See https://github.com/cakephp/cakephp/issues/1901
        
        $searchAttributes = $attributes;
        
        if($this->pluginCfg['search_contacts']) {
          $searchAttributes['sobjects'][] = array('name' => 'Contact');
        }
        if($this->pluginCfg['search_users']) {
          $searchAttributes['sobjects'][] = array('name' => 'User');
        }
        
        // Pull the fields we need for search results listings
        $searchAttributes['fields'] = array(
          'Id',
          'FirstName',
// This field is not enabled by default (CO-1506)
//          'MiddleName',
          'LastName',
          'Email'
        );
        
        // We should be able to do this to constrain searches to email addresses,
        // but it doesn't appear to actually do that (so see special handling, above).
        // $searchAttributes['in'] = 'EMAIL';
      }
      
      $searchResults = $HttpSocket->post(
        $targetUrl . "/services/data/v39.0/parameterizedSearch",
        json_encode($searchAttributes),
        $options
      );
    }
    
    $json = json_decode($searchResults->body);
    
    // Salesforce includes some elements we don't need that change on each request,
    // impacting our ability to detect record changes, so we remove them.
    // (We could instead use LastModifiedDate to detect changes, but the OIS
    // infrastructure doesn't currently support that approach.)
    
    unset($json->LastViewedDate);
    unset($json->LastReferencedDate);
    
    if($searchResults->code == 401
       && !empty($json[0]->errorCode)
       && $json[0]->errorCode == 'INVALID_SESSION_ID') {
      // The access token expired, obtain a new one and reissue the request
      
      $this->refreshToken();
      
      return $this->querySalesforceApi($attributes);
    }
    
    if($searchResults->code >= 400) {
      // Some sort of error occurred
      
      throw new RuntimeException($json[0]->errorCode . ": " . $json[0]->message);
    }
    
    return $json;
  }
  
  /**
   * Refresh the access token.
   * 
   * @since  COmanage Registry v3.1.0
   * @return Boolean True if a new access token was obtained
   * @throws RuntimeException
   */
  
  public function refreshToken() {
    $HttpSocket = new HttpSocket();

    $params = array(
      'client_id'     => $this->pluginCfg['clientid'],
      'client_secret' => $this->pluginCfg['client_secret'],
      'grant_type'    => 'refresh_token',
      'refresh_token' => $this->pluginCfg['refresh_token'],
      'format'        => 'json'
    );
    
    $postUrl = $this->pluginCfg['serverurl'] . "/services/oauth2/token";
    
    $results = $HttpSocket->post($postUrl, $params);
    
    $json = json_decode($results->body());
    
    if($results->code != 200 || empty($json->access_token)) {
      // There should be an error in the response
      throw new RuntimeException(_txt('er.salesforcesource.code',
                                      array($json->error . ": " . $json->error_description)));
    }
    
    // Update the database as well as our current config

    $SalesforceSource = ClassRegistry::init('SalesforceSource.SalesforceSource');
    
    $SalesforceSource->id = $this->pluginCfg['id'];
    $SalesforceSource->saveField('access_token', $json->access_token);
    // Update the instance url in case it changed
    if(!empty($json->instance_url)) {
      $SalesforceSource->saveField('instance_url', $json->instance_url);
    }
    
    $this->pluginCfg['access_token'] = $json->access_token;
    $this->pluginCfg['instance_url'] = $json->instance_url;
    
    return true;
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
    $ret = array();
    
    // Convert the raw string back to a JSON object/array
    $attrs = json_decode($raw);
    
    // Get the list of groupable attributes
    // XXX For performance reasons we should cache this somehow,
    // possibly by writing/updating a json blob into the
    // salesforce_source record on admin operations
    $groupAttrs = $this->groupableAttributes();
    
    foreach(array_keys($groupAttrs) as $gAttr) {
      if(strchr($gAttr, ':')) {
        // Custom object, split the name out
        $oAttr = explode(':', $gAttr, 2);
        $obj = $oAttr[0];
        $att = $oAttr[1];
        
        if(!empty($attrs->$obj[0]->$att) && is_string($attrs->$obj[0]->$att)) {
          $ret[$gAttr][] = (string)$attrs->$obj[0]->$att;
        }
      } else {
        if(!empty($attrs->$gAttr) && is_string($attrs->$gAttr)) {
          $ret[$gAttr][] = (string)$attrs->$gAttr;
        }
      }
    }
    
    return $ret;
  }
  
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array $result Salesforce Search Result
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
    
    if(!empty($result->FirstName)) {
      $orgdata['Name'][0]['given'] = (string)$result->FirstName;
    }
    if(!empty($result->MiddleName)) {
      $orgdata['Name'][0]['middle'] = (string)$result->MiddleName;
    }
    if(!empty($result->LastName)) {
      $orgdata['Name'][0]['family'] = (string)$result->LastName;
    }
// Populate primary_name and type in the caller instead of here?
    $orgdata['Name'][0]['primary_name'] = true;
// XXX this should be configurable
    $orgdata['Name'][0]['type'] = NameEnum::Official;

    $orgdata['EmailAddress'] = array();
    
    if(!empty($result->Email)) {
      $orgdata['EmailAddress'][0]['mail'] = (string)$result->Email;
      $orgdata['EmailAddress'][0]['type'] = EmailAddressEnum::Official;
      $orgdata['EmailAddress'][0]['verified'] = false;
    }
    
    if(!empty($result->CompanyName)) {
      $orgdata['OrgIdentity']['o'] = (string)$result->CompanyName;
    }
    if(!empty($result->Title)) {
      $orgdata['OrgIdentity']['title'] = (string)$result->Title;
    }
    
    return $orgdata;
  }
  
  /**
   * Retrieve a single record from the IdentitySource. The return array consists
   * of two entries: 'raw', a string containing the raw record as returned by the
   * IdentitySource backend, and 'orgidentity', the data in OrgIdentity format.
   *
   * @since  COmanage Registry v3.1.0
   * @param  String $id Unique key to identify record
   * @return Array As specified
   * @throws InvalidArgumentException if not found
   * @throws OverflowException if more than one match
   */

  public function retrieve($id) {
    try {
      // There should just be one result
      $records = $this->querySalesforceApi(array('id' => $id));
    }
    catch(InvalidArgumentException $e) {
      throw new InvalidArgumentException(_txt('er.id.unk-a', array($id)));
    }
    
    if(!empty($records) && !empty($this->pluginCfg['custom_objects'])) {
      // Walk through the list of custom objects (of the form Object:Key)
      // and merge any results into the record
      
      $objs = explode(',', $this->pluginCfg['custom_objects']);
      
      foreach($objs as $o) {
        $oattr = explode(':', $o, 2);
        $sobject = $oattr[0];
        
        $orecords = $this->querySalesforceApi(array(
          'sobject'     => $sobject,
          'sobject_key' => $oattr[1],
          // Note $id is of the form Contact-012345 so use $records->Id instead
          'sobject_id'  => $records->Id)
        );
        
        // Insert the object into the result package
        $records->$sobject = $orecords;
      }
    }
    
    return array(
      'raw' => json_encode($records),
      'orgidentity' => $this->resultToOrgIdentity($records)
    );
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
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function search($attributes) {
    $ret = array();
    
    if(!isset($attributes['q'])) {
      // For now, we only support free form search (though Salesforce does support
      // search by eg email).
      
      return array();
    }
    
    $records = $this->querySalesforceApi($attributes);
    
    if(!empty($records->searchRecords)) {
      foreach($records->searchRecords as $r) {
        // We need to track if the record is a Contact or a User so we
        // can retrieve the details later. We use - instead of / or .
        // because the latter two are parsed as part of URLs.
        $key = (string)$r->attributes->type . "-" . (string)$r->Id;
        
        $ret[ $key ] = $this->resultToOrgIdentity($r);
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
   * @since  COmanage Registry v3.1.0
   * @return Array As specified
   */
  
  public function searchableAttributes() {
    // By default, ORCID uses a free form search. It is possible to search on
    // specific fields (eg: email), though for the initial implementation we
    // won't support that.
    
    return array(
      'q' => _txt('fd.search.all')
    );
  }
}