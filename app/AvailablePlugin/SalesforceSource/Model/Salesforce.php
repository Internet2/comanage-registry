<?php
/**
 * COmanage Registry Salesforce Connection Model
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @todo          Merge with SalesforceProvisioner/Model/Salesforce.php (CO-1593)
 */

App::uses("OrgIdentitySource", "Model");
App::uses("SalesforceSource", "SalesforceSource.Model");
App::uses("Oauth2Server", "Model");
App::uses('CoHttpClient', 'Lib');

class Salesforce {
  // Local copy of access token, in case it's updated in-flight
  protected $accessToken = null;
  protected $instanceUrl = null;
  
  protected $Http = null;
  protected $srvr = null;
  
  /**
   * Establish a connection to the Salesforce API.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $serverId Server ID
   * @param  Integer $sfsrcid  SalesforceSource ID
   * @return Boolean True on success
   * @throws InvalidArgumentException
   */
  
  public function connect($serverId, $sfsrcid) {
    $OrgIdentitySource = new OrgIdentitySource();
    
    // Pull the Server configuration
    
    $args = array();
    $args['conditions']['Server.id'] = $serverId;
    $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = array('Oauth2Server');
    
    $srvr = $OrgIdentitySource->Co->Server->find('first', $args);
    
    // Store the server config in case we need to refresh the access token
    $this->srvr = $srvr;
    
    if(empty($srvr)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.servers.1'), $serverId)));
    }
    
    // Do we have a valid access token?
    if(empty($srvr['Oauth2Server']['access_token'])) {
      throw new InvalidArgumentException(_txt('er.server.oauth2.token.none'));
    }
    
    $this->accessToken = $srvr['Oauth2Server']['access_token'];
    
    // Make sure we have an instance_url
    
    if(!empty($coProvisioningTargetData['CoSalesforceProvisionerTarget']['instance_url'])) {
      $this->instanceUrl = $coProvisioningTargetData['CoSalesforceProvisionerTarget']['instance_url'];
    } else {
      // The instance_url is embedded in the token response from the authorization process.
      
      $json = json_decode($srvr['Oauth2Server']['token_response']);
      
      if(empty($json->instance_url)) {
        throw new RuntimeException(_txt('er.salesforcesource.instanceurl'));
      }
      
      $this->instanceUrl = (string)$json->instance_url;
      
      // Store the instance URL
      $SalesforceSource = new SalesforceSource();
      
      $SalesforceSource->id = $sfsrcid;
      $SalesforceSource->saveField('instance_url', $this->instanceUrl);
    }
    
    $this->Http = new CoHttpClient();
    $this->Http->setBaseUrl($this->instanceUrl);
    
    return true;
  }
  
  /**
   * Make a request to the Salesforce API.
   * 
   * @since  COmanage Registry v3.2.0
   * @param  String  $urlPath     URL Path to request from API
   * @param  Array   $data        Array of query paramaters
   * @param  String  $action      HTTP action
   * @param  Boolean $abortOnFail If true, do not retry on error (eg: invalid token)
   * @return Array   Decoded json message body
   * @throws RuntimeException
   */
  
  public function request($urlPath, $data=array(), $action="get", $abortOnFail=false) {
    $options = array(
      'header' => array(
        'Accept'        => 'application/json',
        'Authorization' => 'Bearer ' . $this->accessToken,
        'Content-Type'  => 'application/json'
      )
    );
    
    $results = $this->Http->$action($urlPath,
                                    // $data is json_encoded in Provisioner, make consistent?
                                    $data,
                                    $options);
    
    $json = json_decode($results->body);
    
    if(!$abortOnFail
       && $results->code == 401
       && !empty($json[0]->errorCode)
       && $json[0]->errorCode == 'INVALID_SESSION_ID') {
      $Oauth2Server = new Oauth2Server();
      
      $newAccessToken = $Oauth2Server->refreshToken($this->srvr['Oauth2Server']['id']);
      
      // Update our cached token.
      // We could also clear instance_url here but we don't know if it was manually set...
      $this->accessToken = $newAccessToken;
      
      // Try again, max 1 more time so we don't loop
      return $this->request($urlPath, $data, $action, true);
    }
    
    if($results->code >= 400) {
      // Some sort of error occurred
      
      throw new RuntimeException($json[0]->errorCode . ": " . $json[0]->message);
    }
    
    // Salesforce includes some elements we don't need that change on each request,
    // impacting our ability to detect record changes, so we remove them.
    // (We could instead use LastModifiedDate to detect changes, but the OIS
    // infrastructure doesn't currently support that approach.)
    
    unset($json->LastViewedDate);
    unset($json->LastReferencedDate);
    
    return $json;
  }
}
