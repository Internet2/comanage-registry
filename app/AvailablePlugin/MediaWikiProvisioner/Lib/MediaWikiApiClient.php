<?php
/**
 * COmanage Registry MediaWiki API Client
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
 * @version       $Id$
 */

App::uses('MediaWikiApiClientException', 'MediaWikiProvisioner.Lib');
App::uses('CakeLog', 'Log');

/**
 * An instance is used to invoke the MediaWiki API using OAuth.
 */
class MediaWikiApiClient extends OAuth {
  // API URL
  private $apiUrl;

  // OAuth consumer key
  private $consumerKey;

  // OAuth consumer secret
  private $consumerSecret;

  // OAuth access token
  private $accessToken;

  // OAuth access secret
  private $accessSecret;

  /**
   * Constructor for MediaWikiApiClient
   *
   * @since  COmanage Directory 3.1.0
   * @param  string $consumerKey API OAuth consumer key
   * @param  string $consumerSecret API OAuth consumer secret
   * @param  string $accessToken API OAuth access token
   * @param  string $accessSecret API OAuth access secret
   * @return instance
   */

  public function __construct($apiUrl, 
                       $consumerKey, 
                       $consumerSecret, 
                       $accessToken, 
                       $accessSecret) {
    
    $this->apiUrl = $apiUrl;
    $this->consumerKey = $consumerKey;
    $this->consumerSecret = $consumerSecret;
    $this->accessToken = $accessToken;
    $this->accessSecret = $accessSecret;

    parent::__construct($consumerKey, 
                        $consumerSecret, 
                        OAUTH_SIG_METHOD_HMACSHA1, 
                        OAUTH_AUTH_TYPE_AUTHORIZATION);
    
    $this->setToken($accessToken, $accessSecret);

  }

  /**
   * Create an account
   *
   * @since  COmanage Registry v3.1.0
   * @param  string $username Username for account
   * @param  string $email Email for account
   * @param  string $realName Real name for account
   * @return boolean True if account created
   */

  public function createAccount($username, $email, $realName) {
    try {
      $createAccountToken = $this->getCreateAccountToken();
    } catch (MediaWikiApiClienException $e) {
      $msg = 'MediaWikiApiClient error creating account: ' . $e->getMessage();
      if(Configure::read('debug')) {
        CakeLog::write('error', $msg);
      }
      throw new MediaWikiApiClientException($msg, 1, $e);
    }

    $queryData = array();
    $queryData['action'] = 'createaccount';
    $queryData['format'] = 'json';
    $queryData['createreturnurl'] = 'https://some.org';
    $queryData['username'] = $username;
    $queryData['email'] = $email;
    $queryData['realname'] = $realName;
    $queryData['reason'] = 'provisioning';

    $url = $this->apiUrl . "?" . http_build_query($queryData);

    $bytes = openssl_random_pseudo_bytes(20, $cstrong);
    if(!$cstrong) {
      $msg = 'MediaWikiApiClient error creating account: could not generate random password';
      if(Configure::read('debug')) {
        CakeLog::write('error', $msg);
      }
      throw new MediaWikiApiClientException($msg, 1);
    }

    $password   = bin2hex($bytes);

    $parameters = array();
    $parameters['createtoken'] = $createAccountToken;
    $parameters['password'] = $password;
    $parameters['retype'] = $password;

    try {
      $this->fetch($url, $parameters, OAUTH_HTTP_METHOD_POST);
    } catch (Exception $e) {
      $msg = 'MediaWikiApiClient error creating account: ' . $e->getMessage();
      if(Configure::read('debug')) {
        CakeLog::write('error', $msg);
        CakeLog::write('error', 'Query data was ' . print_r($queryData, true));
        CakeLog::write('error', 'Url was ' . print_r($url, true));
        CakeLog::write('error', 'Parameters were ' . print_r($parameters, true));
      }
      throw new MediaWikiApiClientException($msg, 1, $e);
    }

    $responseInfo = $this->getLastResponseInfo();

    if ($responseInfo['http_code'] != 200 || !preg_match('/^application\/json/', $responseInfo['content_type'])) {
      $msg = "MediaWikiApiClient error creating account";
      if(Configure::read('debug')) {
        CakeLog::write('error', $msg);
        CakeLog::write('error', 'MediaWikiApiClient response code was ' . $responseInfo['http_code']);
        CakeLog::write('error', 'MediaWikiApiClient content type was ' . $responseInfo['content_type']);
      }
      throw new MediaWikiApiClientException($msg, 1);
    }

    $response = json_decode($this->getLastResponse(), true);

    if (isset($response['createaccount']['status'])) {
      $status = $response['createaccount']['status'];
      
      if ($status == 'PASS') {
        if (isset($response['createaccount']['username'])) {
          $created = $response['createaccount']['username'];
          if ($created == $username) {
            return true;
          }
        }
      } elseif ($status == 'FAIL') {
        if (isset($response['createaccount']['message'])) {
          $message = $response['createaccount']['message'];
          if (preg_match("/Username entered already in use/", $message)) {
            return true;
          }
        }
      }
    }

    // If fall through to here then there was an issue 
    // creating the account.

    $msg = "MediaWikiApiClient error creating account";
    if (isset($response['error']['code'])) {
      $msg = $msg . ": error code is " . $response['error']['code'];
    }
    if (isset($response['error']['info'])) {
      $msg = $msg . ": error info is " . $response['error']['info'];
    }
    if(Configure::read('debug')) {
      CakeLog::write('error', $msg);
      CakeLog::write('error', 'MediaWikiApiClient response was ' . print_r($response, true));
    }
    throw new MediaWikiApiClientException($msg, 1);
  }

  /**
   * Get a createaccount token
   *
   * @since  COmanage Registry v3.1.0
   * @return string createaccount token
   */

  public function getCreateAccountToken() {
    $queryData = array();
    $queryData['action'] = 'query';
    $queryData['meta'] = 'tokens';
    $queryData['type'] = 'createaccount';
    $queryData['format'] = 'json';

    $url = $this->apiUrl . "?" . http_build_query($queryData);

    try {
      $this->fetch($url, null, OAUTH_HTTP_METHOD_POST);
    } catch (Exception $e) {
      $msg = 'MediaWikiApiClient error getting createaccount token: ' . $e->getMessage();
      if(Configure::read('debug')) {
        CakeLog::write('error', $msg);
        CakeLog::write('error', 'Query data was ' . print_r($queryData, true));
        CakeLog::write('error', 'URL was ' . print_r($url, true));
      }
      throw new MediaWikiApiClientException($msg, 1, $e);
    }

    $responseInfo = $this->getLastResponseInfo();

    if ($responseInfo['http_code'] != 200 || !preg_match('/^application\/json/', $responseInfo['content_type'])) {
      $msg = "MediaWikiApiClient error getting createaccount token";
      if(Configure::read('debug')) {
        CakeLog::write('error', $msg);
        CakeLog::write('error', 'MediaWikiApiClient response code was ' . $responseInfo['http_code']);
        CakeLog::write('error', 'MediaWikiApiClient content type was ' . $responseInfo['content_type']);
      }
      throw new MediaWikiApiClientException($msg, 1);
    }

    $response = json_decode($this->getLastResponse(), true);

    if (!isset($response['query']['tokens']['createaccounttoken'])) {
      $msg = "MediaWikiApiClient error getting createaccount token";
      if(Configure::read('debug')) {
        CakeLog::write('error', $msg);
        CakeLog::write('error', 'MediaWikiApiClient response was ' . $print_r($response, true));
      }
      throw new MediaWikiApiClientException($msg, 1);
    }

    $createAccountToken = $response['query']['tokens']['createaccounttoken'];

    return $createAccountToken;
  }

}
