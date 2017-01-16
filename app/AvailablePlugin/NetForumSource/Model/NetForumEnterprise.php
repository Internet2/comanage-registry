<?php
/**
 * COmanage Registry netFORUM Enterprise Implementation Model
 *
 * Copyright (C) 2017 MLA
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
 * @copyright     Copyright (C) 2017 MLA
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("NetForumServer", "NetForumSource.Model");

class NetForumEnterprise extends NetForumServer {
  // namespace returned from server
  protected $xwebNamespace = null;
  
  /**
   * Make an initial authentication request.
   *
   * @since COmanage Registry v1.1.0
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
    $sclient = new SoapClient($this->serverUrl . "/xWeb/secure/netForumXML.asmx?WSDL",
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
   * @since COmanage Registry v1.1.0
   * @param String  $searchKey   Search key (customer key)
   * @param Boolean $queryEvents Whether to also query for events for which the customer has registered
   * @return Array Array of OrgIdentity and raw (XML) data
   */
  
  public function queryByCustomerKey($searchKey, $queryEvents=false) {
    $ret = array();
    
    // There should be only one result (or maybe none).
    
    return $this->queryNetForumEnterprise('GetIndividualInformation',
                                          'GetIndividualInformationResult',
                                          array('IndividualKey' => $searchKey),
                                          false,
                                          true,
                                          $queryEvents);
  }
  
  /**
   * Issue a query by email address. Be sure to call connect() first.
   * 
   * @since COmanage Registry v1.1.0
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
                                          false); // We maybe want true, but it's slow and doesn't return much more
  }
  
  /**
   * Issue a query by name. Be sure to call connect() first.
   * 
   * @since COmanage Registry v1.1.0
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
                                          false); // We maybe want true, but it's slow and doesn't return much more
  }
  
  /**
   * Issue a query for customer events. Be sure to call connect() first.
   * 
   * @since COmanage Registry v1.1.0
   * @param String  $searchKey   Search key (customer key)
   * @return Array Array of OrgIdentity data
   */
  
  public function queryForEvents($searchKey) {
    // Not supported until we have an example to work with
    
    throw new LogicException(_txt('er.notimpl'));
  }
  
  /**
   * Issue a query against a netFORUM Enterprise instance. Be sure to call connect() first.
   * 
   * @since COmanage Registry v1.1.0
   * @param String  $callName   SOAP call name
   * @param String  $resultName SOAP result name
   * @param Array   $attributes Attributes to search by
   * @param Boolean $active     If true, only return active records
   * @param Boolean $raw        If true, return raw (XML) record as well as formatted OrgIdentity data
   * @param Boolean $events     If true, query for events for matching customer keys (requires $raw, set to false if $callName is 'GetCustomerEvent')
   * @param Boolean $deep       If true, make an additional query on customer key to get more detailed record
   * @return Array Array of OrgIdentity data, and optionally raw (XML) data
   * @throws SoapFault
   */

  protected function queryNetForumEnterprise($callName,
                                             $resultName,
                                             $attributes,
                                             $active=true,
                                             $raw=false,
                                             $events=false,
                                             $deep=false) {
    $results = array();
    
    $opts = array(
      'ssl' => array(
        'ciphers'=>"SHA1", // Need to explicitly set SHA1 cipher for some reason
      )
    );
    
    $scontext = stream_context_create($opts);
    $sclient = new SoapClient($this->serverUrl . "/xWeb/secure/netForumXML.asmx?WSDL",
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
    
    // GetIndividualInformationResult includes 'xsi:schemaLocation="http://www.avectra.com/2005/ Individual.xsd"'
    // which causes validation errors. Toss it.
    $sresponse->$resultName->any = preg_replace('/xsi.*xsd\"/', '', $sresponse->$resultName->any);
    $r = new SimpleXMLElement($sresponse->$resultName->any);
    
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
            
            if(!empty($dret)) {
              $results = array_merge($results, $dret);
            }
          }
        } else {
          $results[ (string)$entry->cst_key ] = $this->resultToOrgIdentity($entry);
        }
      }
    } elseif(!empty($r->IndividualObject)) {
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
    
    return $results;
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
    
//    if(!empty($result->cst_org_name_dn))
//      $orgdata['OrgIdentity']['o'] = (string)$result->cst_org_name_dn;
//    if(!empty($result->AddressLine1))
//      $orgdata['OrgIdentity']['ou'] = (string)$result->AddressLine1;
    if(!empty($result->ixo_title))
      $orgdata['OrgIdentity']['title'] = (string)$result->ixo_title;
    
/*
    if(!empty($result->MemberExpireDate)) {
      // netFORUM format is 12/31/2016 12:00:00 AM, we need to convert to YYYY-MM-DD HH:MM:SS
      $time = strtotime($result->MemberExpireDate);
      $orgdata['OrgIdentity']['valid_through'] = strftime("%F %T", $time);
    }*/
    
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
