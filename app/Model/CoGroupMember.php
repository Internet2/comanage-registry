<?php
/**
 * COmanage Registry CO Group Member Model
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
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
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
    "CoGroupNesting",
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
    'co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true
      )
    ),
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
    'valid_from' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'valid_through' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'source_org_identity_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_group_nesting_id' => array(
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
   * Execute logic after model delete.
   *
   * @since  COmanage Registry v3.3.0
   */

  public function afterDelete() {
    // On save, we pull any nestings for this group and sync memberships for the
    // parent group(s). (We don't need to recurse since that should trigger a
    // CO Group Member update for that group, which will then call afterSave
    // again.)
    
    // Due to ChangelogBehavior these references should still be valid after delete.
    
    $group = $this->field('co_group_id');
    $person = $this->field('co_person_id');
    
    if($group && $person) {
      $args = array();
      $args['conditions']['CoGroupNesting.co_group_id'] = $group;
      $args['contain'][] = 'TargetCoGroup';
      
      $nestings = $this->CoGroupNesting->find('all', $args);
      
      if(!empty($nestings)) {
        foreach($nestings as $n) {
          $this->syncNestedMembership($n['TargetCoGroup'],
                                      $n['CoGroupNesting']['id'],
                                      $person,
                                      false);
        }
      }
    }
    
    return true;
  }
  
  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Boolean $created True if new model is saved (ie: add)
   * @param  Array $options Options, as based to model::save()
   * @return Boolean True on success
   */

  public function afterSave($created, $options = Array()) {
    if(isset($options['safeties']) && $options['safeties'] == 'off') {
      return true;
    }
    
    // On save, we pull any nestings for this group and sync memberships for the
    // parent group(s). (We don't need to recurse since that should trigger a
    // CO Group Member update for that group, which will then call afterSave
    // again.)
    
    if(!empty($this->data['CoGroupMember']['co_group_id'])
       && !empty($this->data['CoGroupMember']['co_person_id'])) {
      $args = array();
      $args['conditions']['CoGroupNesting.co_group_id'] = $this->data['CoGroupMember']['co_group_id'];
      $args['contain'][] = 'TargetCoGroup';
      
      $nestings = $this->CoGroupNesting->find('all', $args);
      
      if(!empty($nestings)) {
        foreach($nestings as $n) {
          $this->syncNestedMembership($n['TargetCoGroup'],
                                      $n['CoGroupNesting']['id'],
                                      $this->data['CoGroupMember']['co_person_id'],
                                      $this->data['CoGroupMember']['member']);
        }
      }
    }
    
    return true;
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.1.0
   */

  public function beforeSave($options = array()) {
    // Possibly convert the requested timestamps to UTC from browser time.
    // Do this before the strtotime/time calls below, both of which use UTC.

    if($this->tz) {
      $localTZ = new DateTimeZone($this->tz);

      if(!empty($this->data['CoGroupMember']['valid_from'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data['CoGroupMember']['valid_from'], $localTZ);

        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data['CoGroupMember']['valid_from'] = strftime("%F %T", $offsetDT->getTimestamp());
      }

      if(!empty($this->data['CoGroupMember']['valid_through'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data['CoGroupMember']['valid_through'], $localTZ);

        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data['CoGroupMember']['valid_through'] = strftime("%F %T", $offsetDT->getTimestamp());
      }
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
    // Only pull currently valid group memberships
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoGroupMember.valid_from IS NULL',
        'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
      )
    );
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoGroupMember.valid_through IS NULL',
        'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
      )
    );
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
    // Only pull currently valid group memberships
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoGroupMember.valid_from IS NULL',
        'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
      )
    );
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoGroupMember.valid_through IS NULL',
        'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
      )
    );
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
   * @param  Boolean Whether to only return valid (validfrom/through) entries
   * @return Array Group information, as returned by find
   * @todo   Rewrite to a custom find type
   */
  
  public function findForCoGroup($coGroupId, $limit=null, $offset=null, $order=null, $validOnly=false) {
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
    if($validOnly) {
      // Only pull currently valid group memberships
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_from IS NULL',
          'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
        )
      );
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'CoGroupMember.valid_through IS NULL',
          'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
        )
      );
    }
    $args['contain'] = false;
    
    return $this->findForUpdate($args['conditions'],
                                $args['fields'],
                                $args['joins'],
                                $limit,
                                $offset,
                                $order);
  }
  
  /**
   * Determine if the specified CO Person is an active, valid member of the
   * specified CO Group.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Integer $coGroupId  CO Group ID
   * @param  Integer $coPersonId CO Person ID
   * @return boolean             True if CO Person is a member of CO Group, false otherwse
   */
  
  public function isMember($coGroupId, $coPersonId) {
    $args = array();
    $args['conditions']['CoGroup.status'] = StatusEnum::Active;
    $args['conditions']['CoGroupMember.co_group_id'] = $coGroupId;
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoGroupMember.valid_from IS NULL',
        'CoGroupMember.valid_from < ' => date('Y-m-d H:i:s', time())
      )
    );
    $args['conditions']['AND'][] = array(
      'OR' => array(
        'CoGroupMember.valid_through IS NULL',
        'CoGroupMember.valid_through > ' => date('Y-m-d H:i:s', time())
      )
    );
    
    return (bool)$this->find('count', $args);
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
   * Reprovision records associated with CoGroupMembers where the validity dates
   * have recently become effective.
   *
   * @since  COmanage Registry 3.2.0
   * @param  Integer $coId   CO ID
   * @param  Integer $window Time in minutes to look back for changes (ie: within the last $window minutes)
   */
  
  public function reprovisionByValidity($coId, $window=DEF_GROUP_SYNC_WINDOW) {
    // Pull all group memberships with valid_from or valid_through timestamps
    // in the last $window minutes
    
    $timeend = time();
    $timestart = $timeend - ($window * 60);
    
    $args = array();
    /* JOIN not needed due to contain
    $args['joins'][0]['table'] = 'co_groups';
    $args['joins'][0]['alias'] = 'CoGroup';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoGroup.id=CoGroupMember.co_group_id';
    */
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['conditions']['OR'][] = array(
      // Membership just became active
      'AND' => array(
        'CoGroupMember.valid_from >= ' => date('Y-m-d H:i:s', $timestart),
        'CoGroupMember.valid_from <= ' => date('Y-m-d H:i:s', $timeend)
      )
    );
    $args['conditions']['OR'][] = array(
      // Membership just became inactive
      'AND' => array(
        'CoGroupMember.valid_through >= ' => date('Y-m-d H:i:s', $timestart),
        'CoGroupMember.valid_through <= ' => date('Y-m-d H:i:s', $timeend)
      )
    );
    $args['contain'] = array('CoGroup');
    
    $memberships = $this->find('all', $args);
    
    // Register a new CoJob. This will throw an exception if a job is already in progress.
    $jobId = $this->CoPerson->Co->CoJob->register($coId,
                                                  JobTypeEnum::GroupValidity,
                                                  null,
                                                  "",
                                                  _txt('fd.co_group_member.sync.count',
                                                       array(count($memberships))));
    
    // Flag the Job as started
    $cnt = 0;
    $this->CoPerson->Co->CoJob->start($jobId);
    
    foreach($memberships as $grm) {
      try {
        $this->manualProvision(null, 
                               null,
                               null,
                               ProvisioningActionEnum::CoPersonReprovisionRequested,
                               null,
                               $grm['CoGroupMember']['id']);
        
        $cmt =  _txt('rs.grm.prov.validity', array($grm['CoGroup']['name'],
                                                   $grm['CoGroupMember']['co_group_id']));
        
        $this->CoPerson->HistoryRecord->record($grm['CoGroupMember']['co_person_id'],
                                               null,
                                               null,
                                               null,
                                               ActionEnum::CoGroupMemberValidityTriggered,
                                               $cmt,
                                               $grm['CoGroupMember']['co_group_id']);
        
        $this->CoPerson->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                               $grm['CoGroupMember']['id'],
                                                               $cmt,
                                                               $grm['CoGroupMember']['co_person_id'],
                                                               null,
                                                               JobStatusEnum::Complete);
        
        $cnt++;
      }
      catch(Exception $e) {
        $this->CoPerson->Co->CoJob->CoJobHistoryRecord->record($jobId,
                                                               $grm['CoGroupMember']['id'],
                                                               $e->getMessage(),
                                                               $grm['CoGroupMember']['co_person_id'],
                                                               null,
                                                               JobStatusEnum::Failed);
      }
    }
    
    $this->CoPerson->Co->CoJob->finish($jobId, _txt('fd.co_group_member.sync.count.done', array($cnt)));
  }
  
  /**
   * Set a group membership for a CO Person.
   *
   * @since  COmanage Registry v3.1.0
   * @param  RoleComponent $Role      RoleComponent
   * @param  Integer $coGroupId       CO Group ID
   * @param  Integer $coPersonId      CO Person ID to set membership for
   * @param  Boolean $member          Whether Person is a member of the Group
   * @param  Boolean $owner           Whether Person is an owner of the Group
   * @param  Integer $actorCoPersonId CO Person ID of requestor
   * @param  Boolean $provision       Whether to fire provisioners on save
   * @return Boolean True on success
   * @throws InvalidArgumentException
   * @throws RuntimeException
   * @todo   This should become the core function used by all other calls, except maybe syncMembership.
   */
  
  public function setMembership($Role, $coGroupId, $coPersonId, $member, $owner, $actorCoPersonId, $provision=true) {
    // First pull the group info
    
    $args = array();
    $args['conditions']['CoGroup.id'] = $coGroupId;
    $args['contain'] = false;
    
    $group = $this->CoGroup->find('first', $args);
    
    if(!$group) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_groups.1'), $coGroupId)));
    }
    
    // Reject updates to auto groups
    
    if($group['CoGroup']['auto']) {
      throw new InvalidArgumentException(_txt('er.gr.auto.edit'));
    }
    
    // Or to closed groups where $actorCoPersonId is not an owner or admin
    
    if(!$group['CoGroup']['open']
       && !$Role->isGroupManager($coPersonId, $coGroupId)) {
      throw new RuntimeException(_txt('er.permission'));
    }
    
    // See if there is already a row for this group+person, if so update it
    
    $args = array();
    $args['conditions']['CoGroupMember.co_group_id'] = $coGroupId;
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['contain'] = false;
    
    $grmem = $this->find('first', $args);

    // Store the membership
    // Contrary to former practice, we save empty rows (is this a problem for existing code?)
    
    $hAction = null;
    $hText = "";
    
    $this->clear();
    $data = array();
    $data['CoGroupMember']['co_group_id'] = $coGroupId;
    $data['CoGroupMember']['co_person_id'] = $coPersonId;
    $data['CoGroupMember']['member'] = $member;
    $data['CoGroupMember']['owner'] = $owner;
    if(!empty($grmem['CoGroupMember']['id'])) {
      $data['CoGroupMember']['id'] = $grmem['CoGroupMember']['id'];
      
      $hAction = ActionEnum::CoGroupMemberEdited;
      $hText = _txt('rs.grm.edited', array($group['CoGroup']['name'],
                                          $coGroupId,
                                          ($grmem['CoGroupMember']['member'] ? _txt('fd.yes') : _txt('fd.no')),
                                          ($grmem['CoGroupMember']['owner'] ? _txt('fd.yes') : _txt('fd.no')),
                                          ($member ? _txt('fd.yes') : _txt('fd.no')),
                                          ($owner ? _txt('fd.yes') : _txt('fd.no'))));
    } else {
      $hAction = ActionEnum::CoGroupMemberAdded;
      $hText = _txt('rs.grm.added', array($group['CoGroup']['name'],
                                          $coGroupId,
                                          ($member ? _txt('fd.yes') : _txt('fd.no')),
                                          ($owner ? _txt('fd.yes') : _txt('fd.no'))));
    }
    
    $options = array();
    if(!$provision) {
      $options['provision'] = false;
    }
    
    $this->save($data, $options);
    
    // Create a history record
      
    $this->CoPerson->HistoryRecord->record($coPersonId,
                                           null,
                                           null,
                                           $actorCoPersonId,
                                           $hAction,
                                           $hText,
                                           $coGroupId);

    return true;
  }
  
  /**
   * Sync a group membership based. This function is primarily intended for
   * syncing automatically managed groups (eg: "members").
   *
   * @since  COmanage Registry v2.0.0
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
   * Sync a group membership based on a nested membership.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array     $targetGroup      Array describing CO Group to add the membership to
   * @param  Array     $coGroupNestingId CO Group Nesting ID
   * @param  Integer   $coPersonId       CO Person ID of member
   * @param  Boolean   $sourceMember     Whether the CO Person is a member of the source CoGroup
   */
  
  public function syncNestedMembership($targetGroup, $coGroupNestingId, $coPersonId, $sourceMember) {
    // The operation we perform (add or delete) may be inverted by the CoGroupNesting
    // configuration.
    
    // Our pseudologic for what to do here is as follows:
    //  t = isMemberOf($targetGroup)
    //  t' = shouldBeMemberOf($targetGroup)
    //  
    //  if(t && !t') addTo($targetGroup)
    //  elseif(!t && t') removeFrom($targetGroup)
    
    // $coPersonId should be a member of $targetGroup if any of the following are true
    // (1) Nesting/Negate = false 
    //     AND TargetGroup/Mode = any
    //     AND $sourceMember
    //     AND not a member of any source group for target where Nesting/Negate = true
    // (2) Nesting/Negate = false
    //     AND TargetGroup/Mode = all
    //     AND member of all source groups for target
    //     AND not a member of any source group for target where Nesting/Negate = true
    // (3) Nesting/Negate = true
    //     AND TargetGroup/Mode = any
    //     AND !$sourceMember
    //     AND member of any non-negated source group for target
    //     AND not a member of any source group for target where Nesting/Negate = true
    // (4) Nesting/Negate = true
    //     AND TargetGroup/Mode = all
    //     AND !$sourceMember
    //     AND member of all non-negated source groups for target
    //     AND not a member of any source group for target where Nesting/Negate = true
    
    $shouldBe = false;      // Should $coPerson be a member of $targetGroup?
    $all = false;           // Is $targetGroup configured for nesting all mode?
    $negated = false;       // $coPersonId is ineligible for $targetGroup due to any negative membership
    $isAny = false;         // $coPersonId is a member of any (positive) source group for $targetGroup
    $isAll = false;         // $coPersonId is a member of all (positive) source groups for $targetGroup
    $isCurrent = false;     // $coPersonId is a member of $targetGroup
    
    // Figure out our configuration
    
    $all = $this->CoGroup->field('nesting_mode_all', array('CoGroup.id' => $targetGroup['id']));
    
    // All nestings for $targetGroup
    $args = array();
    $args['conditions']['CoGroupNesting.target_co_group_id'] = $targetGroup['id'];
    $args['contain'] = false;
    
    // This should always have at least the current nesting
    $nestings = $this->CoGroupNesting->find('all', $args);
    
    // Walk all nestings to determine negation and current memberships. To track
    // $isAll, we need at least one positive membership. In other words, a Target
    // Group with only one Nesting, and that one Nesting is negative, does not
    // automatically make everybody else a member.
    $pAvail = 0;    // Available positive memberships
    $pCount = 0;    // Actual positive memberships
    
    foreach($nestings as $n) {
      if($n['CoGroupNesting']['negate']) {
        // If this is the current nesting we don't need to look anything up
        if((($n['CoGroupNesting']['id'] == $coGroupNestingId) && $sourceMember)
           ||
           $this->isMember($n['CoGroupNesting']['co_group_id'], $coPersonId)) {
          $negated = true;
        }
      } else {
        $pAvail++;
        
        if((($n['CoGroupNesting']['id'] == $coGroupNestingId) && $sourceMember)
           ||
           $this->isMember($n['CoGroupNesting']['co_group_id'], $coPersonId)) {
          $isAny = true;
          $pCount++;
        }
      }
    }
    
    // We need at least one positive group to count as ALL
    $isAll = ($pAvail > 0 && $pCount == $pAvail);
    
    if(!$negated && !$all && $isAny) {
      // Case (1) and (3)
      $shouldBe = true;
    } elseif(!$negated && $all && $isAll) {
      // Case (2) and (4)
      $shouldBe = true;
    }
    
    $isCurrent = $this->isMember($targetGroup['id'], $coPersonId);
    
    $htxtkey = '';
    $hAction = null;
    
    if(!$isCurrent && $shouldBe) {
      // Add a CoGroupMember record associated with this Nesting
      $this->clear();
      
      $data = array();
      $data['CoGroupMember']['co_group_id'] = $targetGroup['id'];
      $data['CoGroupMember']['co_person_id'] = $coPersonId;
      $data['CoGroupMember']['member'] = true;
      $data['CoGroupMember']['owner'] = false;
      $data['CoGroupMember']['co_group_nesting_id'] = $coGroupNestingId;
      
      $this->save($data);
      
      $htxtkey = 'rs.grm.added-n';
      $hAction = ActionEnum::CoGroupMemberAdded;
    } elseif($isCurrent && !$shouldBe) {
      // We delete all CoGroupMembers associated with any Nesting
      $conditions = array(
        'CoGroupMember.co_person_id' => $coPersonId,
        'CoGroupMember.co_group_id' => $targetGroup['id'],
        'CoGroupMember.co_group_nesting_id IS NOT NULL',
        // For updateAll, we need to manually inject changelog
        'CoGroupMember.deleted' => false,
        'CoGroupMember.co_group_member_id' => null
      );
      
      $this->deleteAll($conditions, true, true);
      
      $htxtkey = 'rs.grm.deleted-n';
      $hAction = ActionEnum::CoGroupMemberDeleted;
    }

    if($hAction) {
      // Pull the nested group name
      $args = array();
      $args['CoGroupNesting.id'] = $coGroupNestingId;
      $args['contain'] = array('CoGroup');

      $coGroupNesting = $this->CoGroup->CoGroupNesting->find('first', $args);
      
      $hText = _txt($htxtkey, array($targetGroup['name'],
                                    $targetGroup['id'],
                                    !empty($coGroupNesting['CoGroup']['name']) ? $coGroupNesting['CoGroup']['name'] : "(?)",
                                    !empty($coGroupNesting['CoGroup']['id']) ? $coGroupNesting['CoGroup']['id'] : "(?)"));
      
      // Cut a history record
      $this->CoPerson->HistoryRecord->record($coPersonId,
                                             null,
                                             null,
                                             null,
                                             $hAction,
                                             $hText,
                                             $targetGroup['id']);
    }
  }
  
  /**
   * Update the CO Group Memberships for a CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID
   * @param  Array Array of CO Group Member attributes (id, co_group_id, member, owner)
   * @param  Integer CO Person ID of requester
   * @param  Boolean True if $requesterCoPersonId is a CO admin
   * @return Boolean True on success, false otherwise
   * @throws LogicException
   */
  
  public function updateMemberships($coPersonId,
                                    $memberships,
                                    $requesterCoPersonId,
                                    $requesterIsAdmin=false) {
    if($coPersonId && !empty($memberships)) {
      // First, pull the current group roles.
      $curRoles = $this->findCoPersonGroupRoles($coPersonId);
      
      // And also the roles of $requesterCoPersonId, in case we need to check ownership
      $requesterRoles = $this->findCoPersonGroupRoles($requesterCoPersonId);
      
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
        
        if(empty($grp)) {
          throw new InvalidArgumentException(_txt('er.gr.nf', array($m['co_group_id'])));
        }
        
        // If this is an automatic group skip it
        if(isset($grp['CoGroup']['auto']) && $grp['CoGroup']['auto']) {
          continue;
        }
        
        // If this is a closed group and $requesterCoPersonId is not an owner or admin, skip it
        if(!$requesterIsAdmin
           && (!isset($grp['CoGroup']['open']) || !$grp['CoGroup']['open'])
           && !in_array($m['co_group_id'], $requesterRoles['owner'])) {
          continue;
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
