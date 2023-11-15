<?php
/**
 * COmanage Registry OAuth2 Server Model
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('HttpSocket', 'Network/Http');

// We call this "Oauth2" instead of "OAuth2" to not fight Cake's inflector ("o_auth2_server"?)
class Oauth2Server extends AppModel {
  // Define class name for cake
  public $name = "Oauth2Server";
  
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
// Cake auto-inserts this for a HasOne, so we don't want it required.
// XXX For 4.0.0, we should probably make this consistent across all models.
//      'required' => true,
//      'allowEmpty' => false
    ),
    'serverurl' => array(
      'rule' => array('url', true),
      'required' => true,
      'allowEmpty' => false
    ),
    'clientid' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'client_secret' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'access_grant_type' => array(
      'rule' => array('inList', array(Oauth2GrantEnum::AuthorizationCode,
                                      Oauth2GrantEnum::ClientCredentials)),
      'required' => true,
      'allowEmpty' => false
    ),
    'scope' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'refresh_token' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'access_token' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.2.0
   * @return Boolean
   */

  public function beforeSave($options = array()) {
    if(isset($options['safeties']) && $options['safeties'] == 'off') {
      return true;
    }

    // If there is as access or refresh token, see if any "critical"
    // element has changed, and if so clear the token.
    
    if(!empty($this->data['Oauth2Server']['serverurl'])) {
      // Check for serverurl, since saveField() won't provide most attributes
      // (and is used to update the access token anyway, which we don't want
      // to do this check for).
      
      $args = array();
      $args['conditions']['Oauth2Server.id'] = $this->data['Oauth2Server']['id'];
      $args['contain'] = false;
      
      $curdata = $this->find('first', $args);
      
      if(!empty($curdata['Oauth2Server']['access_token'])
         || !empty($curdata['Oauth2Server']['refresh_token'])) {
        if(($this->data['Oauth2Server']['serverurl']
            != $curdata['Oauth2Server']['serverurl'])
           ||
           ($this->data['Oauth2Server']['clientid']
            != $curdata['Oauth2Server']['clientid'])
           ||
           ($this->data['Oauth2Server']['client_secret']
            != $curdata['Oauth2Server']['client_secret'])) {
          // Reset the tokens
          $this->data['Oauth2Server']['access_token'] = null;
          $this->data['Oauth2Server']['refresh_token'] = null;
        }
      }
    }
    
    return true;
  }
  
  /**
   * Exchange an authorization code for an access and refresh token.
   * 
   * @since  COmanage Registry v3.2.0
   * @param  Integer $id          Oauth2Server ID
   * @param  String  $code        Access code returned by call to /oauth/authorize
   * @param  String  $redirectUri Callback URL used for initial request
   * @return StdObject Object of data as returned by server, including access and refresh token
   * @throws RuntimeException
   */

  public function exchangeCode($id, $code, $redirectUri, $store=true) {
    return $this->obtainToken($id, 'authorization_code', $code, $redirectUri, $store);
  }
  
  /**
   * Obtain an OAuth token.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $id          Oauth2Server ID
   * @param  String  $grantType   OAuth grant type
   * @param  String  $code        Access code returned by call to /oauth/authorize, for authorization_code grant
   * @param  String  $redirectUri Callback URL used for initial request, for authorization_code grant
   * @param  Boolean $store       If true, store the retrieved tokens in the Oauth2Server configuration
   * @return StdObject Object of data as returned by server, including access and refresh token
   * @throws RuntimeException
   */
  
  public function obtainToken($id, $grantType, $code=null, $redirectUri=null, $store=true) {
    // Pull our configuration
    
    $args = array();
    $args['conditions']['Oauth2Server.id'] = $id;
    $args['contain'] = false;
    
    $srvr = $this->find('first', $args);
    
    if(!$srvr) {
      throw new RuntimeException(_txt('er.notfound', array(_txt('ct.oauth2_servers.1'), $id)));
    }
    
    $HttpSocket = new HttpSocket();

    $params = array(
      'client_id'     => $srvr['Oauth2Server']['clientid'],
      'client_secret' => $srvr['Oauth2Server']['client_secret'],
      'grant_type'    => $grantType
    );
    
    if($grantType == 'refresh_token') {
      $params['refresh_token'] = $srvr['Oauth2Server']['refresh_token'];
      $params['format'] = 'json';
    } elseif($grantType == 'authorization_code' && $code) {
      $params['code'] = $code;
      $params['redirect_uri'] = $redirectUri;
    } else {
      $params['scope'] = $srvr['Oauth2Server']['scope'];
    }
    
    $postUrl = $srvr['Oauth2Server']['serverurl'] . "/token";
    
    $results = $HttpSocket->post($postUrl, $params);
    
    $json = json_decode($results->body());
    
    if($results->code != 200) {
      // There should be an error in the response
      throw new RuntimeException(_txt('er.server.oauth2.token',
                                 array($json->error . ": " . $json->error_description)));
    }
    
    if($store) {
      // Save the fields we want to keep
      $data = array(
        'id' => $id,
        'access_token' => $json->access_token,
        // Store the raw result in case the server has added some custom attributes
        'token_response' => json_encode($json)
      );
      
      // We shouldn't have a new refresh token on a refresh_token grant
      // (which just gets us a new access token).
      if($grantType != 'refresh_token') {
        $data['refresh_token'] = $json->refresh_token;
      }
      
      // We don't want to change any attributes not specified above
      $this->save($data, true, array_keys($data));
    }
    
    return $json;
  }
  
  /**
   * OBtain the redirect URI for this Oauth2Server.
   * 
   * @since  COmanage Registry v3.2.0
   * @param  Integer $id          Oauth2Server ID
   * @return String Callback URI
   */
  
  public function redirectUri($id) {
    $callback = array(
      'controller' => 'oauth2_servers',
      'action'     => 'callback',
      $id
    );
    
    return Router::url($callback, array('full' => true));
  }
  
  /**
   * Refresh the access token for this Oauth2Server.
   * 
   * @since  COmanage Registry v3.2.0
   * @return String New access token
   * @throws RuntimeException
   */
  
  public function refreshToken($id) {
    $json = $this->obtainToken($id, 'refresh_token');
    
    return $json->access_token;
  }
}
