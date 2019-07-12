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

  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Provisioning Target ID must be provided'
    ),
    'ssl_allow_self_signed' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'ssl_verify_host' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'ssl_verify_peer' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'ssl_verify_peer_name' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
  );

  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    if (Configure::read('debug')) {
      $this->log("CoMidPointProvisioningTarget provision op=$op ***************************", 'debug');
      $this->log('CoMidPointProvisionerTarget provision $coProvisioningTargetData ' . var_export($coProvisioningTargetData, true), 'debug');
      $this->log('CoMidPointProvisionerTarget provision $provisioningData ' . var_export($provisioningData, true), 'debug');
    }

    switch ($op) {
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
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
    // Is the person active and should be provisioned ?
    $active = $this->isCoPersonActive($provisioningData);

    // Is the user already provisioned ?
    $provisioned = $this->isUserProvisioned($coProvisioningTargetData, $provisioningData);

    // Create user if active and not provisioned
    if ($active and !$provisioned) {
      return $this->createUser($coProvisioningTargetData, $provisioningData);
    }

    // Update user if active and already provisioned
    if ($active and $provisioned) {
      return $this->updateUser($coProvisioningTargetData, $provisioningData);
    }

    // Delete user if not active and already provisioned
    if (!$active and $provisioned) {
      return $this->deleteUser($coProvisioningTargetData, $provisioningData);
    }

    // Nothing to do
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
    $api = new MidPointRestApiClient($coProvisioningTargetData);

    // Create MidPoint user
    $oid = $api->createUserFromArray($user);

    // Return false if unable to create MidPoint user
    if (empty($oid)) {
      return false;
    }

    // Save MidPoint identifier
    return $this->saveIdentifier($coProvisioningTargetData, $provisioningData, $oid);
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

    // TODO Handle OID not found

    // Connect to MidPoint
    $api = new MidPointRestApiClient($coProvisioningTargetData);

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
   * TODO what to return if no changes are necessary ?
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return boolean true if the midPoint user was modified successfully, false otherwise.
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
    $api = new MidPointRestApiClient($coProvisioningTargetData);

    // Get user from MidPoint
    $actualUser = $api->getUser($oid);

    // Canonicalize MidPoint user
    $flattenedUser = $this->canonicalizeUser($actualUser);

    // Diff
    $mods = $this->diffUser($user, $flattenedUser);

    // TODO return if no mods

    return $api->modifyUserFromArray($oid, $mods);
  }

  /**
   * Whether the CO Person is active.
   *
   * @param array $provisioningData CO Person provisioning data
   *
   * @return Boolean true if the CO Person is active, false otherwise
   */
  public function isCoPersonActive($provisioningData) {
    if (in_array(
      $provisioningData['CoPerson']['status'],
      array(
        StatusEnum::Active,
        StatusEnum::GracePeriod
      ))) {
      return true;
    }

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
      return true;
    }

    // TODO Get or search MidPoint for user ?

    return false;
  }

  /**
   * Calculate how a midPoint user should be provisioned.
   *
   * @param array $coProvisioningTargetData CO provisioning target data
   * @param array $provisioningData CO Person provisioning data
   *
   * @return array Array representing how a midPoint user should be provisioned
   */
  public function calcUser($coProvisioningTargetData, $provisioningData) {
    $user = array();

    $user['user']['name'] = $this->getUserName($coProvisioningTargetData, $provisioningData);

    $user['user']['fullName'] = generateCn($provisioningData['PrimaryName'], true);

    $user['user']['givenName'] = $provisioningData['PrimaryName']['given'];

    if (!empty($provisioningData['PrimaryName']['family'])) {
      $user['user']['familyName'] = $provisioningData['PrimaryName']['family'];
    }

    // TODO middleName

    // TODO status $user['user']['status'] = $provisioningData['CoPerson']['status'];

    // TODO email

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

    if (Configure::read('debug')) {
      $msg = "CoMidPointProvisionerTarget user modifications " . var_export($mods, true);
      $this->log($msg, 'debug');
    }

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
   * @param $oid String MidPoint user identifier (OID)
   *
   * @return boolean true if identifier was saved successfully, false otherwise
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
    return $this->CoProvisioningTarget->Co->CoPerson->Identifier->save($args, array('provision' => false));
  }
}