<?php
/**
 * COmanage Registry netFORUM Enterprise Implementation Model
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

class NetForumEnterprise extends NetForumServer {
  // namespace returned from server
  protected $xwebNamespace = null;
  
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
    $sclient = new CoSoapClient($this->serverUrl . "/xWeb/secure/netForumXML.asmx?WSDL",
                                array('stream_context' => $scontext,
                                      'cache_wsdl' => WSDL_CACHE_NONE,
                                      // trace needed for get headers
                                      'trace' => true));
    
    $headers = array();
    
    $response = $sclient->__soapCall('Authenticate',
                                     array('parameters' => $requestParams),
                                     null,
                                     null,
                                     $headers);
    
    if(!empty($headers['AuthorizationToken']->Token)) {
      $this->token = $headers['AuthorizationToken']->Token;
      $this->xwebNamespace = $response->AuthenticateResult;
    } else {
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
    
    return $this->queryNetForumEnterprise('GetIndividualInformation',
                                          'GetIndividualInformationResult',
                                          array('IndividualKey' => $searchKey),
                                          false,
                                          true,
                                          $queryEvents,
                                          false,
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
    
    return $this->queryNetForumEnterprise('WEBWebUserFindUsersByEmail',
                                          'WEBWebUserFindUsersByEmailResult',
                                          array('emailToMatch' => $searchKey),
                                          true,
                                          false,
                                          false,
                                          true); // We need membership validity
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
    
    return $this->queryNetForumEnterprise('WEBWebUserFindUsersByName',
                                          'WEBWebUserFindUsersByNameResult',
                                          array('usernameToMatch' => $searchKey),
                                          true,
                                          false,
                                          false,
                                          true); // We need membership validity
  }
  
  /**
   * Issue a query for customer events. Be sure to call connect() first.
   * 
   * @since COmanage Registry v2.0.0
   * @param String  $searchKey   Search key (customer key)
   * @return Array Array of OrgIdentity data
   */
  
  public function queryForEvents($searchKey) {
    $search = array(
      'CustomerKey'     => $searchKey
    );
    
    return $this->queryNetForumEnterprise('WEBActivityGetPurchasedEventsByCustomer',
                                          'WEBActivityGetPurchasedEventsByCustomerResult',
                                          $search,
                                          true,
                                          // We want to return the raw response so the caller can process it
                                          true,
                                          false,
                                          false,
                                          false);
  }
  
  /**
   * Issue a query against a netFORUM Enterprise instance. Be sure to call connect() first.
   * 
   * @since COmanage Registry v2.0.0
   * @param String  $callName   SOAP call name
   * @param String  $resultName SOAP result name
   * @param Array   $attributes Attributes to search by
   * @param Boolean $active     If true, only return active records
   * @param Boolean $raw        If true, return raw (XML) record as well as formatted OrgIdentity data
   * @param Boolean $events     If true, query for events for matching customer keys (requires $raw, set to false if $callName is 'GetCustomerEvent')
   * @param Boolean $deep       If true, make an additional query on customer key to get more detailed record
   * @param Boolean $committees If true, query for committee memberships for matching customer keys (requires $raw)
   * @return Array Array of OrgIdentity data, and optionally raw (XML) data
   * @throws SoapFault
   */

  protected function queryNetForumEnterprise($callName,
                                             $resultName,
                                             $attributes,
                                             $active=true,
                                             $raw=false,
                                             $events=false,
                                             $deep=false,
                                             $committees=false) {
    $results = array();
    
    $opts = array(
      'ssl' => array(
        'ciphers'=>"SHA1", // Need to explicitly set SHA1 cipher for some reason
      )
    );
    
    $scontext = stream_context_create($opts);
    $sclient = new CoSoapClient($this->serverUrl . "/xWeb/secure/netForumXML.asmx?WSDL",
                                array('stream_context' => $scontext,
                                      'cache_wsdl' => WSDL_CACHE_NONE,
                                      // trace needed for get headers
                                      'trace' => true));
    
    // Create header for next request
    $sheader = new SoapHeader($this->xwebNamespace,
                              'AuthorizationToken',
                              array('Token' => $this->token),
                              // not clear that we need this
                              true);
    
    $sresponse = null;
    $outHeaders = array();
    
    // We have to nest the search attributes into another array for some weird PHP reason
    $sresponse = $sclient->__soapCall($callName, array($attributes), null, $sheader, $outHeaders);
    
    // Store the new authtoken (before any recursion)
    $this->token = $outHeaders['AuthorizationToken']->Token;
    
    // Flags suppress namespace warning since there doesn't appear to be a way to load the
    // namespace before the XML is loaded
    $r = new SimpleXMLElement($sresponse->$resultName->any, LIBXML_NOERROR+LIBXML_NOWARNING);
    
    if(isset($r->Result)) {
      foreach($r->Result as $entry) {
        if($deep) {
          if(!empty($entry->cst_key)) {
            // Recurse directly to preserve most of the flags we were passed.
            
            $dret = $this->queryNetForumEnterprise('GetIndividualInformation',
                                                   'GetIndividualInformationResult',
                                                   array('IndividualKey' => (string)$entry->cst_key),
                                                   $active,
                                                   $raw,
                                                   $events,
                                                   false);
            
            // As for below, we need at least one membership to return a record.
            
            if(!empty($dret)) {
              $results = array_merge($results, $dret);
            }
          }
        } else {
          if($raw) {
            $results[] = $entry;
          } else {
            $results[ (string)$entry->cst_key ] = $this->resultToOrgIdentity($entry);
          }
        }
      }
    } elseif(!empty($r->IndividualObject)) {
      // Look for members validity dates
          
      $mret = $this->queryNetForumEnterprise('GetQuery',
                                             'GetQueryResult',
                                             array(
                                              'szObjectName'  => 'mb_membership',
                                              'szColumnList'  => 'mbr_cst_key, mbt_code, mbs_code, mbr_join_date, mbr_expire_date, mbr_terminate_date, mbr_terminate_reason',
                                              'szWhereClause' => "mbr_cst_key = '" . (string)$r->IndividualObject->ind_cst_key . "'",
                                              'szOrderBy'     => 'mbr_cst_key'
                                             ),
                                             $active,
                                             true);
      
      // We need at least one membership to return a record.
      
      if(!empty($mret)) {
        // Merge the raw record so a change in expiration date triggers a sync
        $mxml = $r->IndividualObject->addChild('Membership');
        
        if(!empty($mret[(string)$r->IndividualObject->ind_cst_key]['raw']['from'])) {
          $mxml->addChild('ValidFrom', $mret[(string)$r->IndividualObject->ind_cst_key]['raw']['from']);
        }

        if(!empty($mret[(string)$r->IndividualObject->ind_cst_key]['raw']['through'])) {
          $mxml->addChild('ValidThrough', $mret[(string)$r->IndividualObject->ind_cst_key]['raw']['through']);
        }
        
        if($committees) {
          // Look for committee memberships
    
          $cret = $this->queryNetForumEnterprise('WEBCommitteeGetCommitteesByCustomer',
                                                 'WEBCommitteeGetCommitteesByCustomerResult',
                                                 array(
                                                  'CustomerKey' => (string)$r->IndividualObject->ind_cst_key
                                                 ),
                                                 $active,
                                                 true);
          
          if(!empty($cret)) {
            // Merge the raw records so a change in committee triggers a sync
            $cxml = $r->IndividualObject->addChild('Committees');
            
            foreach($cret as $cmt) {
              // XXX we could also check start/end dates and cpo_code, which appears to be a role
              $cxml->addChild('CommitteeName', htmlspecialchars((string)$cmt->cmt_name));
            }
          }
        }
        
        if($events) {
          // If configured, pull events (evt_code) for purposes of mapping to group memberships.
          // Events are accessed via a separate call. Our typical use case will be to map events
          // to groups, so we'll make that separate call and then merge the results.
          
          $eret = $this->queryForEvents((string)$r->IndividualObject->ind_cst_key);
          
          if($eret) {
            $exml = $r->IndividualObject->addChild('Events');
            
            foreach($eret as $e) {
              $exml->addChild('EventProductCode', (string)$e->evt_code);
            }
          }
        }
        
        if($raw) {
          // Use the customer key as the unique ID
          $results[ (string)$r->IndividualObject->ind_cst_key ]['orgidentity'] = $this->resultToOrgIdentity($r->IndividualObject);
          
          // Insert the raw record for use by retrieve()
          $results[ (string)$r->IndividualObject->ind_cst_key ]['raw'] = $r->IndividualObject->asXML();
        } else {
          // Use the customer key as the unique ID
          $results[ (string)$r->IndividualObject->ind_cst_key ] = $this->resultToOrgIdentity($r->IndividualObject);
        }
      }
    } elseif(!empty($r->mb_membershipObject)) {
      if($raw) {
        // It's not clear how much of this logic is generic vs specific to the one instance
        // we currently have access to.
        // A person can have multiple memberships. We take the earliest valid join date
        // and the latest valid expiration date.
        
        // Store as unix timestamps to facilitate comparison
        $join = null;
        $expire = null;
        
        foreach($r->mb_membershipObject as $m) {
          if(!$active || $m->mbs_code == 'Active') {
            $j = strtotime($m->mbr_join_date);
            $x = strtotime($m->mbr_expire_date);
            
            if($j && (!$join || $j < $join)) {
              $join = $j;
            }
            
            if($x && (!$expire || $x > $expire)) {
              $expire = $x;
            }
          }
        }
        
        // Return an array of the dates. We'll let the parent call format stuff.
        $results[ (string)$r->mb_membershipObject->mbr_cst_key ]['raw'] = array(
          'from' => $join,
          'through' => $expire
        );
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
    
//    if(!empty($result->cst_org_name_dn))
//      $orgdata['OrgIdentity']['o'] = (string)$result->cst_org_name_dn;
//    if(!empty($result->AddressLine1))
//      $orgdata['OrgIdentity']['ou'] = (string)$result->AddressLine1;
    if(!empty($result->ixo_title))
      $orgdata['OrgIdentity']['title'] = (string)$result->ixo_title;
    
    // The format here is a Unix timestamp, which we created when we parsed the membership records
    if(!empty($result->Membership->ValidFrom))
      $orgdata['OrgIdentity']['valid_from'] = strftime("%F %T", (integer)$result->Membership->ValidFrom);
    if(!empty($result->Membership->ValidThrough))
      $orgdata['OrgIdentity']['valid_through'] = strftime("%F %T", (integer)$result->Membership->ValidThrough);
    
    $orgdata['Name'] = array();
    if(!empty($result->ind_first_name))
      $orgdata['Name'][0]['given'] = (string)$result->ind_first_name;
    if(!empty($result->ind_mid_name))
      $orgdata['Name'][0]['middle'] = (string)$result->ind_mid_name;
    if(!empty($result->ind_last_name))
      $orgdata['Name'][0]['family'] = (string)$result->ind_last_name;
    $orgdata['Name'][0]['primary_name'] = true;
    $orgdata['Name'][0]['type'] = NameEnum::Official;

    $orgdata['EmailAddress'] = array();
    
    if(!empty($result->eml_address)) {
      // Format for IndividualObject
      $orgdata['EmailAddress'][0]['mail'] = (string)$result->eml_address;
      $orgdata['EmailAddress'][0]['type'] = EmailAddressEnum::Official;
      $orgdata['EmailAddress'][0]['verified'] = false;
      
      // Note the UAT tier adds a dot to prevent accidentally delivering mail
//      $orgdata['EmailAddress'][0]['mail'] = str_replace('@.', '@',$orgdata['EmailAddress'][0]['mail']);
    } elseif(!empty($result->cst_eml_address_dn)) {
      $orgdata['EmailAddress'][0]['mail'] = (string)$result->cst_eml_address_dn;
      $orgdata['EmailAddress'][0]['type'] = EmailAddressEnum::Official;
      $orgdata['EmailAddress'][0]['verified'] = false;
    }
    
    return $orgdata;
  }
}
