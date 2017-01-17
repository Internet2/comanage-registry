<?php
/**
 * COmanage Registry CO Group Model
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
  
class CoGroup extends AppModel {
  // Define class name for cake
  public $name = "CoGroup";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $hasMany = array(
    // A CoGroup has zero or more members
    "CoGroupMember" => array('dependent' => true),
    "CoEnrollmentFlowApproverCoGroup" => array(
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'approver_co_group_id'
    ),
    "CoEnrollmentFlowAuthzCoGroup" => array(
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'authz_co_group_id'
    ),
    "CoEnrollmentFlowNotificationGroup" => array(
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'notification_co_group_id'
    ),
    "CoExpirationPolicyNotificationGroup" => array(
      'className' => 'CoExpirationPolicy',
      'foreignKey' => 'act_notify_co_group_id'
    ),
    "CoNotificationRecipientGroup" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'recipient_co_group_id'
    ),
    "CoNotificationSubjectGroup" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'subject_co_group_id'
    ),
    "CoProvisioningExport" => array('dependent' => true),
    "CoProvisioningTargetGroup" => array(
      'className' => 'CoProvisioningTarget',
      'foreignKey' => 'provision_co_group_id'
    ),
    "CoService",
    "CoSettingSponsorCoGroup" => array(
      'className' => 'CoSetting',
      'foreignKey' => 'sponsor_co_group_id'
    ),
    "HistoryRecord"
  );

  public $belongsTo = array("Co");           // A CoGroup is attached to one CO
   
  // Default display field for cake generated views
  public $displayField = "name";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order?
//  public $order = array("CoGroup.name");
  
  public $actsAs = array('Containable',
                         'Provisioner',
                         'Changelog' => array('priority' => 5));

  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'name' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'open' => array(
      'rule' => array('boolean')
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'SuspendableStatusEnum'
  );
  
  /**
   * Obtain the ID of the CO or COU admin group.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer $coId    CO ID
   * @param  String  $couName COU Name, within $coId
   * @return Integer CO Group ID
   * @throws InvalidArgumentException
   */
  
  public function adminCoGroupId($coId, $couName=null) {
    $args = array();
    if($couName) {
      $args['conditions']['CoGroup.name'] = 'admin:' . $couName;
    } else {
      $args['conditions']['CoGroup.name'] = 'admin';
    }
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['conditions']['CoGroup.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;
    
    $coAdminGroup = $this->Co->CoGroup->find('first', $args);
    
    if(!empty($coAdminGroup['CoGroup']['id'])) {
      return $coAdminGroup['CoGroup']['id'];
    }
    
    throw new InvalidArgumentException(_txt('er.gr.nf', array($args['conditions']['CoGroup.name'])));
  }
  
  /**
   * Find name of Cou from a Cou admin or members group.
   * 
   * @since COmanage Registry v0.9.3
   * @param Array CoGroup
   * @return String name of the Cou
   */
  function couNameFromAdminOrMembersGroup($group) {
    if($this->isCouAdminGroup($group)) {
      return substr($group['CoGroup']['name'], 6);  
    } elseif ($this->isCouMembersGroup($group)) {
      return substr($group['CoGroup']['name'], 8);  
    }
  }
  
  /**
   * Find a group for a CO by its name.
   * 
   * @since COmanage Registry v0.9.3
   * @param Inteter CO ID
   * @param String name
   * @return Array Group information, as returned by find
   */
  function findByName($coId, $name) {
    $args = array();
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['conditions']['CoGroup.name'] = $name;
    $args['contain'] = false;
    $group = $this->find('first', $args);
    
    return $group;
  }

  /**
   * Obtain all groups for a CO person.
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO Person ID
   * @param  Integer Maximium number of results to retrieve (or null)
   * @param  Integer Offset to start retrieving results from (or null)
   * @param  String Field to sort by (or null)
   * @param  Boolean Whether to return owner-only records
   * @return Array Group information, as returned by find
   * @todo   Rewrite to a custom find type
   */
  
  function findForCoPerson($coPersonId, $limit=null, $offset=null, $order=null, $owner=true) {
    $args = array();
    $args['joins'][0]['table'] = 'co_group_members';
    $args['joins'][0]['alias'] = 'CoGroupMember';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoGroup.id=CoGroupMember.co_group_id';
    $args['conditions']['CoGroup.status'] = StatusEnum::Active;
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    if($owner) {
      $args['conditions']['OR']['CoGroupMember.member'] = 1;
      $args['conditions']['OR']['CoGroupMember.owner'] = 1;
    } else {
      $args['conditions']['CoGroupMember.member'] = true;
    }
    $args['contain'] = false;
    
    if($limit) {
      $args['limit'] = $limit;
    }
    
    if($offset) {
      $args['offset'] = $offset;
    }
    
    if($order) {
      $args['order'] = $order;
    }
    
    return $this->find('all', $args);
  }
  
  /**
   * Obtain the set of members of a group, sorted by owner status and member name.
   *
   * @since  COmanage Registry v1.0.0
   * @param  Integer CO Group ID
   * @return Array Group member information, as returned by find
   */
  
  public function findSortedMembers($id) {
    $conditions = array();
    $conditions['CoGroupMember.co_group_id'] = $id;
    $contain = array();
    $contain['CoPerson'][] = 'PrimaryName';
    
    $args = array();
    $args['conditions'] = $conditions;
    $args['contain'] = $contain;
    
    // Because we're using containable behavior, we can't easily sort by PrimaryName
    // as part of the find. So instead we'll pull the records and sort using Hash.
    $results = $this->CoGroupMember->find('all', $args);
    
    // Before we sort we'll manually split out the members and owners for rendering.
    // We could order by (eg) owner in the find, but we'll still need to walk the results
    // to find the divide.
    $members = array();
    $owners = array();
    
    foreach($results as $r) {
      if(isset($r['CoGroupMember']['owner']) && $r['CoGroupMember']['owner']) {
        $owners[] = $r;
      } else {
        $members[] = $r;
      }
    }
    
    // Now sort each set of results by family name.
    
    $sortedMembers = Hash::sort($members, '{n}.CoPerson.PrimaryName.family', 'asc');
    $sortedOwners = Hash::sort($owners, '{n}.CoPerson.PrimaryName.family', 'asc');
    
    // Finally, combine the two arrays back and return
    
    return array_merge($sortedOwners, $sortedMembers);
  }
  
  /**
   * Determine if the group is an admin group for COU.
   * 
   * @since COmanage Registry v0.9.3
   * @param Array representing CoGroup
   * @return Boolean true if admin group
   */
  public function isCouAdminGroup($group) {
    // Right now we simply look at the name of the group.
    if (strncmp($group['CoGroup']['name'], 'admin:', 6) == 0) {
      return true;
    }
    return false;
  }
  
  /**
   * Determine if the group is an admin or members group for COU.
   * 
   * @since COmanage Registry v0.9.3
   * @param Array representing CoGroup
   * @return Boolean true if admin or members group
   */
  public function isCouAdminOrMembersGroup($group) {
      $admin = $this->isCouAdminGroup($group);
      $members = $this->isCouMembersGroup($group);
      return ($admin || $members);
  }

  /**
   * Determine if the group is the members group for CO.
   * 
   * @since COmanage Registry v0.9.4
   * @param Array representing CoGroup
   * @return Boolean true if members group
   */
  public function isCoMembersGroup($group) {
    // Right now we simply look at the name of the group.
    if ($group['CoGroup']['name'] == 'members') {
      return true;
    }
    return false;
  }
  
  /**
   * Determine if the group is a members group for COU.
   * 
   * @since COmanage Registry v0.9.3
   * @param Array representing CoGroup
   * @return Boolean true if members group
   */
  public function isCouMembersGroup($group) {
    // Right now we simply look at the name of the group.
    if (strncmp($group['CoGroup']['name'], 'members:', 8) == 0) {
      return true;
    }
    return false;
  }
  
  /**
   * Determine the current status of the provisioning targets for this CO Group.
   *
   * @since  COmanage Registry v0.8.2
   * @param  Integer CO Group ID
   * @return Array Current status of provisioning targets
   * @throws RuntimeException
   */
  
  public function provisioningStatus($coGroupId) {
    // First, obtain the list of active provisioning targets for this group's CO.
    
    $args = array();
    $args['joins'][0]['table'] = 'co_groups';
    $args['joins'][0]['alias'] = 'CoGroup';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoGroup.co_id=CoProvisioningTarget.co_id';
    $args['conditions']['CoGroup.id'] = $coGroupId;
    $args['conditions']['CoProvisioningTarget.status !='] = ProvisionerStatusEnum::Disabled;
    $args['contain'] = false;
    
    $targets = $this->Co->CoProvisioningTarget->find('all', $args);
    
    if(!empty($targets)) {
      // Next, for each target ask the relevant plugin for the status for this group.
      
      // We may end up querying the same Plugin more than once, so maintain a cache.
      $plugins = array();
      
      for($i = 0;$i < count($targets);$i++) {
        $pluginModelName = $targets[$i]['CoProvisioningTarget']['plugin']
                         . ".Co" . $targets[$i]['CoProvisioningTarget']['plugin'] . "Target";
        
        if(!isset($plugins[ $pluginModelName ])) {
          $plugins[ $pluginModelName ] = ClassRegistry::init($pluginModelName, true);
          
          if(!$plugins[ $pluginModelName ]) {
            throw new RuntimeException(_txt('er.plugin.fail', array($pluginModelName)));
          }
        }
        
        $targets[$i]['status'] = $plugins[ $pluginModelName ]->status($targets[$i]['CoProvisioningTarget']['id'],
                                                                      null,
                                                                      $coGroupId);
      }
    }
    
    return $targets;
  }
  
 /**
   * Determine if a CO Group is read only.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id CO Group ID
   * @return True if the CO Group is read only, false otherwise
   * @throws InvalidArgumentException
   */

  public function readOnly($id) {
    // A CO Group is read only if it is a members group.
    
    $name = $this->field('name', array('CoGroup.id' => $id));
    
    if(!$name) {
      throw new InvalidArgumentException(_txt('er.gr.nf', array($id)));
    }
    
    return !strncmp($name, 'members', 7);
  }
  
  /**
   * Reconcile CO Person memberships in a members group.
   * 
   * @since COmanage Registry 0.9.3
   * @param Integer CoGroup Id
   * @return true on success
   * @throws InvalidArgumentException
   */
  
  public function reconcileMembersGroup($id) {
    // First find the group
    $args = array();
    $args['conditions']['CoGroup.id'] = $id;
    $args['contain'] = false;
    
    $group = $this->find('first', $args);
      
    if(empty($group)) {
      throw new InvalidArgumentException(_txt('er.gr.nf', array($id)));
    }
    
    // Make sure the group is a members group.    
    $name = $group['CoGroup']['name'];
    
    if($name != 'members' && strncmp($name, 'members:', 8) != 0) {
      throw new InvalidArgumentException(_txt('er.gr.reconcile.inv'));
    }
    
    $cou = null;
    
    // Determine if this is a members group for a COU and if so
    // the ID for the COU.
    if($name != 'members') {
      $couName = substr($name, 8);
      
      $args = array();
      $args['conditions']['Cou.name'] = $couName;
      $args['contain'] = false;
      
      $cou = $this->Co->Cou->find('first', $args);
      
      if(!$cou) {
        throw new InvalidArgumentException(_txt('er.gr.nf', array($name)));
      }
    }
    
    // Determine the set of people who should be in the target group.
    // As of v1.1.0, we only want active people (with active roles in the COU, if specified).
    
    $args = array();
    $args['conditions']['CoPerson.status'] = array(StatusEnum::Active, StatusEnum::GracePeriod);
    $args['conditions']['CoPerson.co_id'] = $group['CoGroup']['co_id'];
    if($cou) {
      $args['joins'][0]['table'] = 'co_person_roles';
      $args['joins'][0]['alias'] = 'CoPersonRole';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoPersonRole.co_person_id';
      $args['conditions']['CoPersonRole.cou_id'] = $cou['Cou']['id'];
      $args['conditions']['CoPersonRole.status'] = array(StatusEnum::Active, StatusEnum::GracePeriod);
    }
    $args['contain'] = false;
    
    $coPeople = $this->Co->CoPerson->find('all', $args);
    
    // Determine the set of people currently in the target group
    $args = array();
    $args['conditions']['CoGroupMember.co_group_id'] = $id;
    $args['conditions']['CoGroupMember.co_group_id'] = $id;
    $args['fields'] = array('CoGroupMember.co_person_id', 'CoGroupMember.id' );
    $args['contain'] = false;
    
    $members = $this->Co->CoGroup->CoGroupMember->find('list', $args);
    
    // Make diff'able arrays
    $currentMembers = array_keys($members);
    $targetMembers = Hash::extract($coPeople, '{n}.CoPerson.id');
    
    // For any person in $currentMembers but not in $targetMembers, remove them from the group
    
    $toRemove = array_diff($currentMembers, $targetMembers);
    
    foreach($toRemove as $coPersonId) {
      $this->Co->CoPerson->CoGroupMember->delete($members[$coPersonId]);
    }
    
    // For any person in $targetMembers but not in $currentMembers, add them to the group
    
    $toAdd = array_diff($targetMembers, $currentMembers);
    
    foreach($toAdd as $coPersonId) {
      $data = array();
      $data['CoGroupMember']['co_group_id'] = $id;
      $data['CoGroupMember']['co_person_id'] = $coPersonId;
      $data['CoGroupMember']['member'] = true;
      $data['CoGroupMember']['owner'] = false;
      
      $this->Co->CoPerson->CoGroupMember->clear();
      $this->Co->CoPerson->CoGroupMember->save($data); 
    }
    
    return true;  
  }
  
  /**
   * Reconcile the existence of the CO and COU members groups
   * 
   * @since COmanage Registry 0.9.3
   * @param Integer CO Id
   * @return true for success or false for failure
   */
  
  public function reconcileMembersGroupsExistence($coId) {
    // Loop over groups looking for CO members group.
    
    $args = array();
    $args['conditions']['CoGroup.name'] = 'members';
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['contain'] = false;
    
    if($this->find('count', $args) == 0) {
      // Create the CO members group.
      $this->clear();
      $data = array();
      $data['CoGroup']['co_id'] = $coId;
      $data['CoGroup']['name'] = 'members';
      $data['CoGroup']['description'] = _txt('fd.group.desc.mem', array($co['Co']['name']));
      $data['CoGroup']['open'] = false;
      $data['CoGroup']['status'] = StatusEnum::Active;
      if(!$this->save($data)) {
        return false;
      }
    }
    
    // Loop over the COUs looking for COU members groups.
    $args = array();
    $args['conditions']['Cou.co_id'] = $coId;
    $args['fields'] = array('Cou.name', 'Cou.id');
    $args['contain'] = false;
    
    $cous = $this->Co->Cou->find('list', $args);
    
    foreach($cous as $couName => $couId) {
      $membersGroupName = 'members:' . $couName;
      
      $args = array();
      $args['conditions']['CoGroup.name'] = $membersGroupName;
      $args['conditions']['CoGroup.co_id'] = $coId;
      $args['contain'] = false;
      
      if($this->find('count', $args) == 0) {
        // Create the CO members group.
        $this->clear();
        $data = array();
        $data['CoGroup']['co_id'] = $coId;
        $data['CoGroup']['name'] = $membersGroupName;
        $data['CoGroup']['description'] = _txt('fd.group.desc.mem', array($couName));
        $data['CoGroup']['open'] = false;
        $data['CoGroup']['status'] = StatusEnum::Active;
        if(!$this->save($data)) {
          return false;
        }
      }
    }
    
    // Loop over groups looking for groups that match the
    // COU members groups structure but that don't have
    // matching COU.
    $args = array();
    $args['conditions']['CoGroup.name LIKE'] = 'members:%';
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['fields'] = array('CoGroup.name', 'CoGroup.id');
    $args['contain'] = false;
    
    $groups = $this->find('list', $args);
    
    foreach($groups as $group => $gid) {
      $cou = substr($group, 8);
      
      if(!isset($cous[$cou])) {
        // COU does not exist
        $this->delete($gid);
      }
    }
    
    return true;    
  }
}
