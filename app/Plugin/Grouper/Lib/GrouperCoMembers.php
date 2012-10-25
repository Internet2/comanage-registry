<?php
/**
 * COmanage Registry Grouper CO Members
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
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses('GrouperRestClient', 'Grouper.Lib');
App::uses('GrouperRestClientException', 'Grouper.Lib');
App::uses('GrouperCoMembersException', 'Grouper.Lib');
App::uses('GrouperCoGroup', 'Grouper.Lib');

/**
 * An instance represents a translation between a COmanage group
 * membership and a Grouper group membership. 
 *
 * @since COmanage Registry 0.7
 */
class GrouperCoMembers {
  // These are the columns normally found in a table representation.
  public $id = null;
  public $coGroupId = null;
  public $coPersonId = null;
  public $member = null;
  public $owner = null;

  // This is the Grouper immediate membership id for the membership.
  public $membershipId = null;

  // Name of group and owner role for the membership instance.
  public $groupName = null;
  public $ownerRoleName = null;

  // Holds the GrouperRestClient instance.
  private $_connection = null;

  /**
   * Constructor for GrouperCoMembers
   *
   * @since  COmanage Directory 0.7
   * @return instance
   */
  public function __construct() {
    // Read configuration details.
    $this->comanageBaseStem = Configure::read('Grouper.COmanage.baseStem');

    $this->groupMembersIdAttributeName = Configure::read('Grouper.COmanage.groupMembersIdAttributeName');
    $this->groupMembersCoGroupIdAttributeName = Configure::read('Grouper.COmanage.groupMembersCoGroupIdAttributeName');
    $this->groupMembersCoPersonIdAttributeName = Configure::read('Grouper.COmanage.groupMembersCoPersonIdAttributeName');

    // Create instance of rest client to Grouper.
    $this->_connection = new GrouperRestClient();
  }

  /**
   * Destructor for GrouperCoMembers
   *
   * @since  COmanage Directory 0.7
   * @return void
   */
  public function __destruct() {
  }

  /**
   * Delete a membership.
   *
   * @since         COmanage Registry 0.7
   * @todo          Make transactional and remove assumption that CO person id is the Grouper subject
   * @return        void
   * @throws        GrouperCoMembersException 
   */
  public function delete() {
    // Find the group object.
    $group = GrouperCoGroup::fromId($this->coGroupId);

    try {
      if ($this->owner) {
        $group->removeOwner($this->coPersonId);
      }

      if ($this->member) {
        $group->removeMember($this->coPersonId);
      }
    } catch (GrouperCoGroupException $e) {
      throw new GrouperCoMembersException("Error deleting membership :" .  $e->getMessage());
    }
  }

  /**
   * Factory function that returns a list of instances that have the 
   * correct CO group id property as stored as an attribute on the membership
   * in grouper.
   *
   * @since         COmanage Registry 0.7
   * @todo          Make transactional.
   * @coGroupId     COmanage CO group id
   * @return        list of instances
   * @throws        GrouperCoMembersException 
   */
  public static function fromCoGroupId($coGroupId) {
    $instance = new self();
    
    // Find all memberships where the CO group Id attribute has
    // the correct value. Note that some of these memberships may
    // be memberships in only the owner role group if the subject is
    // only an owner and not a member of the group.
    try {
      $result = $instance->_connection->getAttributeAssignments(
                                        'imm_mem', 
                                        array($instance->groupMembersCoGroupIdAttributeName), 
                                        'string', 
                                        $coGroupId);

    } catch (GrouperRestClientException $e) {
      throw new GrouperCoMembersException("Error finding memberships with CO group Id $coGroupId: " . $e->getMessage());
    }

    $rawMemberships = $result->WsGetAttributeAssignmentsResults->wsMemberships;

    if (empty($rawMemberships)) {
      // No members or owners for COmanage group with coGroupId
      // so return an empty array.
      return array();
    }

    // Sort the raw memberships into memberships in the group
    // and memberships in the owner role. Use the CO Person Id
    // as the key.
    $groupMemberships = array();
    $ownerRoleMemberships = array();

    foreach($rawMemberships as $rm) {
      $mb = new self();
      $mb->coGroupId = $coGroupId;
      $mb->coPersonId = $rm->subjectId; //TODO remove assumption that CO Person Id is Grouper subject Id
      $mb->membershipId = $rm->immediateMembershipId;

      // We do not need a client connection object here.
      $mb->_connection = null;

      $group = new GrouperCoGroup();
      if (GrouperCoGroup::isOwnerRoleName($rm->groupName)) {
        // This raw Grouper immediate membership represents an owner.
        $mb->ownerRoleName = $rm->groupName;
        $mb->owner = 1;
        $group->oName = $rm->groupName;
        $group->deriveProperties();
        $mb->groupName = $group->gName;
        $ownerRoleMemberships[$mb->coPersonId] = $mb;
      } else {
        // This raw Grouper immediate membership represents a group member.
        $mb->groupName = $rm->groupName;
        $mb->member = 1;

        // Set the ownership flag to false for now since it will
        // be properly set to true below when group memberships
        // and owner roles are reconciled.
        $mb->owner = 0;
        $group->gName = $rm->groupName;
        $group->deriveProperties();
        $mb->ownerRoleName = $group->oName;
        $groupMemberships[$mb->coPersonId] = $mb;
      }
    }

    // Reconcile group memberships and owner role memberships
    // by looping over each owner role membership and determining
    // if there is a corresponding group membership that is already
    // known.
    foreach ($ownerRoleMemberships as $coPersonId => &$om) {
      if (array_key_exists($coPersonId, $groupMemberships)) {
        // Owner role membership does have a corresponding
        // group membership so this person is both a member
        // and an owner.
        $groupMemberships[$coPersonId]->owner = 1;
      } else {
        // Owner role membership does not have a corresponding
        // group membership so this person is only an owner.
        $om->member = 0;
        $groupMemberships[$coPersonId] = $om;
      }
    }

    // Query for the attributes on each membership to
    // obtain the COmanage id for the membership which is
    // stored as an attribute on the Grouper immediate membership.
    $membershipIds = array();
    foreach ($groupMemberships as $mb) {
      $membershipIds[] = $mb->membershipId;
    }

    try {
      $assignments = $instance->_connection->getImmediateMembershipAttributeAssignmentsBulk($membershipIds); 
    } catch (GrouperRestClientException $e) {
      throw new GrouperCoMembersException("Error querying for attributes on memberships :" .  $e->getMessage());
    }

    foreach ($assignments as $membershipId => $a) {
      $coPersonId = $a[$instance->groupMembersCoPersonIdAttributeName];
      $id = $a[$instance->groupMembersIdAttributeName];
      $groupMemberships[$coPersonId]->id = $id;
    }

    // Return an array of membership objects.
    return $groupMemberships;
  }

  /**
   * Factory function that returns a list of instances that have the 
   * correct CO person id property as stored as an attribute on the membership
   * in grouper.
   *
   * @since         COmanage Registry 0.7
   * @todo          Make transactional.
   * @coPersonId    COmanage CO person id
   * @return        list of instances
   * @throws        GrouperCoMembersException 
   */
  public static function fromCoPersonId($coPersonId) {
    $instance = new self();
    
    // Find all memberships where the CO Person Id attribute has
    // the correct value. Note that some of these memberships may
    // be memberships in only the owner role group if the subject is
    // only an owner and not a member of the group.
    try {
      $result = $instance->_connection->getAttributeAssignments(
                                        'imm_mem', 
                                        array($instance->groupMembersCoPersonIdAttributeName), 
                                        'integer', 
                                        $coPersonId);

    } catch (GrouperRestClientException $e) {
      throw new GrouperCoMembersException("Error finding memberships with CO Person Id $coPersonId: " . $e->getMessage());
    }

    $rawMemberships = $result->WsGetAttributeAssignmentsResults->wsMemberships;

    if (empty($rawMemberships)) {
      // No memberships for this CO Person Id
      // so return an empty array.
      return array();
    }

    // Sort the raw memberships into memberships in a group
    // and memberships in an owner role. Use the CO Person Id
    // as the key.
    $groupMemberships = array();
    $ownerRoleMemberships = array();

    foreach($rawMemberships as $rm) {
      $mb = new self();
      $mb->coPersonId = $coPersonId; //TODO remove assumption that CO Person Id is Grouper subject Id
      $mb->membershipId = $rm->immediateMembershipId;

      // We do not need a client connection object here.
      $mb->_connection = null;

      $group = new GrouperCoGroup();
      if (GrouperCoGroup::isOwnerRoleName($rm->groupName)) {
        // This raw Grouper immediate membership represents an owner.
        $mb->ownerRoleName = $rm->groupName;
        $mb->owner = 1;
        $group->oName = $rm->groupName;
        $group->deriveProperties();
        $mb->groupName = $group->gName;
        $ownerRoleMemberships[$mb->coPersonId] = $mb;
      } else {
        // This raw Grouper immediate membership represents a group member.
        $mb->groupName = $rm->groupName;
        $mb->member = 1;

        // Set the ownership flag to false for now since it will
        // be properly set to true below when group memberships
        // and owner roles are reconciled.
        $mb->owner = 0;
        $group->gName = $rm->groupName;
        $group->deriveProperties();
        $mb->ownerRoleName = $group->oName;
        $groupMemberships[$mb->coPersonId] = $mb;
      }
    }

    // Reconcile group memberships and owner role memberships
    // by looping over each owner role membership and determining
    // if there is a corresponding group membership that is already
    // known.
    foreach ($ownerRoleMemberships as $coPersonId => &$om) {
      if (array_key_exists($coPersonId, $groupMemberships)) {
        // Owner role membership does have a corresponding
        // group membership so this person is both a member
        // and an owner.
        $groupMemberships[$coPersonId]->owner = 1;
      } else {
        // Owner role membership does not have a corresponding
        // group membership so this person is only an owner.
        $om->member = 0;
        $groupMemberships[$coPersonId] = $om;
      }
    }

    // Query for the attributes on each membership to
    // obtain the COmanage id for the membership which is
    // stored as an attribute on the Grouper immediate membership.
    $membershipIds = array();
    foreach ($groupMemberships as $mb) {
      $membershipIds[] = $mb->membershipId;
    }

    try {
      $assignments = $instance->_connection->getImmediateMembershipAttributeAssignmentsBulk($membershipIds); 
    } catch (GrouperRestClientException $e) {
      throw new GrouperCoMembersException("Error querying for attributes on memberships :" .  $e->getMessage());
    }

    foreach ($assignments as $membershipId => $a) {
      $coPersonId = $a[$instance->groupMembersCoPersonIdAttributeName];
      $id = $a[$instance->groupMembersIdAttributeName];
      $coGroupId = $a[$instance->groupMembersCoGroupIdAttributeName];
      $groupMemberships[$coPersonId]->id = $id;
      $groupMemberships[$coPersonId]->coGroupId = $coGroupId;
    }

    // Return an array of membership objects.
    return $groupMemberships;
  }

  /**
   * Factory function that returns an instance that has the 
   * correct COmanage id as stored as an attribute on the membership
   * in grouper.
   *
   * @since         COmanage Registry 0.7
   * @todo          Make transactional.
   * @id            COmanage id for the membership as found in the cm_co_group_members table
   * @return        instance
   * @throws        GrouperCoMembersException 
   */
  public static function fromId($id) {
    $instance = new self();
    
    // Find all memberships where the CO group members id attribute has
    // the correct value. At most there can be two, one representing
    // the CO group membership and one representing the owner role.
    try {
      $result = $instance->_connection->getAttributeAssignments(
                                        'imm_mem', 
                                        array($instance->groupMembersIdAttributeName), 
                                        'string', 
                                        $id);

    } catch (GrouperRestClientException $e) {
      throw new GrouperCoMembersException("Error finding memberships with Id $id: " . $e->getMessage());
    }

    $rawMemberships = $result->WsGetAttributeAssignmentsResults->wsMemberships;

    // Sort the raw memberships into memberships in the group
    // and memberships in the owner role. 
    $groupMembership = null;
    $ownerRoleMembership = null;

    foreach($rawMemberships as $rm) {
      $mb = new self();
      $mb->id = $id;
      $mb->coPersonId = $rm->subjectId; //TODO remove assumption that CO Person Id is Grouper subject Id
      $mb->membershipId = $rm->immediateMembershipId;

      // We do not need a client connection object here.
      $mb->_connection = null;

      $group = new GrouperCoGroup();
      if (GrouperCoGroup::isOwnerRoleName($rm->groupName)) {
        // This raw Grouper immediate membership represents an owner.
        $mb->ownerRoleName = $rm->groupName;
        $mb->owner = 1;
        $group->oName = $rm->groupName;
        $group->deriveProperties();
        $mb->groupName = $group->gName;
        $ownerRoleMembership = $mb;
      } else {
        // This raw Grouper immediate membership represents a group member.
        $mb->groupName = $rm->groupName;
        $mb->member = 1;

        // Set the ownership flag to false for now since it will
        // be properly set to true below when group memberships
        // and owner roles are reconciled.
        $mb->owner = 0;
        $group->gName = $rm->groupName;
        $group->deriveProperties();
        $mb->ownerRoleName = $group->oName;
        $groupMembership = $mb;
      }
    }

    // Reconcile group membership and owner role membership.
    if (is_null($groupMembership) and !is_null($ownerRoleMembership)){
      // The subject is only an owner and not a member.
      $groupMembership = $ownerRoleMembership;
      $groupMembership->member = 0;
    } elseif (!is_null($groupMembership) and is_null($ownerRoleMembership)) {
      // The subject is only a member and not an owner.
      // No action to take.
    } else {
      // The subject is both a member and an owner.
      $groupMembership->owner = 1;
    }

    // Query for the attributes on the membership to
    // obtain the COmanage CO group id which is
    // stored as an attribute on the Grouper immediate membership.
    try {
      $attributes = $instance->_connection->getImmediateMembershipAttributeAssignments($groupMembership->membershipId); 
    } catch (GrouperRestClientException $e) {
      throw new GrouperCoMembersException("Error querying for attributes on membership :" .  $e->getMessage());
    }

    $coGroupId = $attributes[$instance->groupMembersCoGroupIdAttributeName];
    $groupMembership->coGroupId = $coGroupId;

    // Return the membership object.
    return $groupMembership;
  }

  /**
   * Serialize from an instance to a simple array 
   * representing what would be a row returned from
   * the cm_co_group_members table.
   *
   * @since         COmanage Registry 0.7
   * @return        array
   */
  public function serializeFromObject() {
    return array(
      'id' => $this->id,
      'co_group_id' => $this->coGroupId,
      'co_person_id' => $this->coPersonId,
      'member' => $this->member,
      'owner' => $this->owner,
    );
  }

  /**
   * Update the details of a membership.
   *
   * @since         COmanage Registry 0.7
   * @todo          Needs to be transactional
   * @newValues     array of values to be assigned, property names are keys, values to be updated are values
   * @return        void
   * @throws        GrouperCoMembersException 
   */
  public function update($newValues) {
    // At this time the only attributes that can change
    // are the member and owner attributes.

    // Find the group object.
    $group = GrouperCoGroup::fromId($this->coGroupId);
    
    if ($newValues['member'] != $this->member) {
      if ($this->member) {
        // Is a member so now remove the membership.
        try {
          $group->removeMember($this->coPersonId);
        } catch (GrouperCoGroupException $e) {
          throw new GrouperCoMembersException("Error updating membership :" .  $e->getMessage());
        }
      } else {
        // Is not a member so now add the membership.
        try {
          $group->addMember($this->id, $this->coGroupId, $this->coPersonId);
        } catch (GrouperCoGroupException $e) {
          throw new GrouperCoMembersException("Error updating membership :" .  $e->getMessage());
        }
      }
    }

    if ($newValues['owner'] != $this->owner) {
      if ($this->owner) {
        // Is an owner so now remove the owner role.
        try {
          $group->removeOwner($this->coPersonId);
        } catch (GrouperCoGroupException $e) {
          throw new GrouperCoMembersException("Error updating membership :" .  $e->getMessage());
        }
      } else {
        // Is not an owner so now add the owner role.
        try {
          $group->addOwner($this->id, $this->coGroupId, $this->coPersonId);
        } catch (GrouperCoGroupException $e) {
          throw new GrouperCoMembersException("Error updating membership :" .  $e->getMessage());
        }

      }
    }

  }
}
