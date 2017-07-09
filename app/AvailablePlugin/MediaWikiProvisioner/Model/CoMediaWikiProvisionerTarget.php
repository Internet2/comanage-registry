<?php
/**
 * COmanage Registry CO MediaWiki Provisioner Target Model
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

App::uses("CoProvisionerPluginTarget", "Model", "ConnectionManager");
App::uses('MediaWikiApiClient', 'MediaWikiProvisioner.Lib');
App::uses('MediaWikiApiClientException', 'MediaWikiProvisioner.Lib');

class CoMediaWikiProvisionerTarget extends CoProvisionerPluginTarget {

  // Define class name for cake
  public $name = "CoMediaWikiProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");
  
  public $hasMany = array();
  
  // Default display field for cake generated views
  public $displayField = "api_url";
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false,
      'on' => null,
      'message' => 'A CO Provisioning Target ID must be provided'
    ),
    'api_url' => array(
      'rule' => array('custom', '/^https?:\/\/.*/'),
      'required' => true,
      'allowEmpty' => false,
      'on' => null,
      'message' => 'Please enter a valid http or https URL'
    ),
    'consumer_key' => array(
      'rule' => 'notBlank',
      'required' => true,
      'on' => null,
      'allowEmpty' => false,
    ),
    'consumer_secret' => array(
      'rule' => 'notBlank',
      'required' => true,
      'on' => null,
      'allowEmpty' => false,
    ),
    'access_token' => array(
      'rule' => 'notBlank',
      'required' => true,
      'on' => null,
      'allowEmpty' => false,
    ),
    'access_secret' => array(
      'rule' => 'notBlank',
      'required' => true,
      'on' => null,
      'allowEmpty' => false,
    ),
    'user_name_identifier' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true,
      'on' => null,
    )
  );

  /**
   * Get the username based on provisioner configuration
   *
   * @since COmanage Registry 3.1.0
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   * @return string Username
   */

  public function getUsername($coProvisioningTargetData, $provisioningData) {
    if (isset($provisioningData['CoPerson'])) {
      $coId = $provisioningData['CoPerson']['co_id'];
      $coPersonId = $provisioningData['CoPerson']['id'];
    } else {
      return null;
    }

    // Select the identifier from the CoPerson based on the provisioner configuration.
    $idType = $coProvisioningTargetData['CoMediaWikiProvisionerTarget']['user_name_identifier'];

    // Try to query to find the identifier.
    $args = array();
    $args['conditions']['Identifier.co_person_id'] = $coPersonId;
    $args['conditions']['Identifier.type'] = $idType;
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['Identifier.deleted'] = false;
    $args['contain'] = false;

    $identifier = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);
    if ($identifier) {
      return $identifier['Identifier']['identifier'];
    }

    // If we fall through we were not able to find an identifier so return null
    // and expect the caller to handle appropriately.
    return null;
  }

  /**
   * Create and return an instance of the MediaWikiApiClient
   *
   * @since COmanage Registry 3.1.0
   * @param array $coProvisioningTargetData CO provisioning target data
   * @return instance MediaWikiApiClient or null if unable to create the instance
   */

  public function mediaWikiApiClientFactory($coProvisioningTargetData) {
    $apiUrl = $coProvisioningTargetData['CoMediaWikiProvisionerTarget']['api_url'];    
    $consumerKey = $coProvisioningTargetData['CoMediaWikiProvisionerTarget']['consumer_key'];    
    $consumerSecret = $coProvisioningTargetData['CoMediaWikiProvisionerTarget']['consumer_secret'];    
    $accessToken = $coProvisioningTargetData['CoMediaWikiProvisionerTarget']['access_token'];    
    $accessSecret = $coProvisioningTargetData['CoMediaWikiProvisionerTarget']['access_secret'];    

    try {
      $client = new MediaWikiApiClient($apiUrl, $consumerKey, $consumerSecret, $accessToken, $accessSecret);
    } catch (MediaWikiApiClientException $e) {
      $this->log("MediaWikiProvisioner unable to create new MediaWikiApiClient: " . $e->getMessage());
      return null;
    }

    return $client;
  }
  
  /**
   * Determine the provisioning status of this target.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer CO Provisioning Target ID
   * @param  Integer CO Person ID (null if CO Group ID is specified)
   * @param  Integer CO Group ID (null if CO Person ID is specified)
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   * @throws RuntimeException 
   */
  
  public function status($coProvisioningTargetId, $coPersonId, $coGroupId=null) {
    $ret = array(
      'status'    => ProvisioningStatusEnum::Unknown,
      'timestamp' => null,
      'comment'   => ""
    );

   return $ret;
  }
  
  /**
   * Provision for the specified CO Person or CO Group.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */

  public function provision($coProvisioningTargetData, $op, $provisioningData) {

    switch($op) {
      // MediaWiki does not have the notion of deleting users
      // so these provisioning actions are no-op.
      case ProvisioningActionEnum::CoPersonDeleted:
      case ProvisioningActionEnum::CoPersonExpired:
        return true;

      // Provision new wiki user. 
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        return $this->provisionCoPerson($coProvisioningTargetData, $provisioningData);

      // Group provisioning operations are no-op
      case ProvisioningActionEnum::CoGroupAdded:
      case ProvisioningActionEnum::CoGroupDeleted:
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
      case ProvisioningActionEnum::CoGroupUpdated:
        return true;

      default:
        // Pass through for any provisioning actions not yet implemented.
        break;
    }
    
    return true;
  }

  /**
   * Provision CoPerson as MediaWiki user
   *
   * @since  COmanage Registry v3.1.0
   * @param  array $coProvisioningTargetData CO Provisioning Target data
   * @param  array $provisioningData Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return boolean True on success
   * @throws RuntimeException 
   */

  public function provisionCoPerson($coProvisioningTargetData, $provisioningData) {
    $client = $this->mediaWikiApiClientFactory($coProvisioningTargetData);

    if(!$client) {
      $msg = "Unable to create MediaWikiApiClient";
      throw new RuntimeException($msg);
    }

    $username = $this->getUsername($coProvisioningTargetData, $provisioningData);
    if (empty($username)) {
      $msg = "MediaWikiProvisioner unable to determine subject for CoPerson " . $provisioningData['CoPerson']['id'];
      $this->log($msg);
      throw new RuntimeException($msg);
    }

    // We rank the possible emails and take the highest ranked
    if(isset($provisioningData['EmailAddress'])) {
      $ranking = array();
      foreach($provisioningData['EmailAddress'] as $i => $m) {
        if($m['type'] == EmailAddressEnum::Recovery) {
          $ranking[$i] = array($m['mail'], 1);
        } elseif($m['type'] == EmailAddressEnum::Personal) {
          $ranking[$i] = array($m['mail'], 2);
        } elseif($m['type'] == EmailAddressEnum::Forwarding) {
          $ranking[$i] = array($m['mail'], 3);
        } elseif($m['type'] == EmailAddressEnum::Official) {
          $ranking[$i] = array($m['mail'], 4);
        } elseif($m['type'] == EmailAddressEnum::Preferred) {
          $ranking[$i] = array($m['mail'], 5);
        } elseif($m['type'] == EmailAddressEnum::Delivery) {
          $ranking[$i] = array($m['mail'], 6);
        }
      } 
      asort($ranking);
      $email = array_pop($ranking)[0];
    } else {
      $msg = "MediaWikiProvisioner unable to determine email for CoPerson " . $provisioningData['CoPerson']['id'];
      $this->log($msg);
      throw new RuntimeException($msg);
    }

    // We use PrimaryName as the real user name
    if(isset($provisioningData['PrimaryName']['given'])) {
      $givenName = trim($provisioningData['PrimaryName']['given']);
    } else {
      $givenName = '';
    }

    if(isset($provisioningData['PrimaryName']['middle'])) {
      $middleName = trim($provisioningData['PrimaryName']['middle']);
    } else {
      $middleName = '';
    }

    if(isset($provisioningData['PrimaryName']['family'])) {
      $familyName = trim($provisioningData['PrimaryName']['family']);
    } else {
      $familyName = '';
    }

    $realName = $givenName;
    if ($middleName) {
      $realName = "$realName $middleName";
    }
    if ($familyName) {
      $realName = "$realName $familyName";
    }

    try {
      $created = $client->createAccount($username, $email, $realName);
    } catch (MediaWikiApiClientException $e) {
      throw new RuntimeException($e->getMessage());
    }

    if(!$created) {
      $msg = "Unable to provision account to MediaWiki";
      $this->log($msg);
      throw new RuntimeException($msg);
    }    

    return true;
  }

  /**
   * Test a MediaWiki API server to verify that the connection available is valid.
   *
   * @since  COmanage Registry v3.1.0
   * @param  string $apiUrl API URL
   * @param  string $consumerKey OAuth consumer key
   * @param  string $consumerSecret OAuth consumer secret 
   * @param  string $accesstoken OAuth access token
   * @param  string $accessSecret OAuth access secret
   * @return Boolean True if parameters are valid
   * @throws RuntimeException
   */
  
  public function verifyApiServer($apiUrl, $consumerKey, $consumerSecret, $accessToken, $accessSecret) {

    // Verify we can connect to the server and obtain createaccount token
    try {
      $client = new MediaWikiApiClient($apiUrl, $consumerKey, $consumerSecret, $accessToken, $accessSecret);
      $createAccountToken = $client->getCreateAccountToken();
    } catch (MediaWikiApiClientException $e) {
      throw new RuntimeException($e->getMessage());
    }

    return true;
  }
}
