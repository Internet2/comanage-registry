<?php
/**
 * COmanage Registry CO Grouper Provisioner Target Model
 *
 * Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("CoProvisionerPluginTarget", "Model", "ConnectionManager");
App::uses('GrouperRestClient', 'GrouperProvisioner.Lib');
App::uses('GrouperRestClientException', 'GrouperProvisioner.Lib');

class CoGrouperProvisionerTarget extends CoProvisionerPluginTarget {

  // Define class name for cake
  public $name = "CoGrouperProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");
  
  public $hasMany = array(
      "CoGrouperProvisionerGroup" => array(
      'className' => 'GrouperProvisioner.CoGrouperProvisionerGroup',
      'dependent' => true
    ),
  );
  
  // Default display field for cake generated views
  public $displayField = "serverurl";
  
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Provisioning Target ID must be provided'
    ),
    'serverurl' => array(
      'rule' => array('custom', '/^https?:\/\/.*/'),
      'required' => true,
      'allowEmpty' => false,
      'message' => 'Please enter a valid http or https URL'
    ),
    'contextpath' => array(
      'rule' => array('custom', '/^\/.*/'),
      'required' => true,
      'allowEmpty' => false,
      'message' => 'Please enter a valid context path'
    ),
    'login' => array(
      'rule' => 'notEmpty'
    ),
    'password' => array(
      'rule' => 'notEmpty'
    ),
    'stem' => array(
      'rule' => 'notEmpty'
    )
  );

  /**
   * Determine the provisioning status of this target.
   *
   * @since  COmanage Registry v0.8.3
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

    if (!empty($coPersonId)) {
      // For CO people we just return unknown.
      $ret['comment'] = 'see status for individual groups';
      return $ret;
    }

    $args = array();
    $args['conditions']['CoGrouperProvisionerTarget.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['conditions']['CoGrouperProvisionerGroup.co_group_id'] = $coGroupId;

    $group = $this->CoGrouperProvisionerGroup->find('first', $args);

    if (!empty($group)) {
      $ret['status'] = ProvisioningStatusEnum::Provisioned;
      $ret['timestamp'] = $group['CoGrouperProvisionerGroup']['modified'];
    }

   return $ret;
  }
  
  /**
   * Provision for the specified CO Person or CO Group.
   *
   * @since  COmanage Registry v0.8
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */

  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    $serverUrl = $coProvisioningTargetData['CoGrouperProvisionerTarget']['serverurl'];
    $contextPath = $coProvisioningTargetData['CoGrouperProvisionerTarget']['contextpath'];
    $login = $coProvisioningTargetData['CoGrouperProvisionerTarget']['login'];
    $password = $coProvisioningTargetData['CoGrouperProvisionerTarget']['password'];
    
    switch($op) {
      case ProvisioningActionEnum::CoGroupAdded:

        $provisionerGroup = $this->CoGrouperProvisionerGroup->addProvisionerGroup($coProvisioningTargetData, $provisioningData);
        $groupName = $this->CoGrouperProvisionerGroup->getGroupName($provisionerGroup);
        $groupDescription = $this->CoGrouperProvisionerGroup->getGroupDescription($provisionerGroup);
        $groupDisplayExtension = $this->CoGrouperProvisionerGroup->getGroupDisplayExtension($provisionerGroup);

        try {
          $grouper = new GrouperRestClient($serverUrl, $contextPath, $login, $password);
          $grouper->groupSave($groupName, $groupDescription, $groupDisplayExtension, 'group');
        } catch (GrouperRestClientException $e) {
          throw new RuntimeException($e->getMessage());
        }

        break;

      case ProvisioningActionEnum::CoGroupDeleted:

        $provisionerGroup = $this->CoGrouperProvisionerGroup->findProvisionerGroup($coProvisioningTargetData, $provisioningData);
        $groupName = $this->CoGrouperProvisionerGroup->getGroupName($provisionerGroup);

        try {
          $grouper = new GrouperRestClient($serverUrl, $contextPath, $login, $password);
          $grouper->groupDelete($groupName);
        } catch (GrouperRestClientException $e) {
          throw new RuntimeException($e->getMessage());
        }

        $this->CoGrouperProvisionerGroup->delProvisionerGroup($provisionerGroup);

        break;

      // We can treat reprovision and update the same by adding some
      // logic in a few places. Note that CoGroupUpdated is called
      // after CoGroupDeleted.
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
      case ProvisioningActionEnum::CoGroupUpdated:

        // Determine if any details about the group itself changed
        // and update as necessary.

        $currentProvisionerGroup = $this->CoGrouperProvisionerGroup->findProvisionerGroup($coProvisioningTargetData, $provisioningData);
        if (empty($currentProvisionerGroup)) {
          if ($op != ProvisioningActionEnum::CoGroupReprovisionRequested) {
            // If we cannot find a provisioner group in our table for this 
            // group and this is not a reprovision then we are being called 
            // after a delete and should do no further processing.
            break;
          } else {
            $currentProvisionerGroup = $this->CoGrouperProvisionerGroup->emptyProvisionerGroup(); 
          }
        } 

        $newProvisionerGroup = $this->CoGrouperProvisionerGroup->computeProvisionerGroup($coProvisioningTargetData, $provisioningData);

        $groupUpdateNeeded = false;
        if ($currentProvisionerGroup['CoGrouperProvisionerGroup']['stem'] != $newProvisionerGroup['CoGrouperProvisionerGroup']['stem']) {
          $groupUpdateNeeded = true;
        }
        if ($currentProvisionerGroup['CoGrouperProvisionerGroup']['extension'] != $newProvisionerGroup['CoGrouperProvisionerGroup']['extension']) {
          $groupUpdateNeeded = true;
        }
        if ($currentProvisionerGroup['CoGrouperProvisionerGroup']['description'] != $newProvisionerGroup['CoGrouperProvisionerGroup']['description']) {
          $groupUpdateNeeded = true;
        }

        // If either something about a group other than membership has changed
        // or if this is a reprovision update the table. We update the table
        // on a reprovision to change the modify timestamp.
        if ($groupUpdateNeeded or ($op == ProvisioningActionEnum::CoGroupReprovisionRequested)) {
          $this->CoGrouperProvisionerGroup->updateProvisionerGroup($currentProvisionerGroup, $newProvisionerGroup);     
        }

        // If something other than membership has changed about the group than
        // tell Grouper to do the update.
        if ($groupUpdateNeeded) {
          try {
            $grouper = new GrouperRestClient($serverUrl, $contextPath, $login, $password);

            $currentName = $this->CoGrouperProvisionerGroup->getGroupName($currentProvisionerGroup);
            $newName = $this->CoGrouperProvisionerGroup->getGroupName($newProvisionerGroup);
            $groupDescription = $this->CoGrouperProvisionerGroup->getGroupDescription($newProvisionerGroup);
            $groupDisplayExtension = $this->CoGrouperProvisionerGroup->getGroupDisplayExtension($newProvisionerGroup);

            // Test for existence here because it is mostly cheap and if
            // the group does not exist we can just create it. This makes
            // manual reprovision nicer.
            $exists = $grouper->groupExists($currentName);
            if ($exists) {
              $grouper->groupUpdate($currentName, $newName, $groupDescription, $groupDisplayExtension);
            } else {
              $grouper->groupSave($newName, $groupDescription, $groupDisplayExtension, 'group');
            }
          } catch (GrouperRestClientException $e) {
          throw new RuntimeException($e->getMessage());
          }
        }

        // Determine if any memberships changed and update as necessary.

        // Query Grouper for all current memberships in the group to
        // obtain an array of CO Person IDs since that is what is being
        // used as the subject in Grouper.
        try {
          $grouper = new GrouperRestClient($serverUrl, $contextPath, $login, $password);

          $groupName = $this->CoGrouperProvisionerGroup->getGroupName($newProvisionerGroup);
          $memberships = $grouper->getMembersManyGroups(array($groupName));
          $currentMembershipsCoPersonIds = $memberships[$groupName];
        } catch (GrouperRestClientException $e) {
          throw new RuntimeException($e->getMessage());
        }

        // If a group is empty the provisioning data passed in may not
        // contain CoGroupMember.
        if(array_key_exists('CoGroupMember', $provisioningData)) {
        	$membershipsPassedIn = $provisioningData['CoGroupMember'];
        } else {
        	$membershipsPassedIn = array();
        }
        
        // Create an array of CO Person IDs for the memberships passed
        // in as the provisioning data.
        $provisioningDataCoPersonIds = array();
        foreach ($membershipsPassedIn as $coGroupMember) {
          if ($coGroupMember['member']) {
            $provisioningDataCoPersonIds[] = $coGroupMember['co_person_id'];
          }
        }

        // Compare what is in Grouper to what was passed in as
        // provisioning data to determine which memberships must
        // be added to Grouper.
        $membershipsToAdd = array_diff($provisioningDataCoPersonIds, $currentMembershipsCoPersonIds);

        if (!empty($membershipsToAdd)) {
          try {
            $grouper = new GrouperRestClient($serverUrl, $contextPath, $login, $password);
            $groupName = $this->CoGrouperProvisionerGroup->getGroupName($newProvisionerGroup);
            $grouper->addManyMember($groupName, $membershipsToAdd);
          } catch (GrouperRestClientException $e) {
            throw new RuntimeException($e->getMessage());
          }

          // Update provisioner group table to record new modified time.
          $this->CoGrouperProvisionerGroup->updateProvisionerGroup($currentProvisionerGroup, $newProvisionerGroup);     
        }

        // Compare what is in Grouper to what was passed in as
        // provisioning data to determine which memberships must
        // be deleted from Grouper.
        $membershipsToRemove = array_diff($currentMembershipsCoPersonIds, $provisioningDataCoPersonIds);

        if (!empty($membershipsToRemove)) {
          try {
            $grouper = new GrouperRestClient($serverUrl, $contextPath, $login, $password);
            $groupName = $this->CoGrouperProvisionerGroup->getGroupName($newProvisionerGroup);
            $grouper->deleteManyMember($groupName, $membershipsToRemove);
          } catch (GrouperRestClientException $e) {
            throw new RuntimeException($e->getMessage());
          }

          // Update provisioner group table to record new modified time.
          $this->CoGrouperProvisionerGroup->updateProvisionerGroup($currentProvisionerGroup, $newProvisionerGroup);     
        }
        
        break;

      default:
        // This provisioner does not fire on any CoPerson related provisioning actions
        // since all changes to groups or group memberships are carried through
        // the group provisioning actions.
        break;
    }
    
    return true;
  }

  /**
   * Test a Grouper server to verify that the connection available is valid.
   *
   * @since  COmanage Registry v0.8.3
   * @param  String Server URL
   * @param  String Context path
   * @param  String Login
   * @param  String Password
   * @param  String Stem name
   * @return Boolean True if parameters are valid
   * @throws RuntimeException
   */
  
  public function verifyGrouperServer($serverUrl, $contextPath, $login, $password, $stemName) {

    // create if necessary the Grouper subject database view
    $this->createDatabaseView();

    // test server access and authentication
    try {
      $grouper = new GrouperRestClient($serverUrl, $contextPath, $login, $password);
      $exists = $grouper->stemExists($stemName);
    } catch (GrouperRestClientException $e) {
      throw new RuntimeException($e->getMessage());
    }

    // create stem if it does not exist
    if (!$exists) {
      try {
        $grouper->stemSave($stemName, "", ""); 
      } catch (GrouperRestClientException $e) {
        throw new RuntimeException($e->getMessage());
      }
    }
    
    return true;
  }
}
