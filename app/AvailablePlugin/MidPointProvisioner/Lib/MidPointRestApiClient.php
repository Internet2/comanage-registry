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
App::uses("Server", "Model");
App::uses('Xml', 'Utility');

/**
 * MidPoint REST API client.
 *
 * @see https://wiki.evolveum.com/display/midPoint/REST+API
 */
class MidPointRestApiClient extends CakeObject {

  /** @var string Server id */
  private $serverId;

  /** @var string Server description */
  private $serverDescription;

  /**
   * MidPointRestApiClient constructor.
   */
  public function __construct($serverId) {
    // TODO validate ?
    $this->serverId = $serverId;
  }

  /**
   * Build a new CoHttpClient.
   *
   * @return CoHttpClient The built CoHttpClient
   */
  public function buildHttpClient() {

    // TODO validate username and password ?

    $Server = ClassRegistry::init('Server');

    $args = array();
    $args['conditions']['Server.id'] = $this->serverId;
    $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = array('HttpServer');

    $server = $Server->find('first', $args);
    if (!$server) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.servers.1'), $this->serverId)));
    }

    // Save server description for log prefix
    $this->serverDescription = $server['Server']['description'];

    // Pass non-null ssl_* context options to HttpClient
    $ssl_config = array_filter($server['HttpServer'],
      function ($value, $key) {
        return preg_match('/^ssl_/', $key) && !is_null($value);
      },
      ARRAY_FILTER_USE_BOTH);
    $Http = new CoHttpClient($ssl_config);

    $Http->setBaseUrl($server['HttpServer']['serverurl']);
    $Http->configAuth('Basic', $server['HttpServer']['username'], $server['HttpServer']['password']);

    return $Http;
  }

  /**
   * Build XML representing a midPoint user.
   *
   * @param array $user Array representing a midPint user.
   *
   * @return mixed
   */
  public static function buildUserXml(array $user) {
    // TODO validate $provisioningData
    $data = array(
      'user' => array(
        'xmlns:' => 'http://midpoint.evolveum.com/xml/ns/public/common/common-3',
      )
    );
    $data = array_merge_recursive($data, $user);
    $options = array('pretty' => true);
    $xmlObject = Xml::fromArray($data, $options);
    return $xmlObject->asXML();
  }

  public static function buildUserModificationXml(array $mods) {
    $itemDeltas = array();
    foreach ($mods as $op => $attr) {
      // TODO op should be one of add, delete, replace
      foreach ($attr as $path => $value) {
        // TODO path should be one of givenName, familyName ...
        $itemDelta = array(
          't:modificationType' => $op,
          't:path' => 'c:' . $path
        );
        if (isset($value)) {
          $itemDelta['t:value'] = $value;
        }
        array_push($itemDeltas, $itemDelta);
      }
    }

    $data = array(
      'objectModification' => array(
        'xmlns:' => 'http://midpoint.evolveum.com/xml/ns/public/common/api-types-3',
        'xmlns:c' => 'http://midpoint.evolveum.com/xml/ns/public/common/common-3',
        'xmlns:t' => 'http://prism.evolveum.com/xml/ns/public/types-3',
        'itemDelta' => $itemDeltas
      )
    );

    $options = array('pretty' => true);
    $xmlObject = Xml::fromArray($data, $options);
    return $xmlObject->asXML();
  }

  /**
   * Create a midPoint user.
   *
   * @param string $xml XML representation of user
   *
   * @return string OID of midPoint User
   * @throws RuntimeException if an error occurs
   * @since COmanage Registry 3.3.0
   */
  public function createUser($xml) {
    // TODO validate xml

    $request = array(
      'header' => array(
        'Content-Type' => 'application/xml'
      )
    );

    $http = $this->buildHttpClient();

    if (Configure::read('debug')) {
      $msg = $this->logPrefix() . "Attempting to create user :\n$xml";
      $this->log($msg, 'debug');
    }

    $results = $http->post('/users/', $xml, $request);

    if ($results->code != 201) {
      $this->log($this->logPrefix() . "Unable to create user :\n" . $results, 'debug');
      throw new RuntimeException($results->reasonPhrase);
    }

    $oid = MidPointRestApiClient::extractOidFromLocation($results->getHeader('Location'));

    if (Configure::read('debug')) {
      $msg = $this->logPrefix() . "Created user with oid $oid";
      $this->log($msg, 'debug');
    }

    return $oid;
  }

  /**
   * Create a midPoint user.
   *
   * @param array $user Array representing a midPoint user
   * @return string OID of midPoint User
   * @throws RuntimeException if an error occurs
   */
  public function createUserFromArray($user) {
    $xml = MidPointRestApiClient::buildUserXml($user);
    return $this->createUser($xml);
  }

  /**
   * Delete a midPoint user.
   *
   * @param $oid OID of midPoint user
   *
   * @return Boolean true on success
   * @throws RuntimeException if an error occurs
   */
  public function deleteUser($oid) {

    // TODO validate oid

    $url = '/users/' . $oid;

    $http = $this->buildHttpClient();

    if (Configure::read('debug')) {
      $this->log($this->logPrefix() . "Attempting to delete user with oid " . $oid, 'debug');
    }

    $results = $http->delete($url);

    if (!$results->isOk()) {
      $this->log($this->logPrefix() . "Unable to delete user :\n" . $results, 'debug');
      throw new RuntimeException($results->reasonPhrase);
    }

    if (Configure::read('debug')) {
      $this->log($this->logPrefix() . "Deleted user with oid " . $oid, 'debug');
    }

    return true;
  }

  /**
   * Get a midPoint user.
   *
   * @param $oid OID of midPoint user
   *
   * @return array if found, empty array if not found, throw exception otherwise
   * @throws RuntimeException if an error occurs
   */
  public function getUser($oid) {

    // TODO validate oid

    $url = '/users/' . $oid;

    $http = $this->buildHttpClient();

    if (Configure::read('debug')) {
      $msg = $this->logPrefix() . "Attempting to get user with oid $oid";
      $this->log($msg, 'debug');
    }

    $results = $http->get($url);

    if ($results->isOk()) {
      if (Configure::read('debug')) {
        $msg = $this->logPrefix() . "Found user with oid $oid ";
        $msg .= var_export($results, true);
        $this->log($msg, 'debug');
      }
      return Xml::toArray(Xml::build($results->body()));
    }

    if ($results->code == 404) {
      $this->log($this->logPrefix() . "Did not find user with oid " . $oid, 'debug');
      return array();
    }

    throw new RuntimeException($results->reasonPhrase);
  }

  /**
   * Modify a midPoint user.
   *
   * @param $oid OID of midPoint user
   * @param string $xml XML representation of user modifications
   *
   * @return Boolean true on success
   * @throws RuntimeException if an error occurs
   */
  public function modifyUser($oid, $xml) {
    $request = array(
      'header' => array(
        'Content-Type' => 'application/xml'
      )
    );

    $url = '/users/' . $oid;

    $http = $this->buildHttpClient();

    if (Configure::read('debug')) {
      $msg = $this->logPrefix() . "Attempting to modify user with oid $oid :\n$xml";
      $this->log($msg, 'debug');
    }

    $results = $http->patch($url, $xml, $request);

    if (!$results->isOk()) {
      $this->log($this->logPrefix() . "Unable to modify user :\n" . $results, 'debug');
      throw new RuntimeException($results->reasonPhrase);
    }

    if (Configure::read('debug')) {
      $this->log($this->logPrefix() . "Modified user with oid " . $oid, 'debug');
    }

    return true;
  }

  /**
   * Modify a midPoint user.
   *
   * @param $oid OID of midPoint user
   * @param $mods Array representing modifications to a midPoint user
   *
   * @return Boolean true on success
   * @throws RuntimeException if an error occurs
   */
  public function modifyUserFromArray($oid, $mods) {
    $xml = MidPointRestApiClient::buildUserModificationXml($mods);
    return $this->modifyUser($oid, $xml);
  }

  /**
   * Extract the OID from a midPoint user URL.
   *
   * @param string $url The URL to parse
   *
   * @return string OID
   * // TODO return OID or null or ?
   */
  public static function extractOidFromLocation($url) {
    return basename(parse_url($url, PHP_URL_PATH));
  }

  /**
   * Return log prefix.
   *
   * @return string Log prefix.
   */
  public function logPrefix() {
    return "MidPoint " . $this->serverDescription . " ";
  }
}