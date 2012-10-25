<?php
/**
 * COmanage Registry Grouper
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses('GrouperRestClient', 'Grouper.Lib');
App::uses('GrouperRestClientException', 'Grouper.Lib');
App::uses('GrouperCoStem', 'Grouper.Lib');
App::uses('GrouperCoStemException', 'Grouper.Lib');
App::uses('GrouperCoGroupException', 'Grouper.Lib');

/**
 * An instance represents a translation between a COmanage group
 * and a Grouper group. 
 *
 * @since COmanage Registry 0.7
 */
class GrouperCoGroup {
  // These are the columns normally found in a table representation.
  public $id = null;    
  public $coId = null;
  public $coName = null;
  public $name = null;
  public $description = null;
  public $open = null;
  public $status = null;

  // These are Grouper details for the group.
  public $gDescription = null;
  public $gDisplayName = null;
  public $gDisplayExtension = null;
  public $gExtension = null;
  public $gName = null;
  public $gStem = null;
  public $gUuid = null;

  // These are Grouper details for the related owner role.
  public $oDescription = null;
  public $oDisplayName = null;
  public $oDisplayExtension = null;
  public $oExtension = null;
  public $oName = null;
  public $oStem = null;
  public $oUuid = null;

  // The base stem in Grouper under which all COmanage details are stored.
  private $comanageBaseStem = null;

  // The stem delineator being used by Grouper, so that we can normalize
  // it away for some Grouper fields if found as input into COmanage,
  // for example as the group name.
  private $grouperStemDelineator = null;

  // A replacement to use if delineator is found.
  private $grouperStemDelineatorReplacement = null;

  // Grouper AttributeDefNameName(s) used for storing COmanage data
  // that would be found in a row in the cm_co_groups table.
  private $groupCoIdAttributeName = null;
  private $groupIdAttributeName = null;
  private $groupStatusAttributeName = null;

  // Grouper AttributeDefNameName(s) used for storing COmanage data
  // that would be found in a row in the cm_co_group_members table.
  private $groupMembersIdAttributeName = null;
  private $groupMembersCoGroupIdAttributeName = null;
  private $groupMembersCoPersonIdAttributeName = null;

  // Suffixes to use when building owner role from COmanage group details.
  private $ownerRoleDescriptionSuffix = null;
  private $ownerRoleDisplayNameSuffix = null;
  private $ownerRoleDisplayExtensionSuffix = null;
  private $ownerRoleExtensionSuffix = null;
  private $ownerRoleNameSuffix = null;

  // Holds the GrouperRestClient instance.
  private $_connection = null;

  /**
   * Constructor for GrouperCoGroup
   *
   * @since  COmanage Directory 0.7
   * @return instance
   */
  public function __construct() {
    // Read configuration details.
    $this->comanageBaseStem = Configure::read('Grouper.COmanage.baseStem');
    $this->grouperStemDelineator = Configure::read('Grouper.COmanage.grouperStemDelineator');
    $this->grouperStemDelineatorReplacement = Configure::read('Grouper.COmanage.grouperStemDelineatorReplacement');
    $this->ownerRoleDescriptionSuffix = Configure::read('Grouper.COmanage.ownerRoleDescriptionSuffix');
    $this->ownerRoleDisplayNameSuffix = Configure::read('Grouper.COmanage.ownerRoleDisplayNameSuffix');
    $this->ownerRoleDisplayExtensionSuffix = Configure::read('Grouper.COmanage.ownerRoleDisplayExtensionSuffix');
    $this->ownerRoleExtensionSuffix = Configure::read('Grouper.COmanage.ownerRoleExtensionSuffix');
    $this->ownerRoleNameSuffix = Configure::read('Grouper.COmanage.ownerRoleNameSuffix');

    $this->groupCoIdAttributeName = Configure::read('Grouper.COmanage.groupCoIdAttributeName');
    $this->groupIdAttributeName = Configure::read('Grouper.COmanage.groupIdAttributeName');
    $this->groupStatusAttributeName = Configure::read('Grouper.COmanage.groupStatusAttributeName');

    $this->groupMembersIdAttributeName = Configure::read('Grouper.COmanage.groupMembersIdAttributeName');
    $this->groupMembersCoGroupIdAttributeName = Configure::read('Grouper.COmanage.groupMembersCoGroupIdAttributeName');
    $this->groupMembersCoPersonIdAttributeName = Configure::read('Grouper.COmanage.groupMembersCoPersonIdAttributeName');

    // Create instance of rest client to Grouper.
    $this->_connection = new GrouperRestClient();
  }

  /**
   * Destructor for GrouperCoGroup
   *
   * @since  COmanage Directory 0.7
   * @return void
   */
  public function __destruct() {
  }

  /**
   * Add a member to a Grouper group and store details in attributes.
   *
   * @since         COmanage Registry 0.7
   * @todo          Make transactional and remove assumption that CO person id is the Grouper subject
   * @membershipId  COmanage id for this membership, the id in the cm_co_group_members table
   * @coGroupId     COmanage CO group id for the group, the id in the cm_co_groups table
   * @coPersonId    COmanage CO person id for the member being added to the group
   * @return        void
   * @throws        GrouperCoGroupException 
   */
  public function addMember($membershipId, $coGroupId, $coPersonId) {
    // TODO Make this transactional

    // Add the subject to the Grouper group.
    // TODO Remove assumption that the CO Person ID is the Grouper subject.
    try {
      $this->_connection->addMember($this->gName, $coPersonId);

      // Get the id for the immediate membership just created.
      $immediateMembershipId = $this->_connection->getImmediateMembershipId($this->gName, $coPersonId);

    // Assign attributes on the immediate membership.
      $assignments = array(
        $this->groupMembersIdAttributeName => $membershipId,
        $this->groupMembersCoGroupIdAttributeName => $coGroupId,
        $this->groupMembersCoPersonIdAttributeName => $coPersonId
        );
      $this->_connection->assignAttributeWithValueBatch($assignments, 'imm_mem', $immediateMembershipId);
    } catch (GrouperRestClientException $e) {
        throw new GrouperCoGroupException("Error adding CO Person $coPersonId to group with CO group Id $coGroupId: " . $e->getMessage());
    }
  }

  /**
   * Add a owner role to a Grouper group and store details in attributes.
   *
   * @since         COmanage Registry 0.7
   * @todo          Make transactional and remove assumption that CO person id is the Grouper subject
   * @membershipId  COmanage id for this membership, the id in the cm_co_group_members table
   * @coGroupId     COmanage CO group id for the group, the id in the cm_co_groups table
   * @coPersonId    COmanage CO person id for the member being added to the group
   * @return        void
   * @throws        GrouperCoGroupException 
   */
  public function addOwner($membershipId, $coGroupId, $coPersonId) {
    // We record the COmanage membership Id, CO group Id, and  CO Person Id
    // as attributes on the Grouper immediate membership in the owner role
    // so that we can search for Grouper immediate memberships by value
    // and find owner role memberships. It is also helpful when a membership
    // is deleted but not an owner role so that the information does not
    // have to be read from the group membership and recorded again on the
    // owner role membership.
    //
    // The disadvantages/trade-offs are more calls and storing the same data in
    // more then one place.

    // Add the subject to the Grouper owner role.
    // TODO Remove assumption that the CO Person ID is the Grouper subject.
    try {
      $this->_connection->addMember($this->oName, $coPersonId);

      // Get the id for the immediate membership just created.
      $immediateMembershipId = $this->_connection->getImmediateMembershipId($this->oName, $coPersonId);

    // Assign attributes on the immediate membership.
      $assignments = array(
        $this->groupMembersIdAttributeName => $membershipId,
        $this->groupMembersCoGroupIdAttributeName => $coGroupId,
        $this->groupMembersCoPersonIdAttributeName => $coPersonId
        );
      $this->_connection->assignAttributeWithValueBatch($assignments, 'imm_mem', $immediateMembershipId);
    } catch (GrouperRestClientException $e) {
        throw new GrouperCoGroupException("Error adding CO Person $coPersonId to owner group: " . $e->getMessage());
    }
  }

  /**
   * Delete the group having the input id. Deletes the group and the owner role.
   *
   * @since         COmanage Registry 0.7
   * @todo          Make transactional
   * @id            COmanage id for the group, the id in the cm_co_groups table
   * @return        void
   * @throws        GrouperCoGroupException 
   */
  public static function deleteById($id) {
    $group = self::fromId($id);

    // TODO Make this transactional.
    try {
      $group->_connection->groupDelete($group->gName);
      $group->_connection->groupDelete($group->oName);
    } catch (GrouperRestClientException $e) {
      throw new GrouperCoGroupException("Error deleting group with id $id: " . $e->getMessage());
    }

    return;
  }

  /**
   * Use existing properties to derive any unset properties. All logic
   * about how Grouper group names and owner role names are related
   * is contained here as well as logic about how group name are
   * normalized.
   *
   * Properties that cannot be derived cheaply without a call out
   * to Grouper are not derived.
   *
   * @since         COmanage Registry 0.7
   * @return        void
   * @throws        GrouperCoGroupException 
   */
  public function deriveProperties() {
    // The CO name can be obtained from the CO Id by
    // a cheap database lookup.
    if (!isset($this->coName)){
      if (isset($this->coId)){
        $Co = ClassRegistry::init('Co');
        $Co->Behaviors->attach('Containable');
        $Co->contain();
        $co = $Co->findById($this->coId);
        $this->coName = $co['Co']['name'];
      }
    }

    // The Grouper group display extension is the
    // unmangled input name for the group.
    if (!isset($this->name)) {
      if (isset($this->gDisplayExtension)){
        $this->name = $this->gDisplayExtension;
      }
    }

    // The input description is the same as the Grouper
    // group description.
    if (!isset($this->description)) {
      if (isset($this->gDescription)) {
        $this->description = $this->gDescription;
      }
    }

    // The input description is the same as the Grouper
    // group description.
    if (!isset($this->gDescription)) {
      if (isset($this->description)) {
        $this->gDescription = $this->description;
      }
    }

    // The Grouper group display extension is the
    // unmangled input name for the group.
    if (!isset($this->gDisplayExtension)) {
      if (isset($this->name)){
        $this->gDisplayExtension = $this->name;
      }
    }

    // The group display name is the unmangled name passed in as input
    // added to the stem display name.
    if (!isset($this->gDisplayName)) {
      if (isset($this->gStem) and isset($this->name)) {
        $this->gDisplayName = $this->gStem->displayName . $this->grouperStemDelineator . $this->name;
      } elseif (isset($this->oStem) and isset($this->name)) {
        $this->gDisplayName = $this->oStem->displayName . $this->grouperStemDelineator . $this->name;
      }
    }

    // The group extension is the mangled name passed in as input.
    if (!isset($this->gExtension)){
      if (isset($this->name)){
        $this->gExtension = $this->nameCanonicalize($this->name);
      }
    }

    // The group name is the mangled name passed in as input
    // appended to the stem name.
    if (!isset($this->gName)) {
      if (isset($this->gStem) and isset($this->name)){
        $this->gName = $this->gStem->name . $this->grouperStemDelineator. $this->nameCanonicalize($this->name);
      } elseif (isset($this->oStem) and isset($this->name)){
        $this->gName = $this->oStem->name . $this->grouperStemDelineator. $this->nameCanonicalize($this->name);
      }
    }

    // The owner role stem and the group stem are the same.
    if (!isset($this->gStem)) {
      if (isset($this->oStem)){
        $this->gStem = $this->oStem;
      }
    }

    // The owner role stem and the group stem are the same.
    if (!isset($this->oStem)) {
      if (isset($this->gStem)){
        $this->oStem = $this->gStem;
      }
    }

    // The owner role description is the same as the CoGroup
    // description with the configured owner role description suffix
    // appended. It is also equivalent to the Grouper group description
    // with the configured owner role description suffix appended.
    if (!isset($this->oDescription)) {
      if (isset($this->description)){
        $this->oDescription = $this->description . $this->ownerRoleDescriptionSuffix;
      } elseif (isset($this->gDescription)){
        $this->oDescription = $this->gDescription . $this->ownerRoleDescriptionSuffix;
      }
    }

    // The owner role display name is the unmangled name passed in as input
    // with the configured owner role display name suffix.
    // It is also equivalent to the Grouper group display name with
    // the configured owner role display name suffix.
    if (!isset($this->oDisplayName)){
      if (isset($this->oStem) and isset($this->name)) {
        $this->oDisplayName = $this->oStem->displayName . $this->grouperStemDelineator . $this->name .  $this->ownerRoleDisplayNameSuffix;
      } elseif (isset($this->gDisplayName)) {
        $this->oDisplayName = $this->gDisplayName . $this->ownerRoleDisplayNameSuffix;
      }
    }

    // The owner display extension is the unmangled name passed in as input
    // with the configured owner role display extension suffix appended.
    if (!isset($this->oDisplayExtension)) {
      if (isset($this->name)) {
        $this->oDisplayExtension = $this->name . $this->ownerRoleDisplayExtensionSuffix;
      }
    }

    // The owner role extension is the mangled name passed in as input
    // with the configured suffix added.
    if (!isset($this->oExtension)) {
      if (isset($this->name)){
        $this->oExtension = $this->nameCanonicalize($this->name) . $this->ownerRoleExtensionSuffix;
      }
    }

    // The owner role name is the mangled name passed in as input
    // appended to the stem name with the configured suffix appended.
    // It is also equivalent to the Grouper group name with the configured
    // suffix appended.
    if (!isset($this->oName)) {
      if (isset($this->oStem) and isset($this->name)) {
        $this->oName = $this->oStem->name . $this->grouperStemDelineator. $this->nameCanonicalize($this->name) .  $this->ownerRoleNameSuffix;
      } elseif (isset($this->gName)) {
        $this->oName = $this->gName .  $this->ownerRoleNameSuffix;
      }
    }
  }

  /**
   * Factory function that returns a list of instances that have the 
   * correct CO id property as stored as an attribute on the group
   * in grouper.
   *
   * @since         COmanage Registry 0.7
   * @coId          COmanage CO id
   * @return        list of instances
   * @throws        GrouperCoGroupException 
   */
  public static function fromCoId($coId) {
    $instance = new self();

    $groups = array();

    try {
      // Find groups by CO Id value and set properities.
      $all = $instance->_connection->getGroupsByAttributeValue($instance->groupCoIdAttributeName, 'integer', $coId);

      foreach($all as $object) {
        $g = new self();
        $g->gDescription = $object->description;
        $g->gDisplayExtension = $object->displayExtension;
        $g->gDisplayName = $object->displayName;
        $g->gExtension = $object->extension;
        $g->gName = $object->name;
        $g->gUuid = $object->uuid;

        // Find attributes for group and set properties.
        $attributes = $g->_connection->getGroupAttributeAssignments($g->gName);

        $g->id     = $attributes[$g->groupIdAttributeName];
        $g->coId   = $attributes[$g->groupCoIdAttributeName];
        $g->status = $attributes[$g->groupStatusAttributeName];

        // Determine if the group is open or not.
        $privileges = array('optin', 'optout');
        $assignedPrivileges = $g->_connection->getPrivileges($g->gName, $privileges, 'GrouperAll');
        if ($assignedPrivileges['optin'] and $assignedPrivileges['optout']) {
          $g->open = 1;
        } elseif (!$assignedPrivileges['optin'] and !$assignedPrivileges['optout']) {
          $g->open = 0;
        } else {
          throw new GrouperCoGroupException("Found group $g->gName with inconsistent optin/optout privileges");
        }

        $g->deriveProperties();

        // We do not need connections here.
        $g->_connection = null;

        $groups[] = $g;
      }

    } catch (GrouperRestClientException $e) {
      throw new GrouperCoGroupException("Error creating groups for CO Id $coId: " . $e->getMessage());
    }

    return $groups;
  }

  /**
   * Factory function that returns the instance that has the 
   * correct id property as stored as an attribute on the group
   * in grouper.
   *
   * @since         COmanage Registry 0.7
   * @id            COmanage id for the groups as found in the cm_co_groups table
   * @return        instance
   * @throws        GrouperCoGroupException 
   */
  public static function fromId($id) {
    $instance = new self();

    $instance->id = $id;

    try {
      // Find group by id value and set properities.
      $all = $instance->_connection->getGroupsByAttributeValue($instance->groupIdAttributeName, 'string', $id);

      if (count($all) > 1) {
        throw new GrouperCoGroupException("Found more than one group with attribute value $id");
      }

      $object = $all[0];

      $instance->gDescription = $object->description;
      $instance->gDisplayExtension = $object->displayExtension;
      $instance->gDisplayName = $object->displayName;
      $instance->gExtension = $object->extension;
      $instance->gName = $object->name;
      $instance->gUuid = $object->uuid;

      // Find attributes for group and set properties.
      $attributes = $instance->_connection->getGroupAttributeAssignments($instance->gName);

      $instance->id     = $attributes[$instance->groupIdAttributeName];
      $instance->coId   = $attributes[$instance->groupCoIdAttributeName];
      $instance->status = $attributes[$instance->groupStatusAttributeName];

      // Determine if the group is open or not.
      $privileges = array('optin', 'optout');
      $assignedPrivileges = $instance->_connection->getPrivileges($instance->gName, $privileges, 'GrouperAll');
      if ($assignedPrivileges['optin'] and $assignedPrivileges['optout']) {
        $instance->open = 1;
      } elseif (!$assignedPrivileges['optin'] and !$assignedPrivileges['optout']) {
        $instance->open = 0;
      } else {
        throw new GrouperCoGroupException("Found group $instance->gName with inconsistent optin/optout privileges");
      }

    } catch (GrouperRestClientException $e) {
      throw new GrouperCoGroupException("Error creating group for id $id: " . $e->getMessage());
    }

    // Derive remaining properties.
    $instance->deriveProperties();

    return $instance;
  }

  /**
   * Factory function that returns an instance using
   * an object returned by many Grouper REST calls
   * as input. The object is often returned when includeGroupDetail
   * is 'T' in the REST call and contains details about the
   * Grouper group.
   *
   * @since         COmanage Registry 0.7
   * @object        simple object as returned from Grouper REST call
   * @return        instance
   * @throws        GrouperCoGroupException 
   */
  public static function fromGroupDetail($object) {
    $instance = new self();

    // Pick off the easy details from the object.
    $instance->gDescription = $object->description;
    $instance->gDisplayName = $object->displayName;
    $instance->gDisplayExtension = $object->displayExtension;
    $instance->gExtension = $object->extension;
    $instance->gName = $object->name;
    $instance->gUuid = $object->uuid;

    try {
      // Find attributes for group and set properties.
      $attributes = $instance->_connection->getGroupAttributeAssignments($instance->gName);

      $instance->id     = $attributes[$instance->groupIdAttributeName];
      $instance->coId   = $attributes[$instance->groupCoIdAttributeName];
      $instance->status = $attributes[$instance->groupStatusAttributeName];

    } catch (GrouperRestClientException $e) {
      throw new GrouperCoGroupException("Error creating group from details where name is $instance->name: " . $e->getMessage());
    }

    // Derive remaining properties.
    $instance->deriveProperties();

    return $instance;
  }

  /**
   * Factory function that returns an instance after creating
   * the necessary group in Grouper and owner role and storing
   * properties as attributes.
   *
   * @since         COmanage Registry 0.7
   * @todo          Need to be atomic
   * @id            COmanage id for the group, the id in the table cm_co_groups
   * @coId          COmanage CO id 
   * @name          COmanage name for the group
   * @description   COmanage description for the group
   * @open          COmanage flag for whether the group is open or not
   * @status        COmanage status for the group
   * @return        instance
   * @throws        GrouperCoGroupException 
   */
  public static function fromInputs($id, $coId, $name, $description, $open, $status) {
    $instance = new self();

    $instance->id = $id;
    $instance->coId = $coId;
    $instance->name = $name;
    $instance->description = $description;
    $instance->open = $open;
    $instance->status = $status;

    // Create the stem object.
    try{
      $instance->gStem = GrouperCoStem::fromCoId($coId);
    } catch (GrouperCoStemException $e) {
      throw new GrouperCoGroupException("Error creating group for co_id $coId and name $name: " . $e->getMessage());
    }

    // Derive other properties.
    $instance->deriveProperties();

    //TODO make this atomic
    try {
      // Create the Grouper group.
      $result = $instance->_connection->groupSave(
                                          $instance->gName,
                                          $instance->gDescription,
                                          $instance->gDisplayExtension);
      $instance->gUuid = $result->uuid;

      // Create the owner role.
      $result = $instance->_connection->groupSave(
                                          $instance->oName,
                                          $instance->oDescription,
                                          $instance->oDisplayExtension,
                                          'role');
      $instance->oUuid = $result->uuid;

      // Give the owner role admin privileges on the group.
      $privileges = array('admin');
      $subjects = array($instance->oUuid);
      $subjectSourceId = 'g:gsa';
      $instance->_connection->assignPrivilege($instance->gName, $privileges, $subjects, $subjectSourceId);

      // Create attribute value assignments to record properties in Grouper.
      $assignments = array(
        $instance->groupIdAttributeName => $instance->id,
        $instance->groupCoIdAttributeName => $instance->coId,
        $instance->groupStatusAttributeName =>$instance->status
        );

      $instance->_connection->assignAttributeWithValueBatch($assignments, 'group', $instance->gName);

      // If the group is open set the optin and optout privileges for
      // the 'GrouperAll' subject, which is equivalent to setting the
      // default privileges for the group using the UI.
      if ($instance->open) {
        $privileges = array('optin', 'optout');
        $subjects = array('GrouperAll');
        $instance->_connection->assignPrivilege($instance->gName, $privileges, $subjects);
      }

    } catch (GrouperRestClientException $e) {
      throw new GrouperCoGroupException("Error creating group for co_id $coId and name $name: " . $e->getMessage());
    }

    return $instance;
  }

  /**
   * Test if a group and owner role are correlated. 
   *
   * @since         COmanage Registry 0.7
   * @groupName     full name of the group
   * @ownerRoleName full name of the owner role
   * @return        true or false
   */
  public static function groupOwnerRoleNameTest($groupName, $ownerRoleName){
    $instance = new self();
    $instance->gName = $groupName;
    $instance->deriveProperties();

    $corresponding = null;
    if($instance->oName == $ownerRoleName){
      $corresponding = true;
    } else {
      $corresponding = false;
    }

    return $corresponding;
  }

  /**
   * Test if a string is a valid owner role name.
   *
   * @since         COmanage Registry 0.7
   * @name          full name of the owner role
   * @return        true or false
   */
  public static function isOwnerRoleName($name) {
    $instance = new self();
    
    $suffix = substr($name, -1 * strlen($instance->ownerRoleNameSuffix));
    if ($suffix == $instance->ownerRoleNameSuffix) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Canonicalize a string so it is suitable for use with Grouper
   * as the extension (not the displayExtension).
   *
   * @since         COmanage Registry 0.7
   * @name          input string to canonicalize
   * @return        string
   */
  private function nameCanonicalize($name) {
    // Remove any spaces.
    $name = str_replace(' ', '', $name);

    // Replace delineators, usually ':'
    $name = str_replace($this->grouperStemDelineator, $this->grouperStemDelineatorReplacement, $name);
    return $name;
  }

  /**
   * Remove a CO person from a Grouper group.
   *
   * @since         COmanage Registry 0.7
   * @todo          Remove assumption that CO Person ID is the Grouper subject.
   * @coPersonId    COmanage CO Person Id for the subject to remove.
   * @return        void
   * @throws        GrouperCoGroupException 
   */
  public function removeMember($coPersonId) {
    // Remove the subject from the Grouper group.
    // TODO Remove assumption that the CO Person ID is the Grouper subject.
    try {
      $this->_connection->deleteMember($this->gName, $coPersonId);
    } catch (GrouperRestClientException $e) {
        throw new GrouperCoGroupException("Error removing CO Person $coPersonId from group $this->gName: " . $e->getMessage());
    }
  }

  /**
   * Remove a CO person from a Grouper owner role.
   *
   * @since         COmanage Registry 0.7
   * @todo          Remove assumption that CO Person ID is the Grouper subject.
   * @coPersonId    COmanage CO Person Id for the subject to remove.
   * @return        void
   * @throws        GrouperCoGroupException 
   */
  public function removeOwner($coPersonId) {
    // Remove the subject from the Grouper group.
    // TODO Remove assumption that the CO Person ID is the Grouper subject.
    try {
      $this->_connection->deleteMember($this->oName, $coPersonId);
    } catch (GrouperRestClientException $e) {
        throw new GrouperCoGroupException("Error removing CO Person $coPersonId from owner role $this->oName" . $e->getMessage());
    }
  }

  /**
   * Serialize from an instance to a simple array 
   * representing what would be a row returned from
   * the cm_co_groups table.
   *
   * @since         COmanage Registry 0.7
   * @return        array
   */
  public function serializeFromObject(){
    return array(
      'id' => $this->id,
      'co_id' => $this->coId,
      'name' => $this->name,
      'description' => $this->description,
      'open' => $this->open,
      'status' => $this->status
     );
  }

  /**
   * Update the details of a group.
   *
   * @since         COmanage Registry 0.7
   * @todo          Needs to be transactional
   * @newValues     array of values to be assigned, property names are keys, values to be updated are values
   * @return        void
   * @throws        GrouperCoGroupException 
   */
  public function update($newValues){

    // Updating the name and description is a distinct operation from
    // updating the other attributes so consider each operation individually.
    if (($newValues['name'] != $this->name) or ($newValues['description'] != $this->description)) {
      $instance = new self();
      $instance->name = $newValues['name'];
      $instance->description = $newValues['description'];
      $instance->gStem = $this->gStem;
      $instance->deriveProperties();

      // TODO this needs to be transactional
      try {
        // Update the group.
        $this->_connection->groupUpdate($this->gName, $instance->gName, $instance->gDescription, $instance->gDisplayExtension);
        // Update the owner role group.
        $this->_connection->groupUpdate($this->oName, $instance->oName, $instance->oDescription, $instance->oDisplayExtension);
      } catch (GrouperRestClientException $e) {
        throw new GrouperCoGroupException("Error updating group with name $this->gName: " . $e->getMessage());
      }

    }

    // Update the optin and optout privileges if necessary.
    $open = $newValues['open'];
    if($open != $this->open) {
      $privileges = array('optin', 'optout');
      $subjects = array('GrouperAll');
      try {
        if ($open) {
          $this->_connection->assignPrivilege($this->gName, $privileges, $subjects);
        } else {
          $this->_connection->removePrivilege($this->gName, $privileges, $subjects);
        }
      } catch (GrouperRestClientException $e) {
        throw new GrouperCoGroupException("Error updating group with name $this->gName so that open is $open: " . $e->getMessage());
      } 
    }

    // Update the status if necessary.
    if($newValues['status'] != $this->status) {
      try{
        $this->_connection->updateAttributeWithValueToGroup($this->groupStatusAttributeName, $this->gName, $newValues['status']); 
      } catch (GrouperRestClientException $e) {
        throw new GrouperCoGroupException("Error updating group status for group with name $this->gName: " . $e->getMessage());
      }
    }
  }
}
