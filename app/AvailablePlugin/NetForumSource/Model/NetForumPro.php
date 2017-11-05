<?php
/**
 * COmanage Registry netFORUM Pro Implementation Model
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

App::uses("NetForumServer", "NetForumSource.Model");
App::uses("CoSoapClient", "Lib");

class NetForumPro extends NetForumServer {
  /**
   * Make an initial authentication request.
   *
   * @since COmanage Registry v2.0.0
   * @throws RuntimeException
   * @throws SoapFault
   */
  
  protected function authenticate() {
    // Connect to the server and get an authtoken
    
    $requestParams = array(
      'userName' => $this->username,
      'password' => $this->password
    );
    
    $opts = array(
      'ssl' => array(
        'ciphers'=>"SHA1", // Need to explicitly set SHA1 cipher for some reason
      )
    );
    
    $scontext = stream_context_create($opts);
    $sclient = new CoSoapClient($this->serverUrl . "/xWeb/Signon.asmx?WSDL",
                                array('stream_context' => $scontext,
                                      'cache_wsdl' => WSDL_CACHE_NONE));
    
    $response = $sclient->Authenticate($requestParams);
    
    $this->token = $response->AuthenticateResult;
    
    if(!$this->token) {
      throw new RuntimeException('er.auth');
    }
  }
  
  /**
   * Issue a query by customer key. Be sure to call connect() first.
   * 
   * @since COmanage Registry v2.0.0
   * @param String  $searchKey       Search key (customer key)
   * @param Boolean $queryEvents     Whether to also query for events for which the customer has registered
   * @param Boolean $queryCommittees Whether to also query for committee memberships
   * @return Array Array of OrgIdentity and raw (XML) data
   */
  
  public function queryByCustomerKey($searchKey, $queryEvents=false, $queryCommittees=false) {
    $ret = array();
    
    // There should be only one result (or maybe none).
    
    return $this->queryNetForumPro('GetCustomerByKey',
                                   'GetCustomerByKeyResult',
                                   array('szCstKey' => $searchKey),
                                   false,
                                   true,
                                   $queryEvents,
                                   false,
                                   true,
                                   $queryCommittees);
  }
  
  /**
   * Issue a query by email address. Be sure to call connect() first.
   * 
   * @since COmanage Registry v2.0.0
   * @param String  $searchKey   Search key (email address)
   * @return Array Array of OrgIdentity data
   */
  
  public function queryByEmail($searchKey) {
    // We need to search "deep" here because MemberStatus is not returned
    // by default in the Email-based search results. (Basically we just get
    // identifiers and name.) Needed (eg) when being called by an Enrollment
    // Source (search by verified email).
    
    return $this->queryNetForumPro('GetCustomerByEmail',
                                   'GetCustomerByEmailResult',
                                   array('szEmailAddress' => $searchKey),
                                   true,
                                   false,
                                   false,
                                   true);
  }
  
  /**
   * Issue a query by name. Be sure to call connect() first.
   * 
   * @since COmanage Registry v2.0.0
   * @param String  $searchKey   Search key (sort name, ie "family given")
   * @return Array Array of OrgIdentity data
   */
  
  public function queryByName($searchKey) {
    // This is a search against SortName, which is of the form "family given",
    // and is executed as a prefix search. So, eg, "smith j" will find
    // "smith jane" and "smith john".
    
    return $this->queryNetForumPro('GetCustomerByName', 'GetCustomerByNameResult', array('szName' => $searchKey));
  }
  
  /**
   * Issue a query for customer committees. Be sure to call connect() first.
   * 
   * @since COmanage Registry v3.1.0
   * @param String  $searchId   Search key (note: Customer ID, not Customer Key)
   * @return SimpleXMLElement Object Unprocessed response from NetForum
   */
  
  public function queryForCommittees($searchId) {
    $search = array(
      'szCstId'     => $searchId
    );
    
    return $this->queryNetForumPro('GetCommitteeListByCstID',
                                   'GetCommitteeListByCstIdResult',
                                   $search,
                                   true,
                                   false,
                                   false,
                                   false,
                                   // We want to return the raw response so the caller can process it
                                   false);
  }
  
  /**
   * Issue a query for customer events. Be sure to call connect() first.
   * 
   * @since COmanage Registry v2.0.0
   * @param String  $searchKey   Search key (customer key)
   * @return SimpleXMLElement Object Unprocessed response from NetForum
   */
  
  public function queryForEvents($searchKey) {
    $search = array(
      'szCstKey'     => $searchKey,
      // We also need to put in a blank record date
      'szRecordDate' => ''
    );
    
    return $this->queryNetForumPro('GetCustomerEvent',
                                   'GetCustomerEventResult',
                                   $search,
                                   true,
                                   false,
                                   false,
                                   false,
                                   // We want to return the raw response so the caller can process it
                                   false);
  }
  
  /**
   * Issue a query against a netFORUM Pro instance. Be sure to call connect() first.
   * 
   * @since COmanage Registry v2.0.0
   * @param String  $callName   SOAP call name
   * @param String  $resultName SOAP result name
   * @param Array   $attributes Attributes to search by
   * @param Boolean $active     If true, only return active records
   * @param Boolean $raw        If true, return raw (XML) record as well as formatted OrgIdentity data
   * @param Boolean $events     If true, query for events for matching customer keys (requires $raw, set to false if $callName is 'GetCustomerEvent')
   * @param Boolean $deep       If true, make an additional query on customer key to get more detailed record
   * @param Boolean $process    If true, attempt to process the record into OrgIdentity format
   * @param Boolean $committees If true, query for committee memberships for matching customer keys (requires $raw)
   * @return Array Array of OrgIdentity data, and optionally raw (XML) data
   * @throws SoapFault
   */

  protected function queryNetForumPro($callName,
                                      $resultName,
                                      $attributes,
                                      $active=true,
                                      $raw=false,
                                      $events=false,
                                      $deep=false,
                                      $process=true,
                                      $committees=false) {
    $results = array();
    
    $opts = array(
      'ssl' => array(
        'ciphers'=>"SHA1", // Need to explicitly set SHA1 cipher for some reason
      )
    );

    $scontext = stream_context_create($opts);
    $sclient = new CoSoapClient($this->serverUrl . "/xweb/netFORUMXMLONDemand.asmx?WSDL",
                                array('stream_context' => $scontext,
                                      'cache_wsdl' => WSDL_CACHE_NONE,
                                      // trace needed for get headers
                                      'trace' => true));
    
    $authParams = array();
    $authParams['Token'] = $this->token;
    
    $sheader = new SoapHeader('http://www.avectra.com/OnDemand/2005/', 'AuthorizationToken', $authParams);
    
    $sresponse = null;
    $outHeaders = array();
    
    // We have to nest the search query into another array for some weird PHP reason
    $sresponse = $sclient->__soapCall($callName, array($attributes), null, $sheader, $outHeaders);
    
    // Store the new authtoken (before any recursion)
    $this->token = $outHeaders['AuthorizationToken']->Token;
    
    $r = new SimpleXMLElement($sresponse->$resultName->any);
    
    if(!$process) {
      return $r;
    }
    
    foreach($r->Result as $entry) {
      if($deep) {
        // If requested to go deep, we don't expect MemberStatus in the "abbreviated" result
        
        if(!empty($entry->cst_key)) {
          // Recurse directly to preserve most of the flags we were passed.
          
          $dret = $this->queryNetForumPro('GetCustomerByKey',
                                          'GetCustomerByKeyResult',
                                          array('szCstKey' => $entry->cst_key),
                                          $active,
                                          $raw,
                                          $events,
                                          false);
          
          if(!empty($dret)) {
            $results = array_merge($results, $dret);
          }
        }
      } else {
        if(!$active || (string)$entry->MemberStatus == 'Active') {
          // The raw option is largely intended for retrieve()
          
          if($raw) {
            if($committees) {
              // Look for committee memberships, note we need cst_id, not cst_key
              
              $cret = $this->queryForCommittees((string)$entry->cst_id);
              
              if($cret) {
                $cxml = $entry->addChild('Committees');
                
                foreach($cret->Result as $c) {
                  $cxml->addChild('CommitteeName', (string)$c->cmt_name);
                }
              }
            }
            
            if($events) {
              // If configured, pull events (prd_code) for purposes of mapping to group memberships.
              // Events are accessed via a separate call. Our typical use case will be to map events
              // to groups, so we'll make that separate call and then merge the results.
              
              $eret = $this->queryForEvents((string)$entry->cst_key);
              
              if($eret) {
                $exml = $entry->addChild('Events');
                
                foreach($eret->Result as $e) {
                  $exml->addChild('EventProductCode', (string)$e->prd_code);
                }
              }
            }
            
            // Use the customer key as the unique ID
            $results[ (string)$entry->cst_key ]['orgidentity'] = $this->resultToOrgIdentity($entry);
            
            // Insert the raw record for use by retrieve()
            $results[ (string)$entry->cst_key ]['raw'] = $entry->asXML();
          } else {
            // Use the customer key as the unique ID
            $results[ (string)$entry->cst_key ] = $this->resultToOrgIdentity($entry);
          }
        }
      }
    }
    
    return $results;
  }
  
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v2.0.0
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
    
    if(!empty($result->MemberExpireDate)) {
      // netFORUM format is 12/31/2016 12:00:00 AM, we need to convert to YYYY-MM-DD HH:MM:SS
      $time = strtotime($result->MemberExpireDate);
      $orgdata['OrgIdentity']['valid_through'] = strftime("%F %T", $time);
    }
    
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
}
