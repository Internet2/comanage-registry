<?php
/**
 * COmanage Registry CO MidPoint Provisioner Target Model
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
 */

App::uses('MidPointRestApiClient', 'MidPointProvisioner.Lib');
App::uses("CoProvisionerPluginTarget", "Model");

/**
 * MidPoint provisioner target.
 *
 * NOTE: API input and output are arrays
 *
 * @see https://wiki.evolveum.com/display/midPoint/REST+API
 * @see https://wiki.evolveum.com/display/midPoint/MidPoint+Common+Schema
 */
class CoMidPointProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoMidPointProvisionerTarget";

  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");

  // Request HTTP servers
  public $cmServerType = ServerEnum::HttpServer;

  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Provisioning Target ID must be provided'
    ),
    'server_id' => array(
      'rule' => 'numeric',
      'required' => true
    )
  );

  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    $this->log("MidPointProvisioner provisioning action $op", 'debug');

    switch ($op) {
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonDeleted:
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
      case ProvisioningActionEnum::CoPersonExpired:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        return $this->provisionCoPerson($coProvisioningTargetData, $provisioningData);

      default:
        // Log noop and fall through.
        $this->log("MidPointProvisioner provisioning action $op not implemented");
    }

    return true;
  }

  /**
   * Provision a CO person as a midPoint user.
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return boolean true if the CO person was provisioned to midPoint as a user successfully, false otherwise
   */
  public function provisionCoPerson($coProvisioningTargetData, $provisioningData) {
    // Should the user be provisioned ?
    $provisionable = $this->isUserProvisionable($coProvisioningTargetData, $provisioningData);

    // Is the user already provisioned ?
    $provisioned = $this->isUserProvisioned($coProvisioningTargetData, $provisioningData);

    // Create user if user should be provisioned but is not provisioned
    if ($provisionable and !$provisioned) {
      return $this->createUser($coProvisioningTargetData, $provisioningData);
    }

    // Update user if user should be provisioned and is already provisioned
    if ($provisionable and $provisioned) {
      return $this->updateUser($coProvisioningTargetData, $provisioningData);
    }

    // Archive user if user should not be provisioned but is already provisioned
    if (!$provisionable and $provisioned) {
      return $this->deleteUser($coProvisioningTargetData, $provisioningData);
    }

    // Nothing to do
    $this->log("MidPointProvisioner nothing to do", 'debug');
    return true;
  }

  /**
   * Create a midPoint user.
   *
   * Saves the midPoint identifier (OID).
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return boolean true if the midPoint user was created successfully, false otherwise.
   */
  public function createUser($coProvisioningTargetData, $provisioningData) {
    // Calculate how user should be provisioned
    $user = $this->calcUser($coProvisioningTargetData, $provisioningData);

    // Connect to MidPoint
    $api = new MidPointRestApiClient($coProvisioningTargetData['CoMidPointProvisionerTarget']['server_id']);

    // Create MidPoint user
    $oid = $api->createUserFromArray($user);

    // Return false if unable to create MidPoint user
    if (empty($oid)) {
      return false;
    }

    // Save MidPoint identifier
    $id = $this->saveIdentifier($coProvisioningTargetData, $provisioningData, $oid);

    // Return false if unable to save MidPoint identifier
    if (empty($id)) {
      return false;
    }

    return true;
  }

  /**
   * Create a midPoint user.
   *
   * Deletes the midPoint identifier (OID).
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return boolean true if the midPoint user was deleted successfully, false otherwise.
   */
  public function deleteUser($coProvisioningTargetData, $provisioningData) {
    // Find MidPoint identifier
    $oid = $this->findIdentifier($coProvisioningTargetData, $provisioningData);

    // Return false if unable to find MidPoint user
    if (empty($oid)) {
      // TODO unable to find user
      return false;
    }

    // Connect to MidPoint
    $api = new MidPointRestApiClient($coProvisioningTargetData['CoMidPointProvisionerTarget']['server_id']);

    // Delete MidPoint user
    if ($api->deleteUser($oid)) {
      // Delete MidPoint identifier
      return $this->deleteIdentifier($coProvisioningTargetData, $provisioningData);
    }

    return false;
  }

  /**
   * Update a midPoint user.
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return boolean true if the midPoint user was modified successfully or no changes are necessary, false otherwise.
   */
  public function updateUser($coProvisioningTargetData, $provisioningData) {
    // Calculate how user should be provisioned
    $user = $this->calcUser($coProvisioningTargetData, $provisioningData);

    // Find MidPoint identifier
    $oid = $this->findIdentifier($coProvisioningTargetData, $provisioningData);

    // Return false if unable to find MidPoint user
    if (empty($oid)) {
      // TODO unable to find user
      return false;
    }

    // Connect to MidPoint
    $api = new MidPointRestApiClient($coProvisioningTargetData['CoMidPointProvisionerTarget']['server_id']);

    // Get user from MidPoint
    $actualUser = $api->getUser($oid);

    // Canonicalize MidPoint user
    $flattenedUser = $this->canonicalizeUser($actualUser);

    // Diff
    $mods = $this->diffUser($user, $flattenedUser);

    // Return true if no modifications need to be made
    if (empty($mods)) {
      return true;
    }

    return $api->modifyUserFromArray($oid, $mods);
  }

  /**
   * Whether the midPoint user should be provisioned.
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return Boolean true if the midPoint user should be provisioned, false otherwise
   */
  public function isUserProvisionable($coProvisioningTargetData, $provisioningData) {
    if (in_array(
      $provisioningData['CoPerson']['status'],
      array(
        StatusEnum::Active,
        StatusEnum::GracePeriod
      ))) {
      $this->log("MidPointProvisioner user is provisionable", 'debug');
      return true;
    }

    $this->log("MidPointProvisioner user is not provisionable", 'debug');
    return false;
  }

  /**
   * Whether the midPoint user is already provisioned.
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return Boolean true if the midPoint user is already provisioned, false otherwise
   */
  public function isUserProvisioned($coProvisioningTargetData, $provisioningData) {
    // Find MidPoint identifier
    $oid = $this->findIdentifier($coProvisioningTargetData, $provisioningData);

    // Return true if OID was found
    if (!empty($oid)) {
      $this->log("MidPointProvisioner user is provisioned", 'debug');
      return true;
    }

    // TODO Get or search MidPoint for user ?

    $this->log("MidPointProvisioner user is not provisioned", 'debug');
    return false;
  }

  /**
   * Calculate how a midPoint user should be provisioned.
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return array Array representing how a midPoint user should be provisioned
   *
   * @throws RuntimeException When the name of the midPoint user cannot be determined.
   */
  public function calcUser($coProvisioningTargetData, $provisioningData) {
    $user = array();

    // midPoint user name
    $user['user']['name'] = $this->getUserName($coProvisioningTargetData, $provisioningData);

    // throw exception if unable to determine MidPoint name
    if (empty($user['user']['name'])) {
      $msg = "MidPointProvisioner unable to determine user name for CoPerson " . $provisioningData['CoPerson']['id'];
      $this->log($msg);
      throw new RuntimeException($msg);
    }

    // midPoint user Lifecycle State
    // TODO status $user['user']['status'] = $provisioningData['CoPerson']['status'];

    // midPoint user fullName
    $user['user']['fullName'] = generateCn($provisioningData['PrimaryName'], true);

    // midPoint user givenName
    $user['user']['givenName'] = $provisioningData['PrimaryName']['given'];

    // midPoint user familyName
    if (!empty($provisioningData['PrimaryName']['family'])) {
      $user['user']['familyName'] = $provisioningData['PrimaryName']['family'];
    }

    // midPoint user additionalName = middle name
    if (!empty($provisioningData['PrimaryName']['middle'])) {
      $user['user']['additionalName'] = $provisioningData['PrimaryName']['middle'];
    }

    // midPoint user nickName
    $user['user']['nickName'] = $provisioningData['PrimaryName']['given'];

    // midPoint user honorificPrefix
    if (!empty($provisioningData['PrimaryName']['honorific'])) {
      $user['user']['honorificPrefix'] = $provisioningData['PrimaryName']['honorific'];
    }

    // midPoint user honorificSuffix
    if (!empty($provisioningData['PrimaryName']['suffix'])) {
      $user['user']['honorificSuffix'] = $provisioningData['PrimaryName']['suffix'];
    }

    // TODO midPoint user title

    // midPoint user emailAddress
    if (!empty($provisioningData['EmailAddress'][0]['mail'])) {
      $user['user']['emailAddress'] = $provisioningData['EmailAddress'][0]['mail'];
    }

    // TODO midPoint user telephoneNumber

    // TODO midPoint user employeeNumber

    return $user;
  }

  /**
   * Get midPoint user name based on provisioner configuration.
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return string MidPoint user name or null
   */
  public function getUserName($coProvisioningTargetData, $provisioningData) {
    if (isset($provisioningData['CoPerson'])) {
      $coPersonId = $provisioningData['CoPerson']['id'];
    } else {
      return null;
    }

    // Select the identifier from the CoPerson based on the provisioner configuration.
    $idType = $coProvisioningTargetData['CoMidPointProvisionerTarget']['user_name_identifier'];

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
   * Returns a normalized representation of a user.
   *
   * Removes metadata and other data that is not provisioned.
   *
   * @param array $user Array representing a midPoint user
   *
   * @return Array Array representing a normalized midPoint user
   */
  public function canonicalizeUser($user) {
    $flat = $user;

    // ignore metadata
    unset($flat['user']['metadata']);

    // ignore operationExecution
    unset($flat['user']['operationExecution']);

    // TODO effectiveStatus
    // flatten effectiveStatus
    // $flat['user']['effectiveStatus'] = $flat['user']['activation']['effectiveStatus'];
    unset($flat['user']['activation']);

    // ignore iteration and iterationToken
    unset($flat['user']['iteration']);
    unset($flat['user']['iterationToken']);

    // ignore version
    unset($flat['user']['@version']);

    // TODO ignore @oid
    unset($flat['user']['@oid']);

    return $flat;
  }

  /**
   * Compute differences between midPoint users.
   *
   * @param array $midPointUserAfter Array representing how a midPoint user should be provisioned
   * @param array $midPointUserBefore Array representing how a midPoint user is provisioned
   *
   * @return array Array of modifications representing differences between midPoint users, the array is empty if there
   *   are no differences
   */
  public function diffUser($midPointUserAfter, $midPointUserBefore) {
    $mods = array();

    // TODO @oid
    // TODO effectiveStatus

    $toAdd = array_diff_key($midPointUserAfter['user'], $midPointUserBefore['user']);
    if (!empty($toAdd)) {
      $mods['add'] = $toAdd;
    }

    $toReplace = array_diff($midPointUserAfter['user'], $midPointUserBefore['user'], $toAdd);
    if (!empty($toReplace)) {
      $mods['replace'] = $toReplace;
    }

    $toDelete = array_diff_key($midPointUserBefore['user'], $midPointUserAfter['user'], $toReplace);
    if (!empty($toDelete)) {
      $mods['delete'] = $toDelete;
    }

    $this->log("MidPointProvisioner user modifications " . var_export($mods, true), 'debug');

    return $mods;
  }

  /**
   * Delete the midPoint identifier (OID).
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return boolean true if identifier was deleted successfully, false otherwise
   */
  public function deleteIdentifier($coProvisioningTargetData, $provisioningData) {
    $args = array();
    $args['conditions']['Identifier.co_person_id'] = $provisioningData['CoPerson']['id'];
    $args['conditions']['Identifier.type'] = IdentifierEnum::ProvisioningTarget;
    $args['conditions']['Identifier.co_provisioning_target_id'] = $coProvisioningTargetData['CoMidPointProvisionerTarget']['co_provisioning_target_id'];
    $args['contain'] = false;

    $identifier = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);
    if (!empty($identifier['Identifier']['identifier'])) {
      return $this->CoProvisioningTarget->Co->CoPerson->Identifier->delete($identifier['Identifier']['id']);
    }

    return false;
  }

  /**
   * Find the midPoint identifier (OID).
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return String MidPoint identifier (OID) or null
   */
  public function findIdentifier($coProvisioningTargetData, $provisioningData) {
    $args = array();
    $args['conditions']['Identifier.co_person_id'] = $provisioningData['CoPerson']['id'];
    $args['conditions']['Identifier.type'] = IdentifierEnum::ProvisioningTarget;
    $args['conditions']['Identifier.co_provisioning_target_id'] = $coProvisioningTargetData['CoMidPointProvisionerTarget']['co_provisioning_target_id'];
    $args['contain'] = false;

    $identifier = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('first', $args);
    if (!empty($identifier['Identifier']['identifier'])) {
      return $identifier['Identifier']['identifier'];
    }

    return null;
  }

  /**
   * Save the midPoint identifier.
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   * @param String $oid MidPoint user identifier (OID)
   *
   * @return Integer Identifier ID of saved record
   */
  public function saveIdentifier($coProvisioningTargetData, $provisioningData, $oid) {
    $args = array(
      'Identifier' => array(
        'identifier' => $oid,
        'co_person_id' => $provisioningData['CoPerson']['id'],
        'type' => IdentifierEnum::ProvisioningTarget,
        'login' => false,
        'status' => SuspendableStatusEnum::Active,
        'co_provisioning_target_id' => $coProvisioningTargetData['CoMidPointProvisionerTarget']['co_provisioning_target_id']
      )
    );

    $this->CoProvisioningTarget->Co->CoPerson->Identifier->clear();
    $this->CoProvisioningTarget->Co->CoPerson->Identifier->save($args, array('provision' => false));

    return $this->CoProvisioningTarget->Co->CoPerson->Identifier->id;
  }
}