<?php
/**
 * COmanage Registry MidPoint REST API Client
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses('CoHttpClient', 'Lib');
App::uses('Xml', 'Utility');

/**
 * MidPoint REST API client.
 *
 * @see https://wiki.evolveum.com/display/midPoint/REST+API
 */
class MidPointRestApiClient extends CakeObject {

  /** @var string REST API URL */
  private $serverUrl;

  /** @var string REST API username */
  private $username;

  /** @var string REST API password */
  private $password;

  private $ssl_allow_self_signed;

  private $ssl_verify_host;

  private $ssl_verify_peer;

  private $ssl_verify_peer_name;

  /**
   * MidPointRestApiClient constructor.
   */
  public function __construct($coProvisioningTargetData) {

    // TODO validate

    $this->serverUrl = $coProvisioningTargetData['CoMidPointProvisionerTarget']['serverurl'];
    $this->username  = $coProvisioningTargetData['CoMidPointProvisionerTarget']['username'];
    $this->password  = $coProvisioningTargetData['CoMidPointProvisionerTarget']['password'];

    $this->ssl_allow_self_signed = $coProvisioningTargetData['CoMidPointProvisionerTarget']['ssl_allow_self_signed'];
    $this->ssl_verify_host       = $coProvisioningTargetData['CoMidPointProvisionerTarget']['ssl_verify_host'];
    $this->ssl_verify_peer       = $coProvisioningTargetData['CoMidPointProvisionerTarget']['ssl_verify_peer'];
    $this->ssl_verify_peer_name  = $coProvisioningTargetData['CoMidPointProvisionerTarget']['ssl_verify_peer_name'];

    if (false) {
      CakeLog::debug("MidPoint URL                   : $this->serverUrl");
      CakeLog::debug("MidPoint username              : $this->username");
      CakeLog::debug("MidPoint ssl_allow_self_signed : $this->ssl_allow_self_signed");
      CakeLog::debug("MidPoint ssl_verify_host       : $this->ssl_verify_host");
      CakeLog::debug("MidPoint ssl_verify_peer       : $this->ssl_verify_peer");
      CakeLog::debug("MidPoint ssl_verify_peer_name  : $this->ssl_verify_peer_name");
    }
  }

  public function buildHttpClient() {
    $Http = new CoHttpClient(
      array(
        'ssl_allow_self_signed' => $this->ssl_allow_self_signed,
        'ssl_verify_host'       => $this->ssl_verify_host,
        'ssl_verify_peer'       => $this->ssl_verify_peer,
        'ssl_verify_peer_name'  => $this->ssl_verify_peer_name,
      )
    );
    $Http->setBaseUrl($this->serverUrl);
    $Http->configAuth('Basic', $this->username, $this->password);
    return $Http;
  }

  /**
   * @return string TODO
   */
  public function logPrefix() {
    return "MidPointRestApiClient " . $this->serverUrl;
  }
  /**
   * Create a midPoint user.
   *
   * @since COmanage Registry 3.3.0
   * @param string $xml XML representation of user
   * @return string OID of midPoint User
   * @throws RuntimeException if an error occurs
   */
  public function createUser($xml) {
    // TODO validate xml

    $request = array(
      'header' => array(
        'Content-Type' => 'application/xml'
      )
    );

    $http = $this->buildHttpClient();

    $this->log($this->logPrefix() . " Attempting to create user :\n" . $xml, 'debug');
    $results = $http->post('/ws/rest/users/', $xml, $request);

    if ($results->code != 201) {
      $this->log($this->logPrefix() . " Unable to create user :\n" . $results, 'debug');
      throw new RuntimeException($results->reasonPhrase);
    }

    $oid = MidPointRestApiClient::extractOidFromLocation($results->getHeader('Location'));
    $this->log($this->logPrefix() . " Created user with oid " . $oid, 'info');
    return $oid;
  }

  public static function extractOidFromLocation($url) {
    return basename(parse_url($url, PHP_URL_PATH));
  }

  /**
   * Get a MidPoint user.
   *
   * @param $oid
   *
   * @return array if found, empty array if not found, throw exception otherwise
   */
  public function getUser($oid) {

    // TODO validate oid

    $url = '/ws/rest/users/' . $oid;

    $http = $this->buildHttpClient();

    $this->log($this->logPrefix() . " Attempting to get user with oid " . $oid, 'debug');

    $results = $http->get($url);

    if ($results->isOk()) {
      $this->log($this->logPrefix() . " Found user with oid " . $oid, 'debug');
      return Xml::toArray(Xml::build($results->body()));
    }

    if ($results->code == 404) {
      $this->log($this->logPrefix() . " Did not find user with oid " . $oid, 'debug');
      return array();
    }

    throw new RuntimeException($results->reasonPhrase);
  }

  /**
   * Delete a midPoint user.
   *
   * @param $oid OID of midPoint user
   *
   * @throws RuntimeException if an error occurs
   */
  public function deleteUser($oid) {

    // TODO validate oid

    $url = '/ws/rest/users/' . $oid;

    $http = $this->buildHttpClient();

    $this->log($this->logPrefix() . " Attempting to delete user with oid " . $oid, 'debug');

    $results = $http->delete($url);

    if (!$results->isOk()) {
      $this->log($this->logPrefix() . " Unable to delete user :\n" . $results, 'debug');
      throw new RuntimeException($results->reasonPhrase);
    }

    $this->log($this->logPrefix() . " Deleted user with oid " . $oid, 'debug');
  }

}