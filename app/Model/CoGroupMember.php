<?php
/**
 * COmanage Registry CO Group Member Model
 *
 * Copyright (C) 2011-17 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-17 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
class CoGroupMember extends AppModel {
  // Define class name for cake
  public $name = "CoGroupMember";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A CoGroupMember is attached to one CoGroup
    "CoGroup",
    // A CoGroupMember is attached to one CoPerson
    "CoPerson",
    // A CoGroupMember created from a Pipeline has a Source Org Identity
    "SourceOrgIdentity" => array(
      'className' => 'OrgIdentity',
      'foreignKey' => 'source_org_identity_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "co_person_id";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order?
//  public $order = array("co_person_id");
  
  public $actsAs = array('Containable',
                         'Provisioner',
                         'Changelog' => array('priority' => 5));

  // Validation rules for table elements
  public $validate = array(
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true
      )
    ),
    'member' => array(
      'content' => array(
        'rule' => array('boolean')
      )
    ),
    'owner' => array(
      'content' => array(
        'rule' => array('boolean')
      )
    ),
    'source_org_identity_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => false,
        'allowEmpty' => true
      )
    )
  );

  /**
   * Add a membership in a CO Group given by name. This
   * method directly creates a history record with an
   * anonymous actor since this is primarily used for
   * COU members group management.
   * 
   * @since COmanage Registry v0.9.3
   * @param Integer $coPersonId CO Person ID
   * @param String $groupName Name of CO Group
   * @param Boolean $owner True if owner
   * @param Boolean $provision Whether to run provisioners
   */
  function addByGroupName($coPersonId, $groupName, $owner = false, $provision = true) {
    // Find the CO using CO person.
    $args = array();
    $args['conditions']['CoPerson.id'] = $coPersonId;
    $args['contain'] = false;
    $coPerson = $this->CoPerson->find('first', $args);
    $coId = $coPerson['CoPerson']['co_id'];

    // Find the group in CO using name.
    $args = array();
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['conditions']['CoGroup.name'] = $groupName;
    $args['contain'] = false;
    $group = $this->CoPerson->Co->CoGroup->find('first', $args);
    if(empty($group)) {
      // XXX shouldn't this throw an InvalidArgumentException?
      return;
    }
    
    // Add the membership.
    $this->clear();
    $data = array();
    $data['CoGroupMember']['co_group_id'] = $group['CoGroup']['id'];
    $data['CoGroupMember']['co_person_id'] = $coPersonId;
    $data['CoGroupMember']['member'] = true;
    $data['CoGroupMember']['owner'] = $owner;
    
    $options = array();
    if(!$provision) {
      $options['provision'] = false;
    }
    
    $this->save($data, $options);
    
    // Cut a history record.
    try {
      $msgData = array(
        $group['CoGroup']['name'],
        $group['CoGroup']['id'],
        _txt('fd.yes'),
        _txt('fd.no')
       );                  
      $msg = _txt('rs.grm.added', $msgData);
      $this->CoPerson->HistoryRecord->record($coPersonId, null, null, null, ActionEnum::CoGroupMemberAdded, $msg, $group['CoGroup']['id']);
    } catch(Exception $e) {
      $msg = _txt('er.grm.history', array($coPersonId, $group['CoGroup']['name']));
      $this->log($msg);
    }      
  }
  
  /**
   * Obtain the member roles for a CO Group.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID
   * @return Array An array of two array: CO Person IDs for the group's members, and CO Person IDs for the group's owners
   */
  
  function findCoGroupRoles($coGroupId) {
    $ret = array(
      'member' => array(),
      'owner'  => array()
    );
    
    $args = array();
    $args['conditions']['CoGroupMember.co_group_id'] = $coGroupId;
    $args['contain'] = false;
    
    $memberships = $this->find('all', $args);
    
    foreach($memberships as $m) {
      if(isset($m['CoGroupMember']['member']) && $m['CoGroupMember']['member']) {
        $ret['member'][] = $m['CoGroupMember']['co_person_id'];
      }
      
      if(isset($m['CoGroupMember']['owner']) && $m['CoGroupMember']['owner']) {
        $ret['owner'][] = $m['CoGroupMember']['co_person_id'];
      }
    }
    
    return $ret;
  }
  
  /**
   * Obtain the group roles for a CO person.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO Person ID
   * @return Array An array of two array: group IDs for which the person is a member, and group IDs for which the person is an owner
   */
  
  function findCoPersonGroupRoles($coPersonId) {
    $ret = array(
      'member' => array(),
      'owner'  => array()
    );
    
    $args = array();
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['contain'] = false;
    
    $memberships = $this->find('all', $args);
    
    foreach($memberships as $m) {
      if(isset($m['CoGroupMember']['member']) && $m['CoGroupMember']['member']) {
        $ret['member'][] = $m['CoGroupMember']['co_group_id'];
      }
      
      if(isset($m['CoGroupMember']['owner']) && $m['CoGroupMember']['owner']) {
        $ret['owner'][] = $m['CoGroupMember']['co_group_id'];
      }
    }
    
    return $ret;
  }
  
  /**
   * Obtain all group members for a CO Group.
   * NOTE: If this function is called within a transaction, a read lock will be obtained (SELECT FOR UPDATE).
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer CO Group ID
   * @param  Integer Maximium number of results to retrieve (or null)
   * @param  Integer Offset to start retrieving results from (or null)
   * @param  String Field to sort by (or null)
   * @return Array Group information, as returned by find
   * @todo   Rewrite to a custom find type
   */
  
  public function findForCoGroup($coGroupId, $limit=null, $offset=null, $order=null) {
    $args = array();
    $args['joins'][0]['table'] = 'co_groups';
    $args['joins'][0]['alias'] = 'CoGroup';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoGroup.id=CoGroupMember.co_group_id';
    $args['fields'] = array(
      'id', 'co_group_id', 'co_person_id', 'member', 'owner'
    );
    $args['conditions']['CoGroup.status'] = StatusEnum::Active;
    $args['conditions']['CoGroup.id'] = $coGroupId;
    $args['contain'] = false;
    
    return $this->findForUpdate($args['conditions'],
                                $args['fields'],
                                $args['joins'],
                                $limit,
                                $offset,
                                $order);
  }

  /**
   * Map a set of CO Group Members to their Identifiers. Based on a similar function in CoLdapProvisionerDn.php.
   *
   * @since  COmanage Registry v0.8.2
   * @param  Array CO Group Members
   * @param  String Identifier to map to
   * @param  Boolean True to map owners, false to map members
   * @return Array Array of Identifiers found -- note this array is not in any particular order, and may have fewer entries
   */
  
  public function mapCoGroupMembersToIdentifiers($coGroupMembers, $identifierType, $owners=false) {
    // Walk through the members and pull the CO Person IDs
    
    $coPeopleIds = array();
    
    foreach($coGroupMembers as $m) {
      if(($owners && $m['CoGroupMember']['owner'])
         || (!$owners && $m['CoGroupMember']['member'])) {
        $coPeopleIds[] = $m['CoGroupMember']['co_person_id'];
      }
    }
    
    if(!empty($coPeopleIds)) {
      // Now perform a find to get the list. Note using the IN notation like this
      // may not scale to very large sets of members.
      
      $args = array();
      $args['conditions']['Identifier.co_person_id'] = $coPeopleIds;
      $args['conditions']['Identifier.type'] = $identifierType;
      $args['fields'] = array('Identifier.co_person_id', 'Identifier.identifier');
      
      return array_values($this->CoPerson->Identifier->find('list', $args));
    } else {
      return array();
    }
  }
  
  /**
   * Sync a group membership based. This function is primarily intended for
   * syncing automatically managed groups (eg: "members").
   *
   * @since  COmanage Registry v1.1.0
   * @param  GroupEnum $coGroupType Type of CO Group to sync membership
   * @param  Integer   $couId       If set, COU ID
   * @param  Integer   $coPersonId CO Person ID of member
   * @param  Boolean   $eligible Whether the person should be in the group
   * @param  Boolean   $provision Whether to run provisioners
   * @param  Boolean   $owner Whether $coPersonId should also be an owner
   * @throws InvalidArgumentException
   */
  
  public function syncMembership($coGroupType, $couId, $coPersonId, $eligible, $provision=true, $owner=false) {
    // Find the CO ID
    $coId = $this->CoPerson->field('co_id', array('CoPerson.id' => $coPersonId));
    
    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_people.1'), $coPersonId)));
    }
    
    // Find the requested group
    
    $args = array();
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['conditions']['CoGroup.group_type'] = $coGroupType;
    // $couId will be NULL for CO level groups
    $args['conditions']['CoGroup.cou_id'] = $couId;
    // We should only sync auto groups
    $args['conditions']['CoGroup.auto'] = true;
    $args['contain'] = false;

    $targetGroup = $this->CoGroup->find('first', $args);

    if(!$targetGroup) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_groups.1'), $coId)));
    }
    
    // Is $coPersonId already a member?
    
    $args = array();
    $args['conditions']['CoGroupMember.co_group_id'] = $targetGroup['CoGroup']['id'];
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['contain'] = false;
    
    // There should really only be one of these
    $groupMember = $this->find('first', $args);
    
    $isMember = (isset($groupMember['CoGroupMember']['member'])
                 && $groupMember['CoGroupMember']['member']);
    
    $hAction = null;
    $hText = "";
    
    if($eligible && !$isMember) {
      // Add a membership
      
      $this->clear();
      $data = array();
      $data['CoGroupMember']['co_group_id'] = $targetGroup['CoGroup']['id'];
      $data['CoGroupMember']['co_person_id'] = $coPersonId;
      $data['CoGroupMember']['member'] = true;
      $data['CoGroupMember']['owner'] = $owner;
      
      $options = array();
      if(!$provision) {
        $options['provision'] = false;
      }
      
      $this->save($data, $options);
      
      $hAction = ActionEnum::CoGroupMemberAdded;
      $hText = _txt('rs.grm.added', array($targetGroup['CoGroup']['name'],
                                          $targetGroup['CoGroup']['id'],
                                          _txt('fd.yes'),
                                          _txt('fd.no')));
    } elseif(!$eligible && $isMember) {
      // Remove the membership
      $this->_provision = $provision;
      $this->delete($groupMember['CoGroupMember']['id']);
      $this->_provision = true;
      
      $hAction = ActionEnum::CoGroupMemberDeleted;
      $hText = _txt('rs.grm.deleted', array($targetGroup['CoGroup']['name'], $targetGroup['CoGroup']['id']));
    }
    // else nothing to do
    
    if($hAction) {
      // Cut a history record
      
      $this->CoPerson->HistoryRecord->record($coPersonId,
                                             null,
                                             null,
                                             null,
                                             $hAction,
                                             $hText,
                                             $targetGroup['CoGroup']['id']);
    }
  }
  
  /**
   * Update the CO Group Memberships for a CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID
   * @param  Array Array of CO Group Member attributes (id, co_group_id, member, owner)
   * @param  Integer CO Person ID of requester
   * @return Boolean True on success, false otherwise
   * @throws LogicException
   */
  
  public function updateMemberships($coPersonId, $memberships, $requesterCoPersonId) {
    if($coPersonId && !empty($memberships)) {
      // First, pull the current group roles.
      
      $curRoles = $this->findCoPersonGroupRoles($coPersonId);
      
      foreach($memberships as $m) {
        // Reset model state between transactions
        $this->create();
        
        // Determine desired roles for this row
        $member = isset($m['member']) && $m['member'];
        $owner = isset($m['owner']) && $m['owner'];
        
        // Pull the related group information
        $args = array();
        $args['conditions']['id'] = $m['co_group_id'];
        $args['contain'] = false;
        
        $grp = $this->CoGroup->find('first', $args);
        
        // If this is an automatic group skip it
        if(!empty($grp)) {
          if(isset($grp['CoGroup']['auto']) && $grp['CoGroup']['auto']) {
            continue;
          }
        } else {
          throw new InvalidArgumentException(_txt('er.gr.nf', array($m['co_group_id'])));
        }
        
        if(!empty($m['id'])) {
          $args = array();
          $args['conditions']['id'] = $m['id'];
          $args['contain'] = false;
          
          $grpMem = $this->find('first', $args);
          
          if(empty($grpMem)) {
            throw new InvalidArgumentException(_txt('er.grm.nf', array($m['id'])));
          }
          
          if(!$member && !$owner) {
            // If a (CO Group Member) id is specified but member and owner are
            // both false, delete the row and cut a history record.
            
            // Set $this->data so ProvisionerBehavior can run on beforeDelete()
            $this->data = $grpMem;
            
            if(!$this->delete($m['id'], false)) {
              throw new RuntimeException(_txt('er.delete'));
            }
            
            // Cut a history record
            
            try {
              $this->CoPerson->HistoryRecord->record($coPersonId,
                                                     null,
                                                     null,
                                                     $requesterCoPersonId,
                                                     ActionEnum::CoGroupMemberDeleted,
                                                     _txt('rs.grm.deleted', array($grp['CoGroup']['name'],
                                                                                  $m['co_group_id'])),
                                                     $m['co_group_id']);
            }
            catch(Exception $e) {
              throw new RuntimeException($e->getMessage());
            }
          } else {
            // Otherwise, update the row if the member or owner are different than current.
            
            $curMember = isset($grpMem['CoGroupMember']['member']) && $grpMem['CoGroupMember']['member'];
            $curOwner = isset($grpMem['CoGroupMember']['owner']) && $grpMem['CoGroupMember']['owner'];
            
            if(($member != $curMember) || ($owner != $curOwner)) {
              $cogm = array();
              $cogm['CoGroupMember']['id'] = $m['id'];
              $cogm['CoGroupMember']['co_group_id'] = $m['co_group_id'];
              $cogm['CoGroupMember']['co_person_id'] = $coPersonId;
              $cogm['CoGroupMember']['member'] = $member;
              $cogm['CoGroupMember']['owner'] = $owner;
              
              if(!$this->save($cogm)) {
                throw new RuntimeException($this->validationErrors);
              }
              
              // Cut a history record
              
              try {
                $this->CoPerson->HistoryRecord->record($coPersonId,
                                                       null,
                                                       null,
                                                       $requesterCoPersonId,
                                                       ActionEnum::CoGroupMemberEdited,
                                                       _txt('rs.grm.edited', array($grp['CoGroup']['name'],
                                                                                   $m['co_group_id'],
                                                                                   _txt($curMember ? 'fd.yes' : 'fd.no'),
                                                                                   _txt($curOwner ? 'fd.yes' : 'fd.no'),
                                                                                   _txt($member ? 'fd.yes' : 'fd.no'),
                                                                                   _txt($owner ? 'fd.yes' : 'fd.no'))),
                                                       $m['co_group_id']);
              }
              catch(Exception $e) {
                throw new RuntimeException($e->getMessage());
              }
            }
          }
        } else {
          // If id is not specified, but a role has been specified, make sure
          // the CO Person is not already in the group, that the group is in the
          // same CO as the CO Person, and add a new row.
          
          if($member || $owner) {
            if(!in_array($m['co_group_id'], $curRoles['member'])
              && !in_array($m['co_group_id'], $curRoles['owner'])) {
              if($grp['CoGroup']['co_id']
                 != $this->CoPerson->field('co_id', array('id' => $coPersonId))) {
                throw new InvalidArgumentException(_txt('er.co.mismatch', array("CoGroup", $m['co_group_id'])));
              }
              
              // We can finally add a new CoGroupMember
              
              $cogm = array();
              $cogm['CoGroupMember']['co_group_id'] = $m['co_group_id'];
              $cogm['CoGroupMember']['co_person_id'] = $coPersonId;
              $cogm['CoGroupMember']['member'] = $member;
              $cogm['CoGroupMember']['owner'] = $owner;
              
              if(!$this->save($cogm)) {
                throw new RuntimeException($this->validationErrors);
              }
              
              // Cut a history record
              
              try {
                $this->CoPerson->HistoryRecord->record($coPersonId,
                                                       null,
                                                       null,
                                                       $requesterCoPersonId,
                                                       ActionEnum::CoGroupMemberAdded,
                                                       _txt('rs.grm.added', array($grp['CoGroup']['name'],
                                                                                  $m['co_group_id'],
                                                                                  _txt($member ? 'fd.yes' : 'fd.no'),
                                                                                  _txt($owner ? 'fd.yes' : 'fd.no'))),
                                                       $m['co_group_id']);
              }
              catch(Exception $e) {
                throw new RuntimeException($e->getMessage());
              }
            } else {
              // We shouldn't get here since $m['id'] should have been set if the CO person
              // already had a role in the group
              
              throw new LogicException(_txt('er.grm.already', array($coPersonId, $m['co_group_id'])));
            }
          }
        }
      }
      
      return true;
    }
    
    return false;
  }
  
  /**
   * Update the CO Group Memberships for a CO Group.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Group ID
   * @param  Array Array of CO Group Member attributes (id, co_person_id, member, owner)
   * @param  Integer CO Person ID of requester
   * @return Boolean True on success, false otherwise
   * @throws LogicException
   * @todo   Perhaps consolidate with updateMemberships (lots of duplicate code)
   */
  
  public function updateGroupMemberships($coGroupId, $memberships, $requesterCoPersonId) {
    if($coGroupId && !empty($memberships)) {
      // First, pull the current group memberships.
      
      $curRoles = $this->findCoGroupRoles($coGroupId);
      
      // Pull the related group information
      $args = array();
      $args['conditions']['id'] = $coGroupId;
      $args['contain'] = false;
      
      $grp = $this->CoGroup->find('first', $args);
      
      if(empty($grp)) {
        throw new InvalidArgumentException(_txt('er.gr.nf', array($coGroupId)));
      }
      
      foreach($memberships as $m) {
        // Determine desired roles for this row
        $member = isset($m['member']) && $m['member'];
        $owner = isset($m['owner']) && $m['owner'];
        
        if(!empty($m['id'])) {
          // There's already a corresponding CO Group Member record
          $args = array();
          $args['conditions']['id'] = $m['id'];
          $args['contain'] = false;
          
          $grpMem = $this->find('first', $args);
          
          if(empty($grpMem)) {
            throw new InvalidArgumentException(_txt('er.grm.nf', array($m['id'])));
          }
          
          if(!$member && !$owner) {
            // If a (CO Group Member) id is specified but member and owner are
            // both false, delete the row and cut a history record.
            
            // Set $this->data so ProvisionerBehavior can run on beforeDelete()
            $this->data = $grpMem;
            
            if(!$this->delete($m['id'], false)) {
              throw new RuntimeException(_txt('er.delete'));
            }
            
            // Cut a history record
            
            try {
              $this->CoPerson->HistoryRecord->record($m['co_person_id'],
                                                     null,
                                                     null,
                                                     $requesterCoPersonId,
                                                     ActionEnum::CoGroupMemberDeleted,
                                                     _txt('rs.grm.deleted', array($grp['CoGroup']['name'],
                                                                                  $coGroupId)),
                                                     $coGroupId);
            }
            catch(Exception $e) {
              throw new RuntimeException($e->getMessage());
            }
          } else {
            // Otherwise, update the row if the member or owner are different than current.
            
            $curMember = isset($grpMem['CoGroupMember']['member']) && $grpMem['CoGroupMember']['member'];
            $curOwner = isset($grpMem['CoGroupMember']['owner']) && $grpMem['CoGroupMember']['owner'];
            
            if(($member != $curMember) || ($owner != $curOwner)) {
              $cogm = array();
              $cogm['CoGroupMember']['id'] = $m['id'];
              $cogm['CoGroupMember']['co_group_id'] = $coGroupId;
              $cogm['CoGroupMember']['co_person_id'] = $m['co_person_id'];
              $cogm['CoGroupMember']['member'] = $member;
              $cogm['CoGroupMember']['owner'] = $owner;
              
              $this->create();
              
              if(!$this->save($cogm)) {
                throw new RuntimeException($this->validationErrors);
              }
              
              // Cut a history record
              
              try {
                $this->CoPerson->HistoryRecord->record($m['co_person_id'],
                                                       null,
                                                       null,
                                                       $requesterCoPersonId,
                                                       ActionEnum::CoGroupMemberEdited,
                                                       _txt('rs.grm.edited', array($grp['CoGroup']['name'],
                                                                                   $coGroupId,
                                                                                   _txt($curMember ? 'fd.yes' : 'fd.no'),
                                                                                   _txt($curOwner ? 'fd.yes' : 'fd.no'),
                                                                                   _txt($member ? 'fd.yes' : 'fd.no'),
                                                                                   _txt($owner ? 'fd.yes' : 'fd.no'))),
                                                       $coGroupId);
              }
              catch(Exception $e) {
                throw new RuntimeException($e->getMessage());
              }
            }
          }
        } else {
          // If id is not specified, but a role has been specified, make sure
          // the CO Person is not already in the group, that the group is in the
          // same CO as the CO Person, and add a new row.
          
          if($member || $owner) {
            if(!in_array($m['co_person_id'], $curRoles['member'])
              && !in_array($m['co_person_id'], $curRoles['owner'])) {
              if($grp['CoGroup']['co_id']
                 != $this->CoPerson->field('co_id', array('id' => $m['co_person_id']))) {
                throw new InvalidArgumentException(_txt('er.co.mismatch', array("CoGroup", $coGroupId)));
              }
              
              // We can finally add a new CoGroupMember
              
              $cogm = array();
              $cogm['CoGroupMember']['co_group_id'] = $coGroupId;
              $cogm['CoGroupMember']['co_person_id'] = $m['co_person_id'];
              $cogm['CoGroupMember']['member'] = $member;
              $cogm['CoGroupMember']['owner'] = $owner;
              
              $this->create();
              
              if(!$this->save($cogm)) {
                throw new RuntimeException($this->validationErrors);
              }
              
              // Cut a history record
              
              try {
                $this->CoPerson->HistoryRecord->record($m['co_person_id'],
                                                       null,
                                                       null,
                                                       $requesterCoPersonId,
                                                       ActionEnum::CoGroupMemberAdded,
                                                       _txt('rs.grm.added', array($grp['CoGroup']['name'],
                                                                                  $coGroupId,
                                                                                  _txt($member ? 'fd.yes' : 'fd.no'),
                                                                                  _txt($owner ? 'fd.yes' : 'fd.no'))),
                                                       $coGroupId);
              }
              catch(Exception $e) {
                throw new RuntimeException($e->getMessage());
              }
            } else {
              // We shouldn't get here since $m['id'] should have been set if the CO person
              // already had a role in the group
              
              throw new LogicException(_txt('er.grm.already', array($m['co_person_id'], $coGroupId)));
            }
          }
        }
      }
      
      return true;
    }
    
    return false;
  }
}
