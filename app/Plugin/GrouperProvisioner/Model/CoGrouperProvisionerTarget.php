<?php
/**
 * COmanage Registry CO Grouper Provisioner Target Model
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
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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
    'subject_identifier' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true,
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
      'required' => false,
      'allowEmpty' => true,
      'on' => null,
    ),
    'subjectViewRule2' => array(
      'rule' => 'isUnique',
      'message' => 'The view name must be unique'
      )
    )
  );

  /**
   * Perform CoGrouperProvisionerTarget model upgrade steps for version 2.0.0.
   * This function should only be called by UpgradeVersionShell.
   *
   * @since  COmanage Registry v2.0.0
   */

  public function _ug110() {
    // We set any existing provisioner targets that already do not have a subject
    // identifier set to using the legacy method for determining the Grouper 
    // subject for the user.
    
    // We use updateAll here which doesn't fire callbacks (including ChangelogBehavior).
    $fields = array(
      'CoGrouperProvisionerTarget.legacy_comanage_subject' => true
    );
    $conditions = array(
      'OR' => array(
        array('CoGrouperProvisionerTarget.subject_identifier' => ''),
        array('CoGrouperProvisionerTarget.subject_identifier' => null )
      )
    );
    $this->updateAll($fields, $conditions);
  }
  
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
    // Only create the view in legacy mode
    if (!$this->data['CoGrouperProvisionerTarget']['legacy_comanage_subject']) {
      return;
    }

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
   * Get the subject used by Grouper for the CoPerson.
   *
   * @since COmanage Registry v1.0.5
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $provisioningData Provisioning data
   * @return string
  */

  public function getGrouperSubject($coProvisioningTargetData, $provisioningData) {
    if(isset($provisioningData['CoGroup'])){
      $coId = $provisioningData['CoGroup']['co_id'];
      $coPersonId = $provisioningData['CoGroup']['CoPerson']['id'];
    } elseif (isset($provisioningData['CoPerson'])) {
      $coId = $provisioningData['CoPerson']['co_id'];
      $coPersonId = $provisioningData['CoPerson']['id'];
    }

    // For legacy use of SQL view as the Grouper subject source the subject
    // is a combination of the CO ID and the CoPerson ID prefixed with 'COMANAGE'.
    if ($coProvisioningTargetData['CoGrouperProvisionerTarget']['legacy_comanage_subject']) {
      if(isset($coId) && isset($coPersonId)) {
        return 'COMANAGE_' . $coId . '_' . $coPersonId;
      } else {
        return null;
      }
    }

    // Select the identifier from the CoPerson based on the provisioner configuration.
    $idType = $coProvisioningTargetData['CoGrouperProvisionerTarget']['subject_identifier'];

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

    // We might have been passed the identifier marked as deleted if this is 
    // a CoPersonDeleted operation.
    if(isset($provisioningData['Identifier'])) {
      foreach($provisioningData['Identifier'] as $id) {
        if($id['type'] == $idType && $id['status'] == SuspendableStatusEnum::Active && $id['deleted']) {
          return $id['identifier'];
        }
      }
    }

    // If we fall through we were not able to find an identifier so return null
    // and expect the caller to handle appropriately.
    return null;
  }

  /**
   * Create and return an instance of the GrouperRestClient.
   *
   * @since COmanage Registry 2.0.0
   * @param array $coProvisioningTargetData CO provisioning target data
   * @return instance GrouperRestClient or null if unable to create the instance
   */

  public function grouperRestClientFactory($coProvisioningTargetData) {
    $serverUrl = $coProvisioningTargetData['CoGrouperProvisionerTarget']['serverurl'];
    $contextPath = $coProvisioningTargetData['CoGrouperProvisionerTarget']['contextpath'];
    $login = $coProvisioningTargetData['CoGrouperProvisionerTarget']['login'];
    $password = $coProvisioningTargetData['CoGrouperProvisionerTarget']['password'];

    try {
      $grouper = new GrouperRestClient($serverUrl, $contextPath, $login, $password);
    } catch (GrouperRestClientException $e) {
      $this->log("GrouperProvisioner unable to create new GrouperRestClient: " . $e->getMessage());
      return null;
    }

    return $grouper;
  }


  /**
   * Process a COU name change.
   *
   * @since COmanage Registry 2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup provisioning data
   * @return void
   */
  
  public function processCouNameChange($coProvisioningTargetData, $coGroup) {
    // When a COU name changes we need to
    // (1) Change the name of the stem in Grouper.
    // (2) Update the mappings of any child COU groups because the
    //     stem in Grouper just changed.
    //
    // We do not change the name of the COU groups themselves here
    // since that is done elsewhere as with any other group name change.
    // 
    // Since a CoGroupUpdated provision event will be called for each of
    // the COU auto managed groups we need to check if the stem needs to
    // be changed and only do so if necessary.
    $current = $this->CoGrouperProvisionerGroup->findProvisionerGroup($coProvisioningTargetData, $coGroup);
    $currentStem = $this->CoGrouperProvisionerGroup->getStem($current);

    if(!$grouper = $this->grouperRestClientFactory($coProvisioningTargetData)) {
      return;
    }

    // If the current stem does not exist it was renamed during a previous
    // CoGroupUpdated provisioning event.
    try {
      $exists = $grouper->stemExists($currentStem);
      if(!$exists) {
        return;
      }
    } catch (GrouperRestClientException $e) {
      $this->log("GrouperProvisioner unable to determine if stem $currentStem exists: $e");
      return;
    }
      
    $new = $this->CoGrouperProvisionerGroup->computeProvisionerGroup($coProvisioningTargetData, $coGroup);
    $newStem = $this->CoGrouperProvisionerGroup->getStem($new);

    try {
      $grouper->stemUpdate($currentStem, $newStem, '', '');  
    } catch (GrouperRestClientException $e) {
      $this->log("GrouperProvisioner unable to change name of stem $currentStem to $newStem: $e");
      return;
    }

    $this->updateChildCouGroupMappings($coProvisioningTargetData, $coGroup);
  }
  
  /**
   * Provision for the specified CO Person or CO Group.
   *
   * @since  COmanage Registry v0.8
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  string $op ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  array $provisioningData CoPerson or CoGroup provisioning data
   * @return boolean true on success
   * @throws RuntimeException
   */

  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    switch($op) {
      case ProvisioningActionEnum::CoPersonDeleted:
      case ProvisioningActionEnum::CoPersonExpired:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        return $this->provisionCoPerson($coProvisioningTargetData, $provisioningData);

      case ProvisioningActionEnum::CoGroupAdded:
        return $this->provisionCoGroupAdded($coProvisioningTargetData, $provisioningData);

      case ProvisioningActionEnum::CoGroupDeleted:
        return $this->provisionCoGroupDeleted($coProvisioningTargetData, $provisioningData);

      case ProvisioningActionEnum::CoGroupReprovisionRequested:
        return $this->provisionCoGroupReprovisionRequested($coProvisioningTargetData, $provisioningData);

      case ProvisioningActionEnum::CoGroupUpdated:
        return $this->provisionCoGroupUpdated($coProvisioningTargetData, $provisioningData);

      default:
        // Log noop and fall through.
        $this->log("GrouperProvisioner provisioning action $op is not implemented");
    }
    
    return true;
  }

  /**
   * Provision a CoGroupAdded event
   *
   * @since  COmanage Registry v2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $provisioningData CoGroup provisioning data
   * @return boolean true on success
   */

  public function provisionCoGroupAdded($coProvisioningTargetData, $coGroup) {
    // Test if an existing CoGrouperProvisionerGroup exists and if so do not call
    // addProvisionerGrouper to create a new one. This allows the reprovision logic
    // to recursively call this method since it has the necessary logic to create
    // any missing stems or group. This works becausing saving an existing
    // group is not an error.
    $provisionerGroup = $this->CoGrouperProvisionerGroup->findProvisionerGroup($coProvisioningTargetData, $coGroup);
    if(empty($provisionerGroup)) {
      $provisionerGroup = $this->CoGrouperProvisionerGroup->addProvisionerGroup($coProvisioningTargetData, $coGroup);
    }
    $groupName = $this->CoGrouperProvisionerGroup->getGrouperGroupName($provisionerGroup);
    $groupDescription = $this->CoGrouperProvisionerGroup->getGrouperGroupDescription($provisionerGroup);
    $groupDisplayExtension = $this->CoGrouperProvisionerGroup->getGroupDisplayExtension($provisionerGroup);

    if(!$this->provisionGroupAndStem($coProvisioningTargetData,
                                     $groupName,
                                     $groupDescription,
                                     $groupDisplayExtension)) {
      $this->log("GrouperProvisioner unable to add group $groupName");
      return false;
    }
    
    return true;
  }

  /**
   * Provision a CoGroupDeleted event
   *
   * @since  COmanage Registry v2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup provisioning data
   * @return boolean true on success
   */

  public function provisionCoGroupDeleted($coProvisioningTargetData, $coGroup) {
    $provisionerGroup = $this->CoGrouperProvisionerGroup->findProvisionerGroup($coProvisioningTargetData, $coGroup);
    $groupName = $this->CoGrouperProvisionerGroup->getGrouperGroupName($provisionerGroup);

    if(!$grouper = $this->grouperRestClientFactory($coProvisioningTargetData)) {
      return false;
    }

    try {
      $grouper->groupDelete($groupName);
      
      // If this was a COU members or admin group we need to delete the stem in which
      // the group lived since that stem represented the COU and the only way
      // a COU members or admin group is deleted is if the COU is deleted.
      // We cannot, however, delete a stem until it has no child groups and
      // we don't know which of the admin or the members group will be deleted
      // first. Grouper has no easy way to query a stem and decide if it has
      // child groups, so for now just try to delete stem and walk over a 
      // failed delete call.
      if($this->CoGrouperProvisionerGroup->CoGroup->isCouAdminOrMembersGroup($coGroup)) {
        $stem = $this->CoGrouperProvisionerGroup->getStem($provisionerGroup);
        try {
          $grouper->stemDelete($stem);
        } catch (GrouperRestClientException $e) {
        }
      }
    } catch (GrouperRestClientException $e) {
      $this->log("GrouperProvisioner unable to delete group $groupName");
      return false;
    }

    $this->CoGrouperProvisionerGroup->delProvisionerGroup($provisionerGroup);
    return true;
  }

  /**
   * Provision a CoGroupReprovisionRequested event
   *
   * @since  COmanage Registry v2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup provisioning data
   * @return boolean true on success
   */

  public function provisionCoGroupReprovisionRequested($coProvisioningTargetData, $coGroup) {
    $provisionerGroup = $this->CoGrouperProvisionerGroup->findProvisionerGroup($coProvisioningTargetData, $coGroup);
    $groupName = $this->CoGrouperProvisionerGroup->getGrouperGroupName($provisionerGroup);
    $groupDescription = $this->CoGrouperProvisionerGroup->getGrouperGroupDescription($provisionerGroup);
    $groupDisplayExtension = $this->CoGrouperProvisionerGroup->getGroupDisplayExtension($provisionerGroup);

    // We reprovision in three steps: 
    // (1) Provision the group and any necessary stems. 
    // (2) Use a transaction and a SELECT FOR UPDATE statement 
    //     with offset and limit to loop over all identifiers
    //     for all members of the group and then ask Grouper
    //     to add those members to the group.
    // (3) Query Grouper for identifiers of all members in
    //     its group instance and query to find any that are not supposed
    //     to be in the group and then ask Grouper to delete those
    //     from its instance of the group.

    if(!$this->provisionGroupAndStem($coProvisioningTargetData,
                                     $groupName,
                                     $groupDescription,
                                     $groupDisplayExtension
                                     )) {
      $this->log("GrouperProvisioner reprovision of group $groupName failed");
      return false;
    }

    // Next reprovision memberships using paging.
    if(!$this->reprovisionMemberships($coProvisioningTargetData, $coGroup)) {
      $this->log("GrouperProvisioner reprovision of memberships failed");
      return false;
    }

    // Next query Grouper using pagination to find the identifiers of all the
    // subjects it thinks are members of the group and test against the
    // database to find any that should be deleted.
    if(!$this->synchronizeMemberships($coProvisioningTargetData, $coGroup)) {
      $this->log("GrouperProvisioner synchronizing of memberships failed");
      return false;
    }

    // Update provisioner group table to record new modified time.
    $this->CoGrouperProvisionerGroup->updateProvisionerGroup($provisionerGroup);     

    return true;
  }

  /**
   * Provision a CoGroupUpdated event
   *
   * @since  COmanage Registry v2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup provisioning data
   * @return boolean true on success
   */

  public function provisionCoGroupUpdated($coProvisioningTargetData, $coGroup) {

    // Find the current mapping.
    $currentProvisionerGroup = $this->CoGrouperProvisionerGroup
                                    ->findProvisionerGroup($coProvisioningTargetData, $coGroup);
    if(empty($currentProvisionerGroup)) {
      // If we cannot find a provisioner group in our table for this 
      // group then we are being called after a delete and should 
      // do no further processing.
      return true;
    } 

    // Compute a new mapping.
    $newProvisionerGroup = $this->CoGrouperProvisionerGroup
                                ->computeProvisionerGroup($coProvisioningTargetData, $coGroup);

    // Is an update of group metadata needed because name or the like changed,
    // as opposed to a membership change?
    $groupUpdateNeeded = !$this->CoGrouperProvisionerGroup
                              ->equal($currentProvisionerGroup, $newProvisionerGroup);

    if($groupUpdateNeeded) {
      if(!$this->updateGroup($coProvisioningTargetData, $coGroup)) {
        return false;
      }
    }

    // Process a group membership update.
    if(!$this->updateGroupMembership($coProvisioningTargetData, $coGroup)) {
      return false;
    }

    return true;
  }

  /**
   * Take action on CoPerson provisioning events
   *
   * @since  COmanage Registry v2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coPerson CoPerson provisioning data
   * @return boolean true on success
   */

  public function provisionCoPerson($coProvisioningTargetData, $coPerson) {
    // All the CoPerson provisioning actions are handled by synchronzing
    // the memberships passed in with the memberships Grouper has for the
    // person.

    // Determine the subject for the user in Grouper.
    $subject = $this->getGrouperSubject($coProvisioningTargetData, $coPerson);
    if (empty($subject)) {
      $this->log("GrouperProvisioner unable to determine subject for CoPerson " . $coPerson['CoPerson']['id']);
      return false;
    }

    // If the provisioning data is for a CoPerson object then we
    // have the complete set of memberships and so we synchronize
    // it with Grouper.
    if(!isset($coPerson['CoPerson'])) {
      $this->log("GrouperProvisioner provisionCoPerson not passed CoPerson object");
      return false;
    }

    if(!$grouper = $this->grouperRestClientFactory($coProvisioningTargetData)) {
      return false;
    }

    // Query Grouper for the the current group memberships.
    try {
      $allGrouperGroups = $grouper->getGroups($subject);
    } catch (GrouperRestClientException $e) {
      $this->log("GrouperProvisioner unable to query group memberships for subject $subject");
      return false;
    }

    // Filter the returned groups by the stem that this plugin is configured to control.
    $grouperGroups = array();
    $stem = $coProvisioningTargetData['CoGrouperProvisionerTarget']['stem'];
    $pattern = "/^" . preg_quote($stem, '/') . "/";
    foreach($allGrouperGroups as $g) {
      if (preg_match($pattern, $g)) {
        $grouperGroups[] = $g;
      }
    }

    // Loop over the group memberships passed in as provisioning data and
    // add the user to any groups as necessary in Grouper. At the same time
    // construct array of the Grouper group names the group memberships 
    // passed in as provisioning data have. 
    $registryGroups = array();

    if(isset($coPerson['CoGroupMember'])) {
      foreach($coPerson['CoGroupMember'] as $m) {
        if($m['member'] && !($m['deleted']) && !($m['co_group_member_id'])) {
          $provisionerGroup = $this->CoGrouperProvisionerGroup
                                   ->findProvisionerGroup($coProvisioningTargetData, $m);
          $groupName = $this->CoGrouperProvisionerGroup
                            ->getGrouperGroupName($provisionerGroup);
          $groupDescription = $this->CoGrouperProvisionerGroup
                                   ->getGrouperGroupDescription($provisionerGroup);
          $groupDisplayExtension = $this->CoGrouperProvisionerGroup
                                        ->getGroupDisplayExtension($provisionerGroup);
          $registryGroups[] = $groupName;
          if(!(in_array($groupName, $grouperGroups))){
            try {
              if(!$grouper->groupExists($groupName)) {
                if(!$this->provisionGroupAndStem($coProvisioningTargetData,
                                                 $groupName,
                                                 $groupDescription,
                                                 $groupDisplayExtension)) {
                  // Log the failure but go onto the next group.
                  $this->log("GrouperProvisioner unable to add subject $subject to group $groupName");
                }
              }
              
              $grouper->addManyMember($groupName, array($subject));
            } catch (GrouperRestClientException $e) {
              // Log the failure but go onto the next group.
              $this->log("GrouperProvisioner unable to add subject $subject to group $groupName");
            }
          }
        }
      }
    }

    // Loop over the Grouper group memberships and delete the user
    // from any groups not passed in as provisioning data.
    foreach($grouperGroups as $g) {
      if(!(in_array($g, $registryGroups))) {
        try {
          $grouper->deleteMember($g, $subject);
        } catch (GrouperRestClientException $e) {
          // Log the failure but go onto the next group.
          $this->log("GrouperProvisioner unable to remove subject $subject from group $g");
        }
      }
    }

    return true;
  }

  /**
   * Provision a Grouper group and any stems if necessary
   *
   * @since  COmanage Registry v2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $groupName Grouper group full name including stems
   * @param  array $groupDescription Grouper group description
   * @param  array $groupDisplayExtension Grouper group display extension
   * @return boolean true on success
   */

  public function provisionGroupAndStem($coProvisioningTargetData, 
                                        $groupName, 
                                        $groupDescription, 
                                        $groupDisplayExtension) {
    // Loop over stems since we may need to create a COU hierarchy. Begin by
    // taking full group name and cutting off the group, and then the
    // first stem since that is the CO base stem which will already exist.

    if(!$grouper = $this->grouperRestClientFactory($coProvisioningTargetData)) {
      return false;
    }

    try {
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
      if(!$grouper->groupExists($groupName)) {
        $grouper->groupSave($groupName, $groupDescription, $groupDisplayExtension, 'group');
      }
    } catch (GrouperRestClientException $e) {
      $this->log("GrouperProvisioner unable to provision group $groupName");
      return false;
    }
    
    return true;
  }

  /**
   * Reprovision memberships using paging to prevent memory issues
   *
   * @since  COmanage Registry v2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup provisioning data
   * @return boolean true on success
   */

  public function reprovisionMemberships($coProvisioningTargetData, $coGroup) {
    // Use a transaction and a SELECT FOR UPDATE statement 
    // with offset and limit to loop over all identifiers
    // for all members of the group and then ask Grouper
    // to add those members to the group.

    if(!$grouper = $this->grouperRestClientFactory($coProvisioningTargetData)) {
      return false;
    }

    $provisionerGroup = $this->CoGrouperProvisionerGroup->findProvisionerGroup($coProvisioningTargetData, $coGroup);
    $groupName = $this->CoGrouperProvisionerGroup->getGrouperGroupName($provisionerGroup);
    
    // Get a handle to the database interface.
    $dbc = $this->getDataSource();

    // Begin a transaction.
    $dbc->begin();

    $args = array();

    // We use Identifier as the primary table.
    $args['table'] = $dbc->fullTableName($this->CoProvisioningTarget->Co->CoPerson->Identifier);
    $args['alias'] = $this->CoProvisioningTarget->Co->CoPerson->Identifier->alias;

    // We join across CoGroupMember and CoPeople.
    $args['joins'][0]['table']         = 'co_group_members';
    $args['joins'][0]['alias']         = 'CoGroupMember';
    $args['joins'][0]['type']          = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Identifier.co_person_id=CoGroupMember.co_person_id';

    $args['joins'][1]['table']         = 'co_people';
    $args['joins'][1]['alias']         = 'CoPerson';
    $args['joins'][1]['type']          = 'INNER';
    $args['joins'][1]['conditions'][0] = 'Identifier.co_person_id=CoPerson.id';

    // We only want identifiers used as the Grouper subject source.
    $args['conditions']['Identifier.type'] = $coProvisioningTargetData['CoGrouperProvisionerTarget']['subject_identifier'];

    // We only want active identifiers.
    $args['conditions']['Identifier.status'] = StatusEnum::Active;

    // We only want memberships in the group being reprovisioned.
    $args['conditions']['CoGroupMember.co_group_id'] = $coGroup['CoGroup']['id'];

    // We only want people from the corresponding CO.
    $args['conditions']['CoPerson.co_id'] = $coGroup['CoGroup']['co_id'];

    // We only want CoPeople in the active or approved status.
    $args['conditions']['CoPerson.status'][0] = StatusEnum::Active;
    $args['conditions']['CoPerson.status'][1] = StatusEnum::Approved;

    // Contain the query since we only want the identifiers.
    $args['contain'] = false;

    // We only need to return the identifier itself.
    $args['fields'] = $dbc->fields($this->CoProvisioningTarget->Co->CoPerson->Identifier, null, array('Identifier.identifier'));

    // Order by the identifier.
    $args['order'] = 'Identifier.identifier';

    // Start at the beginning and only consider 100 at a time in order to
    // help with memory scaling.
    $args['limit']  = 100;
    $offset = 0;

    $done = false;

    while (!$done) {
      $args['offset'] = $offset;

      // Appending to the generated query should be fairly portable.
      // We use buildQuery to ensure callbacks (such as ChangelogBehavior) are
      // invoked, then buildStatement to turn it into SQL.

      $sql = $dbc->buildStatement(
                $this->CoProvisioningTarget->Co->CoPerson->Identifier->buildQuery('all', $args), 
                $this->CoProvisioningTarget->Co->CoPerson->Identifier);

      $sqlForUpdate = $sql . " FOR UPDATE";

      $identifiers = $dbc->fetchAll($sqlForUpdate, array(), array('cache' => false));

      if ($identifiers) {
        $subjects = array();
        foreach ($identifiers as $i) {
          $subjects[] = $i['Identifier']['identifier'];
        }
        try {
            $grouper->addManyMember($groupName, $subjects);
        } catch (GrouperRestClientException $e) {
          // Log the exception but continue with the next set.
          $this->log("GrouperProvisioner unable to add subjects " . print_r($subjects, true) . " to group $groupName");
        }

      } else {
        $done = true;
      }

      $offset = $offset + 100;
    }

    // End the transaction to release the read lock held by SELECT FOR UPDATE.
    $dbc->commit();

    return true;
  }

  /**
   * Determine the provisioning status of this target.
   *
   * @since  COmanage Registry v0.8.3
   * @param  Integer $coProvisioningTargetId CO Provisioning Target ID
   * @param  Model   $Model                  Model being queried for status (eg: CoPerson, CoGroup, CoEmailList)
   * @param  Integer $id                     $Model ID to check status for
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   * @throws InvalidArgumentException If $id not found
   * @throws RuntimeException For other errors
   */
  
  public function status($coProvisioningTargetId, $Model, $id) {
    $ret = array(
      'status'    => ProvisioningStatusEnum::Unknown,
      'timestamp' => null,
      'comment'   => ""
    );

    if($Model->name == 'CoPerson') {
      // For CO people we just return unknown.
      $ret['comment'] = 'see status for individual groups';
      return $ret;
    }
    
    if($Model->name != 'CoGroup') {
      throw new InvalidArgumentException(_txt('er.notimpl'));
    }
    
    $args = array();
    $args['conditions']['CoGrouperProvisionerTarget.co_provisioning_target_id'] = $coProvisioningTargetId;
    $args['conditions']['CoGrouperProvisionerGroup.co_group_id'] = $id;

    $group = $this->CoGrouperProvisionerGroup->find('first', $args);

    if(!empty($group)) {
      $ret['status'] = ProvisioningStatusEnum::Provisioned;
      $ret['timestamp'] = $group['CoGrouperProvisionerGroup']['modified'];
    }

    return $ret;
  }

  /**
   * Synchronize memberships using paging to prevent memory issues
   *
   * @since  COmanage Registry v2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup provisioning data
   * @return boolean true on success
   */

  public function synchronizeMemberships($coProvisioningTargetData, $coGroup) {
    // Query Grouper for identifiers of all members in
    // its group instance and query to find any that are not supposed
    // to be in the group and then ask Grouper to delete those
    // from its instance of the group.

    if(!$grouper = $this->grouperRestClientFactory($coProvisioningTargetData)) {
      return false;
    }

    $provisionerGroup = $this->CoGrouperProvisionerGroup->findProvisionerGroup($coProvisioningTargetData, $coGroup);
    $groupName = $this->CoGrouperProvisionerGroup->getGrouperGroupName($provisionerGroup);

    $done = false;

    // Request no more than 100 members of a Grouper group.
    $pageSize = 100;

    // Start with the first page of members.
    $pageNumber = 1;

    // Order by the subject Id which will be the configured identifier.
    $sortString = 'subjectId';

    // Order in ascending order.
    $ascending = 'T';

    // Get a handle to the database interface.
    $dbc = $this->getDataSource();

    while (!$done) {
      $memberIdentifiers = array();

      // Query Grouper for a page of members.
      try {
        $ret = $grouper->getMembersManyGroups(array($groupName), $pageSize, $pageNumber, $sortString, $ascending);
      } catch (GrouperRestClientException $e) {
        // Log the exception but continue gracefully.
        $this->log("GrouperProvisioner unable to create new GrouperRestClient");
        $done = true;
        continue;
      }

      if (!empty($ret)) {
        if (array_key_exists($groupName, $ret)) {
          $memberIdentifiers = $ret[$groupName];
        }
      }

      if (!$memberIdentifiers) {
        // No more members returned so we are done paging.
        $done = true;
        continue;
      } else {
        // Increment page number for the next query.
        $pageNumber = $pageNumber + 1;
      }

      $coGroupId = $coGroup['CoGroup']['id'];
      $identifierType = $coProvisioningTargetData['CoGrouperProvisionerTarget']['subject_identifier'];

      $args = array();

      // LEFT join the CoGroupMember table so we can find identifiers of people not in the group.
      $args['joins'][0]['table'] = 'co_group_members';
      $args['joins'][0]['alias'] = 'CoGroupMember';
      $args['joins'][0]['type'] = 'LEFT';
      $args['joins'][0]['conditions'][0] = 'Identifier.co_person_id=CoGroupMember.co_person_id';
      $args['joins'][0]['conditions'][1] = "CoGroupMember.co_group_id=$coGroupId";

      // INNER join the CoPerson table so we only select identifiers for people in our CO.
      $args['joins'][1]['table'] = 'co_people';
      $args['joins'][1]['alias'] = 'CoPerson';
      $args['joins'][1]['type'] = 'INNER';
      $args['joins'][1]['conditions'][0] = 'Identifier.co_person_id=CoPerson.id';

      // Only consider the identifier we are using as the Grouper subject.
      $args['conditions']['Identifier.type'] = $coProvisioningTargetData['CoGrouperProvisionerTarget']['subject_identifier'];

      // Select identifiers of people not in the group by having the CoGroupMember id be null.
      $args['conditions']['CoGroupMember.id'] = null;

      // Only consider CoPeople in our CO.
      $args['conditions']['CoPerson.co_id'] = $coGroup['CoGroup']['co_id'];

      // Only consider active or approved people.
      $args['conditions']['OR'][0]['CoPerson.status'] = StatusEnum::Active;
      $args['conditions']['OR'][1]['CoPerson.status'] = StatusEnum::Approved;

      // Consider only the group of identifiers Grouper says are in the group.
      foreach ($memberIdentifiers as $identifier) {
        $args['conditions']['Identifier.identifier'][] = $identifier;
      }

      // We only need to return the identifier itself.
      $args['fields'] = $dbc->fields($this->CoProvisioningTarget->Co->CoPerson->Identifier, null, array('Identifier.identifier'));

      $args['contain'] = false;

      $identifiersToDelete = $this->CoProvisioningTarget->Co->CoPerson->Identifier->find('all', $args);

      if ($identifiersToDelete) {
        $subjects = array();
        foreach ($identifiersToDelete as $s) {
          $subjects[] = $s['Identifier']['identifier'];
        }
        try {
            $grouper->deleteManyMember($groupName, $subjects);
        } catch (GrouperRestClientException $e) {
          // Log the exception but continue with the next set.
          $this->log("GrouperProvisioner unable to create GrouperRestClient");
        }
      }
    }

    return true;
  }

  /**
   * Update CoGrouperProvisionerGroup mappings for child COU managed groups.
   *
   * @since COmanage Registry 2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup provisioning data
   * @return void
   */

  public function updateChildCouGroupMappings($coProvisioningTargetData, $coGroup) {
    // Only update the mappings from CoGroup to Grouper group, do not 
    // update anything in Grouper.
    if(!$this->CoGrouperProvisionerGroup->CoGroup->isCouAdminOrMembersGroup($coGroup)) {
      return;
    }

    // Group types we manage.
    $managedGroups = array(GroupEnum::ActiveMembers, GroupEnum::Admins, GroupEnum::AllMembers);

    // Find the COU for this COU admin or members group.
    $couId = $coGroup['CoGroup']['cou_id'];
    $args = array();
    $args['conditions']['Cou.id'] = $couId;
    $args['contain'] = false;
    $cou = $this->CoProvisioningTarget->Co->Cou->find('first', $args);
        
    // Find the children, if any of the COU.
    $allChildCous = $this->CoProvisioningTarget->Co->Cou->children($cou['Cou']['id']);
    foreach($allChildCous as $child) {
      // For each COU find the managed groups.
      $args = array();
      $args['conditions']['CoGroup.cou_id'] = $child['Cou']['id'];
      $args['conditions']['CoGroup.group_type'] = $managedGroups;
      $args['conditions']['CoGroup.deleted'] = false;
      $args['contain'] = false;

      $groups = $this->CoGrouperProvisionerGroup->CoGroup->find('all', $args);
      foreach($groups as $g) {
        // Find the current CoGrouperProvisionerGroup mapping.
        $current = $this->CoGrouperProvisionerGroup->findProvisionerGroupByCoGroupId($g['CoGroup']['id']);

        // Compute the new mapping based on the new COU name.
        $new = $this->CoGrouperProvisionerGroup->computeProvisionerGroup($coProvisioningTargetData,$g);
        $new['CoGrouperProvisionerGroup']['id'] = $current['CoGrouperProvisionerGroup']['id'];
        $this->CoGrouperProvisionerGroup->updateProvisionerGroup($new);
      }
    }
  }

  /**
   * Update a group because CoGroup metadata like name has changed.
   *
   * @since COmanage Registry v2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup provisioning data
   * @return boolean true on success
   *
   */

  public function updateGroup($coProvisioningTargetData, $coGroup) {
    // If this is a COU auto managed group process any necessary
    // changes due to a COU name change.
    if($this->CoGrouperProvisionerGroup
            ->isCouNameChange($coProvisioningTargetData, $coGroup)) {
      $this->processCouNameChange($coProvisioningTargetData, $coGroup);
    }

    // Find the current mapping and compute the new mapping.
    $current = $this->CoGrouperProvisionerGroup->findProvisionerGroup($coProvisioningTargetData, $coGroup);
    $new = $this->CoGrouperProvisionerGroup->computeProvisionerGroup($coProvisioningTargetData, $coGroup);

    // Update the group in Grouper.
    $currentName = $this->CoGrouperProvisionerGroup->getGrouperGroupName($current);
    $newName = $this->CoGrouperProvisionerGroup->getGrouperGroupName($new);
    $groupDescription = $this->CoGrouperProvisionerGroup->getGrouperGroupDescription($new);
    $groupDisplayExtension = $this->CoGrouperProvisionerGroup->getGroupDisplayExtension($new);

    if(!$grouper = $this->grouperRestClientFactory($coProvisioningTargetData)) {
      return false;
    }

    try {
      $grouper->groupUpdate($currentName, $newName, $groupDescription, $groupDisplayExtension);
    } catch (GrouperRestClientException $e) {
      $this->log("GrouperProvisioner unable to update group $currentName: $e");
      return false;
    }

    // Update the mapping.
    $new['CoGrouperProvisionerGroup']['id'] = $current['CoGrouperProvisionerGroup']['id'];
    $this->CoGrouperProvisionerGroup->updateProvisionerGroup($new);

    return true;
  }

  /**
   * Update a group membership.
   *
   * @since COmanage Registry v2.0.0
   * @param  array $coProvisioningTargetData CO provisioning target data
   * @param  array $coGroup CoGroup provisioning data
   * @return boolean true on success
   *
   */

  public function updateGroupMembership($coProvisioningTargetData, $coGroup) {
    // CoGroupMember is only present if a membership change has happened,
    // but we need to make sure that we are not being called because a CoPerson
    // has been deleted.
    if (isset($coGroup['CoGroup']['CoPerson']) &&
          $coGroup['CoGroup']['CoPerson']['status'] != StatusEnum::Deleted) {

      $coGroupMember = $coGroup['CoGroup']['CoGroupMember'];
      $subject = $this->getGrouperSubject($coProvisioningTargetData, $coGroup);

      if (empty($subject)) {
        $coPersonId = $coGroup['CoGroup']['CoPerson']['id'];
        $this->log("GrouperProvisioner is unable to compute the Grouper subject for coPersonId = $coPersonId");
        return false;
      }

      $provisionerGroup = $this->CoGrouperProvisionerGroup->findProvisionerGroup($coProvisioningTargetData, $coGroup);
      $groupName = $this->CoGrouperProvisionerGroup->getGrouperGroupName($provisionerGroup);

      if(!$grouper = $this->grouperRestClientFactory($coProvisioningTargetData)) {
        return false;
      }

      try {
        // If CoGroupMember is empty or the member flag is false then it is a membership delete, 
        // otherwise if the member flag is true it is a membership add.
        if (!$coGroupMember) {
          $grouper->deleteManyMember($groupName, array($subject));
        } elseif (!$coGroupMember[0]['member'] && ($coGroupMember[0]['co_group_id'] == $coGroup['CoGroup']['id'])) {
          $grouper->deleteManyMember($groupName, array($subject));
        } elseif ($coGroupMember[0]['member'] && ($coGroupMember[0]['co_group_id'] == $coGroup['CoGroup']['id'])) {
          $grouper->addManyMember($groupName, array($subject));
        } else {
          // This should not happen so log if we get here.
          $this->log("GrouperProvisioner is unable to determine membership status");
        }

        // Update provisioner group table to record new modified time.
        $this->CoGrouperProvisionerGroup->updateProvisionerGroup($provisionerGroup);     

      } catch (GrouperRestClientException $e) {
        $this->log("GrouperProvisioner unable to update group membership for subject $subject");
        return false;
      }

    }

    return true;
  }

  /**
   * Test a Grouper server to verify that the connection available is valid.
   *
   * @since  COmanage Registry v0.8.3
   * @param  string $serverUrl Server URL (https://some.server)
   * @param  string $contextPath Context path (/grouper-ws)
   * @param  string $login Login 
   * @param  string $password Password
   * @param  string $stemName Base stem to use for provisioning this CO
   * @return boolean true if parameters are valid
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
