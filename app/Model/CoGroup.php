<?php
/**
 * COmanage Registry CO Group Model
 *
 * Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
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
      'rule' => 'notBlank',
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'description' => array(
      'rule' => '/.*/',
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
   * @return Array Group information, as returned by find
   * @todo   Rewrite to a custom find type
   */
  
  function findForCoPerson($coPersonId, $limit=null, $offset=null, $order=null) {
    $args = array();
    $args['joins'][0]['table'] = 'co_group_members';
    $args['joins'][0]['alias'] = 'CoGroupMember';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoGroup.id=CoGroupMember.co_group_id';
    $args['conditions']['CoGroup.status'] = StatusEnum::Active;
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['conditions']['OR']['CoGroupMember.member'] = 1;
    $args['conditions']['OR']['CoGroupMember.owner'] = 1;
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
   * Reconcile CO Person memberships in a members group.
   * 
   * @since COmanage Registry 0.9.3
   * @param Integer CoGroup Id
   * @return true if success or false for failure
   */
  
  public function reconcileMembersGroup($id) {
    $args = array();
    $args['conditions']['CoGroup.id'] = $id;
    $args['contain']['Co'] = 'Cou';
    $group = $this->find('first', $args);
      
    if(empty($group)) {
      return false;
    }
    
    // Make sure the group is a members group.    
    $name = $group['CoGroup']['name'];
    if($name != 'members' && strncmp($name, 'members:', 8) != 0) {
      return false;
    }
    
    $coId = $group['CoGroup']['co_id'];
    
    // Determine if this is a members group for a COU and if so
    // the ID for the COU.
    $couId = null;
    if($name != 'members') {
        foreach($group['Co']['Cou'] as $cou) {
          if($name == 'members:' . $cou['name']) {
            $couId = $cou['id'];
          }      
        }                    
    }
    
    // Find all CO people for the CO.
    $args = array();
    $args['conditions']['CoPerson.co_id'] = $group['CoGroup']['co_id'];
    $args['contain'][] = 'CoGroupMember';
    $args['contain'][] = 'CoPersonRole';
    $coPeople = $this->Co->CoPerson->find('all', $args);
    
    // Loop over the CO people and determine if any are not
    // members of the group that should be members.
    foreach($coPeople as $coPerson) {
      $coPersonId = $coPerson['CoPerson']['id'];
      if(isset($couId)) {
        // Check for role in the COU.
        foreach($coPerson['CoPersonRole'] as $role) {
          if($role['cou_id'] == $couId) {
            // Since have role in the COU should be in the COU members group.
            $isMember = false;
            foreach($coPerson['CoGroupMember'] as $membership) {
              if($membership['co_group_id'] == $id) {
                $isMember = true;
                break;
              }
            } 
            if(!$isMember) {
              $data = array();
              $data['CoGroupMember']['co_group_id'] = $id;
              $data['CoGroupMember']['co_person_id'] = $coPerson['CoPerson']['id'];
              $data['CoGroupMember']['member'] = true;
              $this->Co->CoPerson->CoGroupMember->clear();
              $success = $this->Co->CoPerson->CoGroupMember->save($data);              
              if(!$success) {
                $this->log("Error saving membership for CoPerson.id $coPersonId in CoGroup.id $id");
                return false;
              }
            }
          } 
        }
      } else {
        // Check for membership in the CO members group.        
        $isMember = false;
        foreach($coPerson['CoGroupMember'] as $membership) {
          if($membership['co_group_id'] == $id) {
            $isMember = true;
            break;
          } 
        }
        if(!$isMember) {
          $data = array();
          $data['CoGroupMember']['co_group_id'] = $id;
          $data['CoGroupMember']['co_person_id'] = $coPerson['CoPerson']['id'];
          $data['CoGroupMember']['member'] = true;
          $this->Co->CoPerson->CoGroupMember->clear();
          $success = $this->Co->CoPerson->CoGroupMember->save($data);              
          if(!$success) {
            $this->log("Error saving membership for CoPerson.id $coPersonId in CoGroup.id $id");
            return false;
          }
        }
      }
    }
    
    // Find all memberships for the group.
    $args = array();
    $args['conditions']['CoGroupMember.co_group_id'] = $id;
    $args['contain']['CoPerson'] = 'CoPersonRole';
    $memberships = $this->Co->CoPerson->CoGroupMember->find('all', $args);
    
    // Loop over the memberships to find any that should not exist for this group.
    foreach($memberships as $membership) {
      if(isset($couId)) {
        // This is a COU members group so check for role in the COU.
        $delete = true;
        foreach($membership['CoPerson']['CoPersonRole'] as $role) {
          if($role['cou_id'] == $couId) {
            $delete = false;
            break;
          }
        }
        if($delete) {
          $success = $this->Co->CoPerson->CoGroupMember->delete($membership['CoGroupMember']['id']);
          if(!$success) {
            $this->log("Error deleting CoGroupMember.id " . $membership['CoGroupMember']['id']);
            return false;
          }
        }
      } else {
        // This is a CO members group so check person in the CO.
        if($membership['CoPerson']['co_id'] != $coId) {
          $success = $this->Co->CoPerson->CoGroupMember->delete($membership['CoGroupMember']['id']);
          if(!$success) {
            $this->log("Error deleting CoGroupMember.id " . $membership['CoGroupMember']['id']);
            return false;
          }
        }
      }
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
    // Find the CO, COUs, and groups.
    $args = array();
    $args['conditions']['Co.id'] = $coId;
    $args['contain'][] = 'Cou';
    $args['contain'][] = 'CoGroup';
    $co = $this->Co->find('first', $args);
    
    // Loop over groups looking for CO members group.
    $membersGroupExists = false;
    foreach($co['CoGroup'] as $group) {
      if($group['name'] == 'members') {
        $membersGroupExists = true;
        break;
      } 
    }
    
    if(!$membersGroupExists) {
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
    foreach($co['Cou'] as $cou) {
      $membersGroupName = 'members:' . $cou['name'];
      $membersGroupExists = false;
      foreach($co['CoGroup'] as $group) {
        if($group['name'] == $membersGroupName) {
          $membersGroupExists = true;
          break;
        } 
      }
      if(!$membersGroupExists) {
        // Create the CO members group.
        $this->clear();
        $data = array();
        $data['CoGroup']['co_id'] = $coId;
        $data['CoGroup']['name'] = $membersGroupName;
        $data['CoGroup']['description'] = _txt('fd.group.desc.mem', array($cou['name']));
        $data['CoGroup']['open'] = false;
        $data['CoGroup']['status'] = StatusEnum::Active;
        if(!$this->save($data)) {
          $couId = $cou['id'];
          return false;
        }
      }
    }
    
    // Loop over groups looking for groups that match the
    // COU members groups structure but that don't have
    // matching COU.
    foreach($co['CoGroup'] as $group) {
      if(strncmp($group['name'], 'members:', 8) == 0) {
        $couExists = false;
        foreach($co['Cou'] as $cou) {
          $nameFromCou = 'members:' . $cou['name'];
          if($group['name'] == $nameFromCou) {
            $couExists = true;
            break;
          }
        }
        if(!$couExists) {
          $this->delete($group['id']);
        }
      } 
    }
    
    return true;    
  }
}
