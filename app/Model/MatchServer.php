<?php
/**
 * COmanage Registry Match Server Model
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
 * @package       registry
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoHttpClient', 'Lib');

class MatchServer extends AppModel {
  // Define class name for cake
  public $name = "MatchServer";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("Server");
  
  public $hasMany = array("MatchServerAttribute");
  
  // Default display field for cake generated views
  public $displayField = "serverurl";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'server_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'serverurl' => array(
      // The Cake URL rule accepts FTP, gopher, news, and file... not clear that
      // we'd want all of those
      'rule' => array('custom', '/^https?:\/\/.*/'),
      'required' => true,
      'allowEmpty' => false
    ),
    'username' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'password' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'sor_label' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.3.0
   * @return Boolean
   */
  /*
   * We don't implement this here because we don't know that serverurl is valid.
   * ie: The configured URL might be https://server.org/myapp, but that might
   * always return unauthorized because the actual URL being assembled is
   * something like https://server.org/myapp/api/v1/resources.json and we have
   * no way of knowing what the latter component is. So it's up to the code
   * using the HttpServer object to whatever validation it wants to do.
   * 
  public function beforeSave($options = array()) {
  }
   */
  
  /**
   * Assemble attributes from a person record into a format suitable for wire
   * transfer.
   *
   * @since  COmanage Registry v4.0.0
   * @param  array $matchAttributes Match Attribute configuration
   * @param  array $person          Org Identity or CO Person
   * @return array                  Array of data suitable for conversion to JSON
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  protected function assembleRequestAttributes($matchAttributes, $person) {
    $matchRequest = array();
    
    $supportedAttrs = $this->MatchServerAttribute->supportedAttributes();
    
    // We accept either an OrgIdentity or CoPerson, and (at least for now) the
    // structure of the two will be the same (since we don't look at any MVPAs
    // attached to CoPersonRoles), so we just need to figure out which one we
    // are working with.
    
    $pmodel = isset($person['OrgIdentity']) ? 'OrgIdentity' : 'CoPerson';
    
    foreach($matchAttributes as $mattr) {
      if($mattr['required'] == RequiredEnum::NotPermitted)
        continue;
      
      $found = false;
      
      // This is the key used by supportedAttributes(), which is also the value
      // stored in the database for 'attribute' by the form
      $attrKey = $mattr['attribute'];
      
      // $model = (eg) EmailAddress
      $model = $supportedAttrs[$attrKey]['model'];
      // $wire = (eg) emailAddresses
      $wire = $supportedAttrs[$attrKey]['wire'];
      
      if(isset($supportedAttrs[$attrKey]['attribute'])) {
        // This is a singleton value on OrgIdentity, eg "date_of_birth"

        // XXX date_of_birth is expected to be YYYY-MM-DD but we don't currently try to reformat it...
        
        if(!empty($person[$pmodel][ $supportedAttrs[$attrKey]['attribute'] ])) {
          $matchRequest['sorAttributes'][$wire] = $person[$pmodel][ $supportedAttrs[$attrKey]['attribute'] ];
          $found = true;
        }
      } elseif(isset($supportedAttrs[$attrKey]['attributes'])) {
        // This is an MVPA, eg "emailAddress"
        
        // When assembling attributes from MVPAs, we include all available attributes.
        // The Match server can ignore the ones it doesn't care about.
        
        // We don't try to reformat the attribute (strip spaces, slashes, etc) since
        // the match engine should be configured to treat the attribute appropriately
        // (eg: alphanumeric).
        
        // $type = (eg) official (as configured for this Match Server instance)
        $type = $mattr['type'];
        
        $obj = Hash::extract($person[$model], '{n}[type='.$type.']');
        
        if(!empty($obj)) {
          foreach($obj as $o) {
            // Assemble the record
            $attrs = array(
              'type' => $type
            );
            
            foreach($supportedAttrs[$attrKey]['attributes'] as $ra => $ad) {
              // $ra = Registry Attribute, $ad = Attribute Dictionary attribute
              // We use isset() rather than !empty() to avoid issues with
              // "blank" values, including 0
              if(isset($o[$ra])) {
                $attrs[$ad] = $o[$ra];
              }
            }
            
            // Make sure we have something other than type to work with
            if(count(array_keys($attrs)) > 1) {
              $matchRequest['sorAttributes'][$wire][] = $attrs;
              $found = true;
            }
          }
        }
      } else {
        throw new LogicException('NOT IMPLEMENTED: ' . $attrKey);
      }
      
      if(!$found && $mattr['required'] == RequiredEnum::Required) {
        throw new InvalidArgumentException(_txt('er.match.attr.req', array($mattr['attribute'], $mattr['id'])));
      }
    }
    
    if(empty($matchRequest)) {
      // We didn't find any attributes, so throw an error
      
      throw new RuntimeException(_txt('er.match.attr.none'));
    }
    
    return $matchRequest;
  }
  
  /**
   * Perform an ID Match Reference Identifier or Update Attributes Request.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $serverId      Server ID
   * @param  integer $orgIdentityId Org Identity ID to pull attributes from for match request
   * @param  integer $coPersonId    CO Person ID to pull attributes from for match request
   * @param  string  $action        'request' or 'update'
   * @param  string  $referenceId   Reference ID, for forced reconciliation request
   * @return mixed                  Reference ID (for request). Array (for request/300), or boolean true (for update)
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function doRequest($serverId, $orgIdentityId, $coPersonId, $action, $referenceId=null) {
    // Pull the Match Server configuration.
    
    $args = array();
    $args['conditions']['Server.id'] = $serverId;
    // Make sure server configuration is still active
    $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = array('MatchServer' => array('MatchServerAttribute'));
    
    $srvr = $this->Server->find('first', $args);
    
    if(empty($srvr)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.match_servers.1'), $serverId)));
    }
    
    // We accept either an Org Identity ID or a CO Person ID to pull attributes from.
    // Org Identities are used by Pipelines, CO Person IDs are used by Enrollment Flows.
    // Only one should be specified.
    
    if(!$orgIdentityId && !$coPersonId) {
      throw new InvalidArgumentException(_txt('er.notprov'));
    }
    
    // Pull the person record
    $args = array();
    if($orgIdentityId) {
      $args['conditions']['OrgIdentity.id'] = $orgIdentityId;
    } else {
      $args['conditions']['CoPerson.id'] = $coPersonId;
    }
    $args['contain'] = array(
      'EmailAddress',
      'Name',
      'Identifier'
    );
    
    if($orgIdentityId) {
      $person = $this->Server->Co->OrgIdentity->find('first', $args);
      
      if(empty($person)) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.org_identities.1'), $orgIdentityId)));
      }
      
      // Find an SOR ID in the Org Identity
      $s = array();
      
      if(!empty($person['Identifier'])) {
        $s = Hash::extract($person['Identifier'], '{n}[type='.IdentifierEnum::SORID.']');
      }
      
      if(empty($s)) {
        throw new InvalidArgumentException(_txt('er.match.attr.sorid'));
      }
      
      $sorId = $s[0]['identifier'];
    } else {
      $person = $this->Server->Co->CoPerson->find('first', $args);
      
      if(empty($person)) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_people.1'), $coPersonId)));
      }
      
      // We don't really have a guarantee of anything other than the CO Person ID
      // so we'll use that.
      $sorId = (string)$coPersonId;
    }
    
    // Assemble a match request using the attributes in the Org Identity record
    // Let any exceptions bubble up
    $matchRequest = $this->assembleRequestAttributes($srvr['MatchServer']['MatchServerAttribute'], $person);
    
    if($referenceId) {
      // Insert the requested Reference ID into the message body
      
      $matchRequest['referenceId'] = $referenceId;
    }
    
    $Http = new CoHttpClient();
    
    $Http->setBaseUrl($srvr['MatchServer']['serverurl']);
    $Http->setRequestOptions(array(
      'header' => array(
        'Content-Type'  => 'application/json'
      )
    ));
    $Http->configAuth(
      'Basic',
      $srvr['MatchServer']['username'],
      $srvr['MatchServer']['password']
    );
    
    $url = "/people/" . urlencode($srvr['MatchServer']['sor_label']) . "/" . urlencode($sorId);
    
    if($action == 'request') {
      // Before we submit the PUT, we do a GET (Request Current Values) to see
      // if there is already a reference ID available. This is primarily to
      // handle a potential match (202) situation (ie: we previously tried to
      // get a reference ID but got a 202 instead), since we don't currently
      // have a mechanism to be notified when a 202 is resolved.
      
      // An alternate approach here would be to store the Match Request ID
      // returned as part of the 202 response, however that ID is optional
      // (maybe it should be required?) and we would need to track it somewhere
      // (in the OIS Record?). It would be nice to link to the pending request
      // though...
      
      $response = $Http->get($url);
      
      if($response->code == 200) {
        $body = json_decode($response->body);
        
        if(!empty($body->meta->referenceId)) {
          // The pending match has been resolved
          return $body->meta->referenceId;
        }
      }
    }
    
    $response = $Http->put($url, json_encode($matchRequest));
    
    $body = json_decode($response->body);
    
    // If we get anything other than a 200/201 back, throw an error. This includes
    // 202, which we handle by simply generating a slightly different error.
    
    if($response->code == 202) {
      $matchRequest = "?";
      
      if(!empty($body->matchRequest)) {
        // Match Request is an optional part of the response
        $matchRequest = $body->matchRequest;
      }
      
      throw new RuntimeException(_txt('rs.match.accepted', array($matchRequest)));
    }
    
    if($response->code == 300) {
      $candidates = $body->candidates;
      
      // Inject the "new" candidate to make it easier for the calling code
      $candidates[] = (object)array(
        'referenceId' => 'new',
        'sorRecords' => (object)array(
          (object)array(
            'meta' => (object)array(
              'referenceId' => 'new'
            ),
            'sorAttributes' => (object)$matchRequest['sorAttributes']
          )
        )
      );
      
      return $candidates;
    }
    
    if($response->code != 200 && $response->code != 201) {
      $error = $response->reasonPhrase;
      
      // If an error was provided in the response, use that instead
      if(!empty($body->error)) {
        $error = $body->error;
      }
      
      throw new RuntimeException(_txt('er.match.response', array($error)));
    }
    
    if($action == 'request') {
      // We expect a reference ID
      return $body->referenceId;
    }
    
    return true;
  }
  
  /**
   * Perform an ID Match Reference Identifier Request.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $serverId      Server ID
   * @param  integer $orgIdentityId Org Identity ID to pull attributes from for match request
   * @param  integer $coPersonId    CO Person ID to pull attributes from for match request
   * @param  string  $referenceId   Reference ID, for forced reconciliation request
   * @return mixed                  Reference ID or Array (on 300 response)
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function requestReferenceIdentifier($serverId, $orgIdentityId, $coPersonId=null, $referenceId=null) {
    return $this->doRequest($serverId, $orgIdentityId, $coPersonId, 'request', $referenceId);
  }
  
  /**
   * Perform an ID Match Update Match Attributes Request.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $serverId      Server ID
   * @param  array   $orgIdentityId Org Identity ID to pull attributes from for match request
   * @return boolean                true on success
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function updateMatchAttributes($serverId, $orgIdentityId) {
    // This is basically the same request as requestReferenceIdentifier().
    
    return $this->doRequest($serverId, $orgIdentityId, null, 'update');
  }
}