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
App::import("SalesforceSource.Model", "Salesforce");

class SalesforceSourceBackend extends OrgIdentitySourceBackend {
  public $name = "SalesforceSourceBackend";

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
      $res = $this->querySalesforceApi("/services/data/v39.0/sobjects/" . $sobject . "/updated/",
                                       $attributes);
      
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
      $r = $this->querySalesforceApi("/services/data/v39.0/sobjects",
                                     array('id' => 'Contact-describe'));
      
      if(!empty($r->fields)) {
        foreach($r->fields as $f) {
          $attrs[ $f->name ] = $f->label;
        }
      }
    }
    
    if($this->pluginCfg['search_users']) {
      $r = $this->querySalesforceApi("/services/data/v39.0/sobjects",
                                     array('id' => 'User-describe'));
      
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
        
        $r = $this->querySalesforceApi("/services/data/v39.0/sobjects",
                                       array('id' => $o[0] .'-describe'));
        
        foreach($r->fields as $f) {
          $attrs[ $r->name . ':' . $f->name ] = $r->labelPlural . ": " . $f->label;
        }
        
        // Name doesn't appear to be returned in this list, but is a valid option
        $attrs[ $r->name . ':Name' ] = $r->labelPlural . ": Name";
      }
    }
    
    // Sort by display label
    asort($attrs);

    // Cache the results, since otherwise we consume 3 API calls each time we need this data
    
    $this->pluginCfg['groupable_attrs'] = json_encode($attrs);
    
    $SFModel = ClassRegistry::init("SalesforceSource.SalesforceSource");

    $SFModel->id = $this->pluginCfg['id'];
    
    $SFModel->saveField('groupable_attrs',
                        $this->pluginCfg['groupable_attrs'],
                        // Disable callbacks so beforeSave doesn't blank out groupable_attrs
                        array('callbacks' => false));

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
    
    $sf = $this->querySalesforceApi("/services/data/v39.0/limits");
    
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
   * @param  String $urlpath    API URL path to query
   * @param  Array  $attributes Request attributes
   * @param  String $action     HTTP action
   * @return StdClass JSON decoded results
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function querySalesforceApi($urlpath, $attributes=array(), $action="get") {
    // First we need to get an access token. If the access token expires,
    // we need to use the refresh token to get a new one.
    
    $Salesforce = new Salesforce();
    
    $Salesforce->connect($this->pluginCfg['server_id'],
                         $this->pluginCfg['id']);
    
    if(!empty($attributes['id'])) {
      // We expect id to be of the form ObjectType-Id (so we can tell objects apart)
      $sfid = explode('-', $attributes['id'], 2);
      
      return $Salesforce->request(
        $urlpath . "/" . $sfid[0] . "/" . $sfid[1]
      );
    }
    
    return $Salesforce->request($urlpath, $attributes, $action);
  }
  
  /**
   * Convert a raw result, as from eg retrieve(), into an array of attributes that
   * can be used for group mapping.
   *
   * @since  COmanage Registry v3.1.0
   * @param  String $raw Raw record, as obtained via retrieve()
   * @return Array Array, where keys are attribute names and values are lists (arrays) of arrays with these keys: value, valid_from, valid_through
   */

  public function resultToGroups($raw) {
    $ret = array();
    
    // Convert the raw string back to a JSON object/array
    $attrs = json_decode($raw);
    
    if(!is_object($attrs))
      return array();
    
    // Get the list of groupable attributes
    $groupAttrs = $this->groupableAttributes();
    
    foreach(array_keys($groupAttrs) as $gAttr) {
      if(strchr($gAttr, ':')) {
        // Custom object, split the name out
        $oAttr = explode(':', $gAttr, 2);
        $obj = $oAttr[0];
        $att = $oAttr[1];
        
        foreach($attrs->$obj as $attrobj) {
          if(!empty($attrobj->$att) && is_string($attrobj->$att)) {
            $v = array(
              'value' => (string)$attrobj->$att
            );
            
            // Unclear if these field names are standard
            if(!empty($attrobj->Start_Date__c)) {
              // We don't know what timezone this is, so we treat it as UTC
              $v['valid_from'] = $attrobj->Start_Date__c . " 00:00:00";
            }
            
            if(!empty($attrobj->End_Date__c)) {
              // We don't know what timezone this is, so we treat it as UTC
              $v['valid_through'] = $attrobj->End_Date__c . " 23:59:59";
            }
            
            $ret[$gAttr][] = $v;
          }
        }
      } else {
        if(!empty($attrs->$gAttr) && is_string($attrs->$gAttr)) {
          $ret[$gAttr][] = array('value' => (string)$attrs->$gAttr);
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
    
    if(!empty($result->Account->Name)) {
      $orgdata['OrgIdentity']['o'] = (string)$result->Account->Name;
    } elseif(!empty($result->CompanyName)) {
      $orgdata['OrgIdentity']['o'] = (string)$result->CompanyName;
    }
    
    if(!empty($result->Title)) {
      $orgdata['OrgIdentity']['title'] = (string)$result->Title;
    }
    
    // Populate the SORID
// XXX should this be done automatically? or be a recommended standard?
// Note this is just the ID, vs the unique key which is (eg) Contact-Id
    if(!empty($result->Id)) {
      $orgdata['Identifier'][] = array(
        'identifier' => $result->Id,
        'login'      => false,
        'status'     => StatusEnum::Active,
        'type'       => IdentifierEnum::SORID
      );
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
      $records = $this->querySalesforceApi("/services/data/v39.0/sobjects",
                                           array('id' => $id));
    }
    catch(InvalidArgumentException $e) {
      throw new InvalidArgumentException(_txt('er.id.unk-a', array($id)));
    }
    
    if(!empty($records)) {
      if(!empty($records->AccountId)) {
        // Pull the account record to populate additional attributes.
        
        $account = $this->querySalesforceApi("/services/data/v39.0/sobjects/Account/" . $records->AccountId);
        
        // Insert the object into the result package
        $records->Account = $account;
      }
      
      if(!empty($this->pluginCfg['custom_objects'])) {
        // Walk through the list of custom objects (of the form Object:Key)
        // and merge any results into the record. Treat this like a hybrid search/retrieve.
        
        // According to
        // https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_relationship_traversal.htm#dome_relationship_traversal
        // we should be able to traverse the relationship in one call, but it doesn't appear to work
        /*
        $searchResults = $this->querySalesforceApi(
          $targetUrl . "/services/data/v39.0/sobjects/Contact/0030n000001bFWeAAM/Committee_Boards__r"
        );*/
        
        $objs = explode(',', $this->pluginCfg['custom_objects']);
        
        foreach($objs as $o) {
          $searchResults = array();
          
          $oattr = explode(':', $o, 2);
          $sobject = $oattr[0];
          
          $urlpath = "/services/data/v39.0/query/?q="
                   . urlencode("SELECT Id FROM " . $sobject
                               // Note $id is of the form Contact-012345 so use $records->Id instead
                               . " WHERE " . $oattr[1] . "='" . $records->Id . "'");
          
          $objectIds = $this->querySalesforceApi($urlpath);
          
          // There could be more than one search result. Note this could be paginated, see
          // $objectIds->done and $objectIds->nextRecordsUrl for additional results.
          
          if(!empty($objectIds->records)) {
            foreach($objectIds->records as $r) {
              // Pull the associated object record
              $rRecord = $this->querySalesforceApi(
                "/services/data/v39.0/sobjects/" . $sobject . "/" . $r->Id
              );
              
              if(!empty($rRecord)) {
                $searchResults[] = $rRecord;
              }
              // else ignore
            }
          }
          
          // Insert the object into the result package
          $records->$sobject = $searchResults;
        }
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
    
    $searchAttributes = $attributes;
    
    // Special case mail for CoPetition usage. Although the documentation suggests
    // we should be able to filter scope to email addresses (IN=EMAIL), this doesn't
    // appear to work...
    
    if(isset($attributes['mail'])) {
      $searchAttributes['q'] = $attributes['mail'];
      unset($searchAttributes['mail']);
    }
    
    if(!isset($searchAttributes['q'])) {
      // For now, we only support free form search (though Salesforce does support
      // search by eg email).
      
      return array();
    }
    
    // We have to use POST (and it's slightly more complex syntax) because
    // PHP (and therefore Cake) incorrectly generates URLs with indexes for
    // repeated parameters, eg: sobject[1]
    // See https://github.com/cakephp/cakephp/issues/1901

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

    $records = $this->querySalesforceApi(
      "/services/data/v39.0/parameterizedSearch",
      json_encode($searchAttributes),
      'post'
    );
    
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