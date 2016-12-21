<?php
/**
 * COmanage Registry netFORUM OrgIdentitySource Backend Model
 *
 * Copyright (C) 2016 MLA
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2016 MLA
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("OrgIdentitySourceBackend", "Model");

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
   * @since  COmanage Registry v1.1.0
   * @return Array As specified
   */
  
  public function groupableAttributes() {
    return $this->groupAttrs;
  }
  
  /**
   * Obtain all available records in the IdentitySource, as a list of unique keys
   * (ie: suitable for passing to retrieve()).
   *
   * @since  COmanage Registry v1.1.0
   * @return Array Array of unique keys
   * @throws DomainException If the backend does not support this type of requests
   */
  
  public function inventory() {
    // Not clear if we can implement this... (or should...)
    throw new DomainException("NOT IMPLEMENTED");
  }
  
  /**
   * Request an auth token and perform the approprate SOAP call.
   *
   * @since  COmanage Registry v1.1.0
   * @param  String $queryType Type of query: Key, Email, or Name
   * @param  String $searchKey Key/string to search for
   * @return SimpleXMLElement, or null if no records found
   * @throws SoapFault
   */
  
  protected function makeNetForumQuery($queryType, $searchKey) {
    // It appears we can't reuse connections (error: "Invalid Token Value"),
    // so we wrap the appropriate calls into this function.
    
    // First, obtain an auth token.
    
    $opts = array(
      'ssl' => array(
        'ciphers'=>"SHA1", // Need to explicitly set SHA1 cipher for some reason
      )
    );

    $scontext = stream_context_create($opts);
    $sclient = new SoapClient($this->pluginCfg['serverurl'] . "/xweb/netFORUMXMLONDemand.asmx?WSDL",
                              array('stream_context' => $scontext,
                                    'cache_wsdl' => WSDL_CACHE_NONE));
    
    $authParams = array();
    $authParams['Token'] = $this->obtainAuthToken($this->pluginCfg['serverurl'],
                                                  $this->pluginCfg['username'],
                                                  $this->pluginCfg['password']);
    
    $sheader = new SoapHeader('http://www.avectra.com/OnDemand/2005/', 'AuthorizationToken', $authParams);
    $sclient->__setSoapHeaders($sheader);
    
    // Next, construct the search query. Map the query type to the parameter name.
    $searchAttrs = array(
      'Email' => array(
        'attr' => 'szEmailAddress',
        'call' => 'ByEmail'
      ),
      'Event' => array(
        'attr' => 'szCstKey',
        'call' => 'Event'
      ),
      'Key'   => array(
        'attr' => 'szCstKey',
        'call' => 'ByKey'
      ),
      'Name'  => array(
        'attr' => 'szName',
        'call' => 'ByName'
      )
    );
    
    $searchParams = array();
    $searchParams[ $searchAttrs[$queryType]['attr'] ] = $searchKey;
    
    if($queryType == 'Event') {
      // We also need to put in a blank record date
      $searchParams['szRecordDate'] = '';
    }
    
    $sresponse = null;
    
    // Construct function and result names
    $fname = "GetCustomer" . $searchAttrs[$queryType]['call'];
    $rname = $fname . "Result";
    
    $sresponse = $sclient->$fname($searchParams);
    
    if(!empty($sresponse->$rname->any)) {
      return new SimpleXMLElement($sresponse->$rname->any);
    } else {
      return null;
    }
  }
  
  /**
   * Obtain an auth token for the netFORUM API.
   *
   * @since  COmanage Registry v1.1.0
   * @param  String Server URL
   * @param  String Username
   * @param  String Password
   * @return String Auth token
   * @throws SoapFault
   */
  
  protected function obtainAuthToken($serverUrl, $username, $password) {
    $requestParams = array(
      'userName' => $username,
      'password' => $password
    );
    
    $opts = array(
      'ssl' => array(
        'ciphers'=>"SHA1", // Need to explicitly set SHA1 cipher for some reason
      )
    );
    
    $context = stream_context_create($opts);
    
    $client = new SoapClient($serverUrl . "/xWeb/Signon.asmx?WSDL",
                             array('stream_context' => $context,
                                   'cache_wsdl' => WSDL_CACHE_NONE));
    
    $response = $client->Authenticate($requestParams);
    
    return $response->AuthenticateResult;
  }
  
  /**
   * Query a netFORUM server.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Array $attributes Attributes to query (ie: searchableAttributes())
   * @return Array Search results
   * @throws RuntimeException
   */
  
  protected function queryNetForum($attributes) {
    $ret = array();
    
    // If more than one search attribute was provided, we'll OR the results
    
    if(!empty($attributes['cstkey'])) {
      $results = $this->makeNetForumQuery('Key', $attributes['cstkey']);
      
      if($results) {
        foreach($results->Result as $r) {
          // Should really only be one...
          
          if((string)$r->MemberStatus == 'Active') {
            if($this->pluginCfg['query_events']) {
              // If configured, pull events (prd_code) for purposes of mapping to group memberships.
              // Events are accessed via a separate call. Our typical use case will be to map events
              // to groups, so we'll make that separate call and then merge the results.
              
              $events = $this->makeNetForumQuery('Event', $attributes['cstkey']);
              
              if($events) {
                $exml = $r->addChild('Events');
                
                foreach($events->Result as $e) {
                  $exml->addChild('EventProductCode', (string)$e->prd_code);
                }
              }
            }
            
            // Use the customer key as the unique ID
            $ret[ (string)$r->cst_key ]['orgidentity'] = $this->resultToOrgIdentity($r);
            
            // Insert the raw record for use by retrieve()
            $ret[ (string)$r->cst_key ]['raw'] = $r->asXML();
          }
        }
      }
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

      $results = $this->makeNetForumQuery('Email', $searchEmail);
      
      if($results) {
        foreach($results->Result as $r) {
          // Pull the full record returned by customer key in case we're
          // being called by an Enrollment Source (search by verified email).
          $r2 = $this->makeNetForumQuery('Key', $r->cst_key);
          
          // There should be only one response per key
          if($r2 && (string)$r2->Result->MemberStatus == 'Active') {
            // Use the customer key as the unique ID
            $ret[ (string)$r->cst_key ] = $this->resultToOrgIdentity($r2->Result[0]);
          }
        }
      }
    }
    
    if(!empty($attributes['cn'])) {
      // This is a search against SortName, which is of the form "family given",
      // and is executed as a prefix search. So, eg, "smith j" will find
      // "smith jane" and "smith john".
      $results = $this->makeNetForumQuery('Name', $attributes['cn']);
      
      if($results) {
        foreach($results->Result as $r) {
          if((string)$r->MemberStatus == 'Active') {
            // Use the customer key as the unique ID
            $ret[ (string)$r->cst_key ] = $this->resultToOrgIdentity($r);
          }
        }
      }
    }
    
    return $ret;
  }
  
  /**
   * Convert a raw result, as from eg retrieve(), into an array of attributes that
   * can be used for group mapping.
   *
   * @since  COmanage Registry v1.1.0
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
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Array $result netFORUM Search Result
   * @return Array Org Identity and related models, in the usual format
   */
  
  protected function resultToOrgIdentity($result) {
    $orgdata = array();
    $orgdata['OrgIdentity'] = array();
    
    // Until we have some rules, everyone is a member
    $orgdata['OrgIdentity']['affiliation'] = AffiliationEnum::Member;
    
    if(!empty($result->cst_org_name_dn))
      $orgdata['OrgIdentity']['o'] = (string)$result->cst_org_name_dn;
    // AddressLine1 sometimes has an OU and sometimes has a street address
//    if(!empty($result->AddressLine1))
//      $orgdata['OrgIdentity']['ou'] = (string)$result->AddressLine1;
    if(!empty($result->ind_title))
      $orgdata['OrgIdentity']['title'] = (string)$result->ind_title;
    
    $orgdata['Name'] = array();
    if(!empty($result->ind_first_name))
      $orgdata['Name'][0]['given'] = (string)$result->ind_first_name;
    if(!empty($result->ind_last_name))
      $orgdata['Name'][0]['family'] = (string)$result->ind_last_name;
    $orgdata['Name'][0]['primary_name'] = true;
    $orgdata['Name'][0]['type'] = NameEnum::Official;

    $orgdata['EmailAddress'] = array();
    
    if(!empty($result->EmailAddress)) {
      $orgdata['EmailAddress'][0]['mail'] = (string)$result->EmailAddress;
      $orgdata['EmailAddress'][0]['type'] = EmailAddressEnum::Official;
      $orgdata['EmailAddress'][0]['verified'] = false;
      
      // Note the UAT tier adds a dot to prevent accidentally delivering mail
      $orgdata['EmailAddress'][0]['mail'] = str_replace('@.', '@',$orgdata['EmailAddress'][0]['mail']);
    }
    
    return $orgdata;
  }
  
  /**
   * Retrieve a single record from the IdentitySource. The return array consists
   * of two entries: 'raw', a string containing the raw record as returned by the
   * IdentitySource backend, and 'orgidentity', the data in OrgIdentity format.
   *
   * @since  COmanage Registry v1.1.0
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
   * @since  COmanage Registry v1.1.0
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
   * @since  COmanage Registry v1.1.0
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
   * @since  COmanage Registry v1.1.0
   * @param  String Server URL
   * @param  String Username
   * @param  String Password
   * @return Boolean True if parameters are valid
   * @throws RuntimeException
   */
  
  public function verifyNetForumServer($serverUrl, $username, $password) {
    // Based on similar code in CoLdapProvisionerTarget
    
    try {
      $this->obtainAuthToken($serverUrl, $username, $password);
    }
    catch(SoapFault $e) {
      throw new RuntimeException($e->getMessage());
    }
    
    return true;
  }
}
