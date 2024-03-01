<?php
/**
 * COmanage Registry PrivacyIDEA Model
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("Identifier", "Model");
App::uses("Server", "Model");
App::uses("TotpToken", "PrivacyIdeaAuthenticator.Model");

class PrivacyIdea extends AppModel {
	// Define class name for cake
  public $name = "PrivacyIdea";
	
  // Current schema version for API
  public $version = "1.0";
  
  // This is basically a virtual model
  public $useTable = false;
  
  // Server connection
  protected $requestCfg = null;
  
  /**
   * Confirm a token by validating an OTP value.
   *
   * @since  COmanage Registry v4.0.0
   * @param  array   $privacyIdeaAuthenticator PrivacyIdeaAuthenticator
   * @param  integer $coPersonId               CO Person ID
   * @param  string  $serial                   privacyIDEA token serial
   * @param  string  $totpValue                TOTP Value provided by CO Person
   * @return boolean                           true if the token is confirmed
   * @throws InvalidArgumentException
   */
  
  public function confirmToken($privacyIdeaAuthenticator, $coPersonId, $serial, $totpValue) {
    // Make sure we already have a token created
    
    $args = array();
    $args['conditions']['TotpToken.privacy_idea_authenticator_id'] = $privacyIdeaAuthenticator['id'];
    $args['conditions']['TotpToken.co_person_id'] = $coPersonId;
    $args['conditions']['TotpToken.serial'] = $serial;
    $args['contain'] = false;

    $TotpToken = new TotpToken();
    $token = $TotpToken->find('first', $args); 
    
    if(empty($token)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.totp_tokens.1'), $serial)));
    }
    
    $identifier = $this->lookupIdentifier($privacyIdeaAuthenticator['identifier_type'], $coPersonId);
    
    // By default, validation API does not require authentication. However, if
    // desired the admin can create a policy that requires authn using an "Validate Token"
    // https://privacyidea.readthedocs.io/en/latest/policies/authentication.html
    // https://privacyidea.readthedocs.io/en/latest/modules/api/auth.html
    // https://privacyidea.readthedocs.io/en/latest/installation/system/pimanage/index.html
    // in which case there must be a password provided here
    $Http = $this->connect($privacyIdeaAuthenticator['validation_server_id']);
    
    // XXX More assumption of TOTP
    
    $params = array(
      'user' => $identifier,
      'realm' => $privacyIdeaAuthenticator['realm'],
      'pass' => $totpValue
    );
    
    $response = $Http->get("/validate/check", $params, $this->requestCfg);
    $jresponse = json_decode($response);
    
    // Success = HTTP 204, failure = HTTP 400, or look at result->status
    if(!$jresponse->result->status) {
      throw new InvalidArgumentException(_txt('er.privacyideaauthenticator.code'));
    }
    
    $TotpToken->clear();
    $TotpToken->id = $token['TotpToken']['id'];
    
    $TotpToken->saveField('confirmed', true);
    
    return true;
  }
  
  /**
   * Connect to a privacyIDEA server.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $serverId Server ID
   * @return CoHttpClient      CoHttpClient
   */
  
  protected function connect($serverId) {
    // First pull the server info
    
    $args = array();
    $args['conditions']['Server.id'] = $serverId;
    $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = array('HttpServer');
    
    $Server = new Server();
    $srvr = $Server->find('first', $args);
    
    if(empty($srvr)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.servers.1'), $serverId)));
    }
    
    $Http = new CoHttpClient(array(
      'ssl_verify_peer' => $srvr['HttpServer']['ssl_verify_peer'],
      'ssl_verify_host' => $srvr['HttpServer']['ssl_verify_host']
    ));
    
    $Http->setBaseUrl($srvr['HttpServer']['serverurl']);
    $Http->setRequestOptions(array(
      'header' => array(
        'Accept'        => 'application/json'
      )
    ));
    
    $this->requestCfg = array(
      'header' => array(
        //'Authorization' => $altpasswd
        'Authorization' => $srvr['HttpServer']['password']
      )
    );
    
    return $Http;
  }
  
  /**
   * Create a new PrivacyIDEA Token.
   *
   * @since  COmanage Registry v4.0.0
   * @param  PrivacyIdeaAuthenticator $privacyIdeaAuthenticator PrivacyIdeaAuthenticator
   * @param  integer                  $coPersonId               CO Person ID
   * @return TotpToken                TotpToken
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function createToken($privacyIdeaAuthenticator, $coPersonId) {
    $identifier = $this->lookupIdentifier($privacyIdeaAuthenticator['identifier_type'], $coPersonId);
    
    $Http = $this->connect($privacyIdeaAuthenticator['server_id']);
    
    // XXX For now we only set params for TOTP tokens. We'll need to refactor
    // this section when we add support for additional token types.
    
    $params = array(
      'type' => 'totp',
      'user' => $identifier,
      'realm' => $privacyIdeaAuthenticator['realm'],
      'genkey' => '1',
      'optlen' => '6'
    );
    
    $response = $Http->post("/token/init", $params, $this->requestCfg);
    
    $jresponse = json_decode($response);
    
    if(!$jresponse->result->status) {
      throw new RuntimeException($jresponse->result->error->message);
    }
    
    $token = array(
      'privacy_idea_authenticator_id' => $privacyIdeaAuthenticator['id'],
      'co_person_id'                  => $coPersonId,
      'serial'                        => $jresponse->detail->serial,
      'confirmed'                     => false
    );
    
    $TotpToken = new TotpToken();
    $TotpToken->save($token);
    
    // We don't persist the QR Data, but we do need to return it for rendering
    $token['qr_data'] = $jresponse->detail->googleurl->img;
    
    return $token;
  }
  
  /**
   * Delete a PrivacyIDEA Token.
   *
   * @since  COmanage Registry v4.0.0
   * @param  PrivacyIdeaAuthenticator $privacyIdeaAuthenticator PrivacyIdeaAuthenticator
   * @param  string                   $serial                   privacyIDEA Serial
   * @return stdClass Object                   			JSON decoded response from HTTP call                       
   * @throws InvalidArgumentException
   */
    
  public function deleteToken($privacyIdeaAuthenticator, $serial) {
    $Http = $this->connect($privacyIdeaAuthenticator['server_id']);
    
    // This should work regardless of token type
    
    $response = $Http->delete("/token/" . $serial, array(), $this->requestCfg);

    $jresponse = json_decode($response);

    // Success = HTTP 204, failure = HTTP 400, or look at result->status
    if(!isset($jresponse->result->status) || !$jresponse->result->status) {
      // error code 601 indicates Token was not found in Privacy Idea database, so we want to continue deleting but return that information
      if(isset($jresponse->result->error->code) && $jresponse->result->error->code != 601) {
        throw new InvalidArgumentException($jresponse->result->error->message);
      }
    }

    return $jresponse;
  }
  
  /**
   * Lookup the Identifier of the specified type for the specified CO Person.
   *
   * @since  COmanage Registry v4.0.0
   * @param  string  $identifierType Identifier type
   * @param  integer $coPersonId     CO Person ID
   * @return string                  Identifier
   * @throws InvalidArgumentException
   */
  
  protected function lookupIdentifier($identifierType, $coPersonId) {
    // Pull the CO Person Identifier
    
    $args = array();
    $args['conditions']['Identifier.co_person_id'] = $coPersonId;
    $args['conditions']['Identifier.type'] = $identifierType;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;
    
    $Identifier = new Identifier();
    $id = $Identifier->find('first', $args);
    
    if(empty($id)) {
      throw new InvalidArgumentException(_txt('er.privacyideaauthenticator.identifier', array($identifierType)));
    }
    
    return $id['Identifier']['identifier'];
  }
  
  /**
   * Lock or unlock privacyIDEA token(s) associated with the CO Person and the
   * specified PrivacyIdeaAuthenticator.
   *
   * @since  COmanage Registry v4.0.0
   * @param  PrivacyIdeaAuthenticator $privacyIdeaAuthenticator PrivacyIdeaAuthenticator
   * @param  integer                  $coPersonId               CO Person ID
   * @param  boolean                  $unlock                   true to unlock, false to lock
   * @return boolean                                            true on success
   */
  
  public function manageLock($privacyIdeaAuthenticator, $coPersonId, $unlock=false) {
    // Pull all associated tokens for this instantiation for this CO Person.
    // Typically there will be only one, but there could be multiple. Because
    // of how Authenticator lock() operations are structured, we lock *all*
    // tokens.
    
    $args = array();
    $args['conditions']['TotpToken.co_person_id'] = $coPersonId;
    $args['conditions']['privacy_idea_authenticator_id'] = $privacyIdeaAuthenticator['id'];
    $args['fields'] = array('id', 'serial');
    $args['contain'] = false;
    
    // We again assume TotpToken here, since that's the only type we currently support.
    
    $TotpToken = new TotpToken();
    $tokens = $TotpToken->find('list', $args); 
    
    if(!empty($tokens)) {
      $Http = $this->connect($privacyIdeaAuthenticator['server_id']);
      $action = $unlock ? 'enable' : 'disable';
      
      foreach(array_values($tokens) as $serial) {
        $params = array(
          'serial' => $serial
        );
    
        $response = $Http->post("/token/".$action, $params, $this->requestCfg);
        
        $jresponse = json_decode($response);
    
        // Success = HTTP 204, failure = HTTP 400, or look at result->status
        if(!$jresponse->result->status) {
          // What happens if we fail to disable any token? maybe because it's already disabled?
          // $jresponse->result->status is true but $jresponse->result->value is 0 (ie: 0 tokens were disabled)

          throw new InvalidArgumentException($jresponse->result->error->message);
        }
      }
    }
    
    return true;
  }
}
