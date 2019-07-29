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
    ),
    'is_comanage_match' => array(
      'rule' => 'boolean',
      'required' => true,
      'allowEmpty' => false
    ),
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
   * Perform an ID Match Reference Identifier or Update Attributes Request.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $serverId      Server ID
   * @param  array   $orgIdentityId Org Identity ID to pull attributes from for match request
   * @param  string  $action        'request' or 'update'
   * @return [type]           [description]
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function doRequest($serverId, $orgIdentityId, $action) {
    // Pull the Match Server configuration.
    
    $args = array();
    $args['conditions']['Server.id'] = $serverId;
    // Make sure server configuration is still active
    $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = array('MatchServer');
    
    $srvr = $this->Server->find('first', $args);
    
    if(empty($srvr)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.match_servers.1'), $serverId)));
    }
    
    // Pull the Org Identity record
    $args = array();
    $args['conditions']['OrgIdentity.id'] = $orgIdentityId;
    $args['contain'] = array(
      'PrimaryName',
      'Identifier'
    );
    
    $orgIdentity = $this->Server->Co->OrgIdentity->find('first', $args);
    
    if(empty($orgIdentity)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.org_identities.1'), $orgIdentityId)));
    }

    // Assemble a match request using the attributes in the Org Identity record
    // XXX this should be configurable, but for now we'll just send a fixed record
    
    $matchRequest = array(
      'sorAttributes' => array(
        'names' => array(
          0 => array(
            'type' => 'official',
            'given' => $orgIdentity['PrimaryName']['given'],
            'family' => $orgIdentity['PrimaryName']['family']
          )
        )
      )
    );
    
    if(!empty($orgIdentity['OrgIdentity']['date_of_birth'])) {
      // XXX this is expected to be YYYY-MM-DD but we don't currently try to reformat it...
      $matchRequest['sorAttributes']['dateOfBirth'] = $orgIdentity['OrgIdentity']['date_of_birth'];
    }
    
    $nationalId = null;
    $sorId = null;
    
    foreach($orgIdentity['Identifier'] as $id) {
      if($id['type'] == IdentifierEnum::National) {
        $nationalId = $id['identifier'];
      } elseif($id['type'] == IdentifierEnum::SORID) {
        $sorId = $id['identifier'];
      }
    }
  
    if(!$sorId) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.identifiers.1'), _txt('en.identifier.type', null, IdentifierEnum::SORID))));
    }
    
    if($nationalId) {
      // We don't try to reformat the identifier (strip spaces, slashes, etc) since
      // the match engine should be configured to treat the attribute appropriately
      // (eg: alphanumeric).
      $matchRequest['sorAttributes']['identifiers'] = array(
        0 => array(
          'type' => IdentifierEnum::National,
          'identifier' => $nationalId
        )
      );
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
        
        if(!empty($body->referenceId)) {
          // The pending match has been resolved
          return $body->referenceId;
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
   * @param  array   $orgIdentityId Org Identity ID to pull attributes from for match request
   * @return string                 Reference ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function requestReferenceIdentifier($serverId, $orgIdentityId) {
    return $this->doRequest($serverId, $orgIdentityId, 'request');
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
    
    return $this->doRequest($serverId, $orgIdentityId, 'update');
  }
}