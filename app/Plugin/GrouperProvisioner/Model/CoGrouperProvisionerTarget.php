<?php
/**
 * COmanage Registry CO Grouper Provisioner Target Model
 *
 * Copyright (C) 2012-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("CoProvisionerPluginTarget", "Model", "ConnectionManager");
App::uses('GrouperRestClient', 'GrouperProvisioner.Lib');
App::uses('GrouperRestClientException', 'GrouperProvisioner.Lib');
App::uses('GrouperCouProvisioningStyle', 'GrouperProvisioner.Lib');

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
      'allowEmpty' => false,
      'on' => null,
      'message' => 'A CO Provisioning Target ID must be provided'
    ),
    'serverurl' => array(
      'rule' => array('custom', '/^https?:\/\/.*/'),
      'required' => true,
      'allowEmpty' => false,
      'on' => null,
      'message' => 'Please enter a valid http or https URL'
    ),
    'contextpath' => array(
      'rule' => array('custom', '/^\/.*/'),
      'required' => true,
      'allowEmpty' => false,
      'on' => null,
      'message' => 'Please enter a valid context path'
    ),
    'login' => array(
      'rule' => 'notBlank',
      'required' => true,
      'on' => null,
      'allowEmpty' => false,
    ),
    'password' => array(
      'rule' => 'notBlank',
      'required' => true,
      'on' => null,
      'allowEmpty' => false,
    ),
    'stem' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false,
      'on' => null,
    ),
    'login_identifier' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false,
      'on' => null,
    ),
    'email_identifier' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false,
      'on' => null,
    ),
    'subject_view' => array(
      'subjectViewRule1' => array(
      'rule' => array('maxLength', 30),
      'required' => true,
      'allowEmpty' => false,
      'on' => null,
    ),
    'subjectViewRule2' => array(
      'rule' => 'isUnique',
      'message' => 'The view name must be unique'
      )
    )
  );
  
  /**
   * Called after each successful save operation. Right now used
   * to create the view(s) for Grouper subjects.
   * 
   * @since COmanage Registry v0.9.3
   * @param bool $created True if this save created a new record
   * @param array $options Options passed from Model::save().
   * @return void
   */
  public function afterSave($created, $options = array()) {
    $prefix = "";
    $db =& ConnectionManager::getDataSource('default');
    $db_driver = split("/", $db->config['datasource'], 2);
      
    if(isset($db->config['prefix'])) {
      $prefix = $db->config['prefix'];
    }        
    
    $view = $this->data['CoGrouperProvisionerTarget']['subject_view'];
    
    $args = array();
    $args['conditions']['CoProvisioningTarget.id'] = $this->data['CoGrouperProvisionerTarget']['co_provisioning_target_id'];
    $args['contain'] = false;
    $target = $this->CoProvisioningTarget->find('first', $args);
    $coId = $target['CoProvisioningTarget']['co_id'];
    
    $sqlTemplate = "
CREATE OR REPLACE VIEW $view (id, name, lfname, description, description_lower, loginid, email) AS
SELECT
  CONCAT('COMANAGE_', '$coId', '_', CAST(cm_co_people.id AS @CAST_TYPE@)),
  CONCAT(COALESCE(cm_names.given,''), ' ', COALESCE(cm_names.family,'')),
  CONCAT(COALESCE(cm_names.family,''), ' ', COALESCE(cm_names.given,'')),
  CONCAT(COALESCE(cm_names.given,''), ' ', COALESCE(cm_names.family,''), ' (', cm_cos.name, ')'),
  LOWER(CONCAT(cm_names.given, ' ', cm_names.family, ' ', COALESCE(cm_email_addresses.mail, ''), ' ', COALESCE(cm_identifiers.identifier,''))),
  cm_identifiers.identifier,
  cm_email_addresses.mail
FROM
  cm_co_people
  LEFT JOIN cm_names ON cm_co_people.id = cm_names.co_person_id AND cm_names.primary_name IS TRUE AND cm_names.name_id IS NULL AND cm_names.deleted IS FALSE
  JOIN cm_cos ON cm_co_people.co_id = cm_cos.id AND cm_cos.id = $coId
  LEFT JOIN cm_identifiers ON cm_co_people.id = cm_identifiers.co_person_id AND cm_identifiers.type = '@IDENTIFIER_TYPE@' AND cm_identifiers.identifier_id IS NULL AND cm_identifiers.deleted IS FALSE AND cm_identifiers.status = 'A'
  LEFT JOIN cm_email_addresses ON cm_co_people.id = cm_email_addresses.co_person_id AND cm_email_addresses.type = '@EMAIL_TYPE@' AND cm_email_addresses.email_address_id IS NULL AND cm_email_addresses.deleted IS FALSE
  WHERE cm_co_people.status = 'A' AND cm_co_people.co_person_id IS NULL AND cm_co_people.deleted IS FALSE
  ";                  
        
    $replacements = array();
    $replacements['cm_'] = $prefix;
    $replacements['@IDENTIFIER_TYPE@'] = $this->data['CoGrouperProvisionerTarget']['login_identifier'];
    $replacements['@EMAIL_TYPE@'] = $this->data['CoGrouperProvisionerTarget']['email_identifier'];  
    
    switch ($db_driver[1]) {
      case 'Mysql':
        $replacements['@CAST_TYPE@'] = 'char';
        break;
      case 'Postgres':
        $replacements['@CAST_TYPE@'] = 'text';
        break;
    }
    
    $sql = strtr($sqlTemplate, $replacements);       
            
    $result = $this->query($sql);
    
  }

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

    if(!empty($coPersonId)) {
      // For CO people we just return unknown.
      $ret['comment'] = 'see status for individual groups';
      return $ret;
    }

    $args = array();
    $args['conditions']['CoGrouperProvisionerTarget.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['conditions']['CoGrouperProvisionerGroup.co_group_id'] = $coGroupId;

    $group = $this->CoGrouperProvisionerGroup->find('first', $args);

    if(!empty($group)) {
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
          
          // Loop over stems since we may need to create a COU hierarchy. Begin by
          // taking full group name and cutting off the group, and then the
          // first stem since that is the CO base stem which will already exist.
          
          $stemComponents = explode(':', $groupName, -1);
          $baseStem = array_shift($stemComponents);
          
          $stem = $baseStem;
          foreach($stemComponents as $component) {
            $stem = $stem . ':' . $component;
            $exists = $grouper->stemExists($stem);
            if(!$exists) {
              $grouper->stemSave($stem, '', '');
            }
          }
          
          // All stems exists so now save the group.
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
          
          // If this was a COU members or admin group we need to delete the stem in which
          // the group lived since that stem represented the COU and the only way
          // a COU members or admin group is deleted is if the COU is deleted.
          // We cannot, however, delete a stem until it has no child groups and
          // we don't know which of the admin or the members group will be deleted
          // first. Grouper has no easy way to query a stem and decide if it has
          // child groups, so for now just try to delete stem and walk over a 
          // failed delete call.
          if($this->CoGrouperProvisionerGroup->CoGroup->isCouAdminOrMembersGroup($provisioningData)) {
            $stem = $this->CoGrouperProvisionerGroup->getStem($provisionerGroup);
            try {
              $grouper->stemDelete($stem);
            } catch (GrouperRestClientException $e) {
            }
          }
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
        if(empty($currentProvisionerGroup)) {
          if($op != ProvisioningActionEnum::CoGroupReprovisionRequested) {
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
        if($currentProvisionerGroup['CoGrouperProvisionerGroup']['stem'] != $newProvisionerGroup['CoGrouperProvisionerGroup']['stem']) {
          $groupUpdateNeeded = true;
        }
        if($currentProvisionerGroup['CoGrouperProvisionerGroup']['extension'] != $newProvisionerGroup['CoGrouperProvisionerGroup']['extension']) {
          $groupUpdateNeeded = true;
        }
        if($currentProvisionerGroup['CoGrouperProvisionerGroup']['description'] != $newProvisionerGroup['CoGrouperProvisionerGroup']['description']) {
          $groupUpdateNeeded = true;
        }
        
        // If either something about a group other than membership has changed
        // or if this is a reprovision update the table. We update the table
        // on a reprovision to change the modify timestamp.
        if($groupUpdateNeeded or ($op == ProvisioningActionEnum::CoGroupReprovisionRequested)) {
          $this->CoGrouperProvisionerGroup->updateProvisionerGroup($currentProvisionerGroup, $newProvisionerGroup);     
          // If this is a COU members group and its name changed because the COU name changed
          // and if the COU has children we need to change the stems for all of the children also
          // in the mapping table.
          if($this->CoGrouperProvisionerGroup->CoGroup->isCouAdminOrMembersGroup($provisioningData)) {
            if($newProvisionerGroup['CoGrouperProvisionerGroup']['extension'] != $currentProvisionerGroup['CoGrouperProvisionerGroup']['extension']) {
              // Find the COU for this COU admin or members group.
              $coId = $provisioningData['CoGroup']['co_id'];
              $args = array();
              $args['conditions']['Cou.co_id'] = $coId;
              $args['conditions']['Cou.name'] = $this->CoGrouperProvisionerGroup->CoGroup->couNameFromAdminOrMembersGroup($provisioningData);
              $args['contain'] = false;
              $cou = $this->CoProvisioningTarget->Co->Cou->find('first', $args);
                  
              // Find the children, if any of the COU.
              $allChildCous = $this->CoProvisioningTarget->Co->Cou->children($cou['Cou']['id']);
              foreach($allChildCous as $child) {
                $prefixes = array('admin:', 'members:');
                foreach($prefixes as $prefix) {
                  // Find the admin or members group for the COU.
                  $group = $this->CoProvisioningTarget->Co->CoGroup->findByName($coId, $prefix . $child['Cou']['name']);
                  // Find the provisioner group.
                  $args = array();
                  $args['conditions']['CoGrouperProvisionerGroup.co_group_id'] = $group['CoGroup']['id'];
                  $args['contain'] = false;
                  $current = $this->CoGrouperProvisionerGroup->find('first', $args);
                  if(!empty($current)) {
                    $new = $this->CoGrouperProvisionerGroup->computeProvisionerGroup($coProvisioningTargetData, $group);
                    $this->CoGrouperProvisionerGroup->updateProvisionerGroup($current, $new);
                  }
                }
              }
            }
          }
        }

        // If something other than membership has changed about the group than
        // tell Grouper to do the update.
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
          if($exists && $groupUpdateNeeded) {
            // Before updating the group see if any stem names might have changed 
            // due to this being a COU admin or members group and COU name change and change those.
            $currentStems = explode(':', $currentName, -1);
            $newStems = explode(':', $newName, -1);
            $stems = array_combine($currentStems, $newStems);
            $base = array_shift($stems);
            if(!empty($stems)) {
              $stem = $base;
              foreach($stems as $cur => $new) {
                    $old = $stem . ':' . $cur;
                    $stem = $stem . ':' . $new;
                  if($new != $cur) {
                    $grouper->stemUpdate($old, $stem, '', '');  
                  }
              }
            }
            
            // Now change the group itself.
            $grouper->groupUpdate($currentName, $newName, $groupDescription, $groupDisplayExtension);
          } elseif(!$exists) {
            // Loop over stems since we may need to create a COU hierarchy. Begin by
            // taking full group name and cutting off the group, and then the
            // first stem since that is the CO base stem which will already exist.
          
            $stemComponents = explode(':', $newName, -1);
            $baseStem = array_shift($stemComponents);
          
            $stem = $baseStem;
            foreach($stemComponents as $component) {
              $stem = $stem . ':' . $component;
              $exists = $grouper->stemExists($stem);
              if(!$exists) {
                $grouper->stemSave($stem, '', '');
              }
            }
            // All stems exists so now save the group.
            try {
              $grouper->groupSave($newName, $groupDescription, $groupDisplayExtension, 'group');
            } catch (GrouperRestClientException $e) {
               // Since it is possible for the group to exist outside of the control
               // of COmanage and then for COmanage to be asked to provision into
               // the group it is not an error here for the group to exist.
               if ($e->getMessage() != 'Group already existed') {
                 throw new RuntimeException($e->getMessage());
               }
            }
          }
        } catch (GrouperRestClientException $e) {
        throw new RuntimeException($e->getMessage());
        }

        // Determine if any memberships changed and update as necessary.

        // Query Grouper for all current memberships in the group. The subject
        // IDs returned by Grouper are CO Person IDs prefixed with 'COMANAGE_coId_'.
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
          if($coGroupMember['member']) {
            $coId = $provisioningData['CoGroup']['co_id'];
            $provisioningDataCoPersonIds[] = 'COMANAGE_' . $coId . '_' . $coGroupMember['co_person_id'];
          }
        }

        // Compare what is in Grouper to what was passed in as
        // provisioning data to determine which memberships must
        // be added to Grouper.
        $membershipsToAdd = array_diff($provisioningDataCoPersonIds, $currentMembershipsCoPersonIds);

        if(!empty($membershipsToAdd)) {
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

        if(!empty($membershipsToRemove)) {
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

    // test server access and authentication
    try {
      $grouper = new GrouperRestClient($serverUrl, $contextPath, $login, $password);
      $exists = $grouper->stemExists($stemName);
    } catch (GrouperRestClientException $e) {
      throw new RuntimeException($e->getMessage());
    }

    // create stem if it does not exist
    if(!$exists) {
      try {
        $grouper->stemSave($stemName, "", ""); 
      } catch (GrouperRestClientException $e) {
        throw new RuntimeException($e->getMessage());
      }
    }
    
    return true;
  }
}
