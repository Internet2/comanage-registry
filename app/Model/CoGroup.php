<?php
/**
 * COmanage Registry CO Group Model
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
  
class CoGroup extends AppModel {
  // Define class name for cake
  public $name = "CoGroup";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $hasMany = array(
    // A CoGroup has zero or more members
    "CoGroupMember" => array('dependent' => true),
    "CoGroupNesting" => array(
      'dependent' => true,
      'foreignKey' => 'target_co_group_id'
    ),
    "SourceCoGroupNesting" => array(
      'dependent' => true,
      'className' => 'CoGroupNesting',
      'foreignKey' => 'co_group_id'
    ),
    "CoDashboardVisibilityCoGroup" => array(
      'className' => 'CoDashboard',
      'foreignKey' => 'visibility_co_group_id'
    ),
    "CoDepartmentAdministrativeCoGroup" => array(
      'className' => 'CoDepartment',
      'foreignKey' => 'administrative_co_group_id'
    ),
    "CoDepartmentLeadershipCoGroup" => array(
      'className' => 'CoDepartment',
      'foreignKey' => 'leadership_co_group_id'
    ),
    "CoDepartmentSupportCoGroup" => array(
      'className' => 'CoDepartment',
      'foreignKey' => 'support_co_group_id'
    ),
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
    "EmailListAdmin" => array(
      'className' => 'CoEmailList',
      'foreignKey' => 'admins_co_group_id'
    ),
    "EmailListMember" => array(
      'className' => 'CoEmailList',
      'foreignKey' => 'members_co_group_id'
    ),
    "EmailListModerator" => array(
      'className' => 'CoEmailList',
      'foreignKey' => 'moderators_co_group_id'
    ),
    "HistoryRecord",
    "Identifier" => array('dependent' => true),
    "VetterCoGroup" => array(
      'className' => 'VettingStep',
      'foreignKey' => 'vetter_co_group_id'
    )
  );

  public $belongsTo = array(
    "Co",
    "Cou"
  );
   
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
    ),
    'group_type' => array(
      'rule' => array('inList', array(GroupEnum::Standard,
                                      GroupEnum::ActiveMembers,
                                      GroupEnum::Admins,
                                      GroupEnum::AllMembers)),
      'required' => false,
      'allowEmpty' => true
    ),
    'auto' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'cou_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'nesting_mode_all' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'SuspendableStatusEnum'
  );
  
  /**
   * Perform CoGroup model upgrade steps for version 2.0.0.
   * This function should only be called by UpgradeVersionShell.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $coId    CO ID
   * @param  String  $coName  CO Name
   * @param  Integer $couId   COU ID
   * @param  String  $couName COU Name
   */

  public function _ug110($coId, $coName, $couId=null, $couName=null) {
    // Unlike most _ug functions, this one is intended to be called by
    // UpgradeVersionShell multiple times, to allow for progress reporting.
    // There is a bit of duplication here and in addDefaults, but since this
    // function is not needed in the long term (and so doesn't need updating) that's OK.
    // v2.0.0 was previously v1.1.0.
    
    // We'll check for the existence of each group before creating it, just in case
    
    $ckArgs = array();
    $ckArgs['conditions']['CoGroup.co_id'] = $coId;
    $ckArgs['conditions']['CoGroup.cou_id'] = $couId;
    $ckArgs['contain'] = false;
    
    // First find and rename the admin group and add the new metadata.
    
    $ckArgs['conditions']['CoGroup.group_type'] = GroupEnum::Admins;
    
    if(!$this->find('count', $ckArgs)) {
      $args = array();
      $args['conditions']['CoGroup.name'] = 'admin' . ($couName ? ":" . $couName : "");
      $args['conditions']['CoGroup.co_id'] = $coId;
      $args['contain'] = false;
      
      $group = $this->find('first', $args);
      
      if($group) {
        $data = array(
          'CoGroup' => array(
            'id'          => $group['CoGroup']['id'],
            'name'        => "CO" . ($couName ? ":COU:".$couName : "") . ":admins",
            'group_type'  => GroupEnum::Admins,
            'auto'        => false,
            'description' => _txt('fd.group.desc.adm', array($couName ?: $coName)),
            'open'        => false,
            'status'      => SuspendableStatusEnum::Active,
            'co_id'       => $coId,
            'cou_id'      => ($couId ?: null)
          )
        );
        
        $this->clear();
        
        if(!$this->save($data)) {
          throw new RuntimeException(_txt('er.db.save-a', array('CoGroup::_ug101')));
        }
        
        // Admin groups are not automatically managed, so do not reconcile
      }
    }
    
    // Next, convert the "members" group to All Members (matching pre-2.0.0 behavior).
    
    $ckArgs['conditions']['CoGroup.group_type'] = GroupEnum::AllMembers;
    
    if(!$this->find('count', $ckArgs)) {
      $args = array();
      $args['conditions']['CoGroup.name'] = 'members' . ($couName ? ":" . $couName : "");
      $args['conditions']['CoGroup.co_id'] = $coId;
      $args['contain'] = false;
      
      $group = $this->find('first', $args);
      
      if($group) {
        $data = array(
          'CoGroup' => array(
            'id'          => $group['CoGroup']['id'],
            'name'        => "CO" . ($couName ? ":COU:".$couName : "") . ":members:all",
            'group_type'  => GroupEnum::AllMembers,
            'auto'        => true,
            'description' => _txt('fd.group.desc.mem', array($couName ?: $coName)),
            'open'        => false,
            'status'      => SuspendableStatusEnum::Active,
            'co_id'       => $coId,
            'cou_id'      => ($couId ?: null)
          )
        );
        
        $this->clear();
        
        if(!$this->save($data)) {
          throw new RuntimeException(_txt('er.db.save-a', array('CoGroup::_ug101')));
        }
        
        $this->reconcileAutomaticGroup($this->id);
      }
    }
    
    // Finally, create a new Active Members group.
    
    $ckArgs['conditions']['CoGroup.group_type'] = GroupEnum::ActiveMembers;
    
    if(!$this->find('count', $ckArgs)) {
      $data = array(
        'CoGroup' => array(
          'name'        => "CO" . ($couName ? ":COU:".$couName : "") . ":members:active",
          'group_type'  => GroupEnum::ActiveMembers,
          'auto'        => true,
          'description' => _txt('fd.group.desc.mem.act', array($couName ?: $coName)),
          'open'        => false,
          'status'      => SuspendableStatusEnum::Active,
          'co_id'       => $coId,
          'cou_id'      => ($couId ?: null)
        )
      );
        
      $this->clear();
      
      if(!$this->save($data)) {
        throw new RuntimeException(_txt('er.db.save-a', array('CoGroup::_ug101')));
      }
        
      $this->reconcileAutomaticGroup($this->id);
    }
  }
  
  /**
   * Add all default groups for the specified CO.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $coId   CO ID
   * @param  Integer $couId  COU ID
   * @param  Boolean $rename If true, rename any existing groups
   * @return Boolean True on success
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function addDefaults($coId, $couId=null, $rename=false) {
    // Pull the name of the CO/COU
    
    $coName = $this->Co->field('name', array('Co.id' => $coId));
    
    if(!$coName) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.cos.1'), $coId)));
    }
    
    $couName = null;
    
    if($couId) {
      // We rely on COU name validation not permitting a COU Name with a colon.
      $couName = $this->Cou->field('name', array('Cou.id' => $couId));
      
      if(!$couName) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.cous.1'), $couId)));
      }
    }
    
    // The names get prefixed "CO" or "CO:COU:<couname>", as appropriate
    
    $defaultGroups = array(
      ':admins' => array(
        'group_type'  => GroupEnum::Admins,
        'auto'        => false,
        'description' => _txt('fd.group.desc.adm', array($couName ?: $coName)),
        'open'        => false,
        'status'      => SuspendableStatusEnum::Active,
        'cou_id'      => ($couId ?: null)
      ),
      ':members:active' => array(
        'group_type'  => GroupEnum::ActiveMembers,
        'auto'        => true,
        'description' => _txt('fd.group.desc.mem.act', array($couName ?: $coName)),
        'open'        => false,
        'status'      => SuspendableStatusEnum::Active,
        'cou_id'      => ($couId ?: null)
      ),
      ':members:all' => array(
        'group_type'  => GroupEnum::AllMembers,
        'auto'        => true,
        'description' => _txt('fd.group.desc.mem', array($couName ?: $coName)),
        'open'        => false,
        'status'      => SuspendableStatusEnum::Active,
        'cou_id'      => ($couId ?: null)
      ),
    );
    
    foreach($defaultGroups as $suffix => $attrs) {
      // Construct the full group name
      $gname = "CO" . ($couName ? ":COU:".$couName : "") . $suffix;
      
      // See if there is already a group with this type for this CO
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $coId;
      $args['conditions']['CoGroup.group_type'] = $attrs['group_type'];
      $args['conditions']['CoGroup.cou_id'] = $couId ?: null;
      
      $grp = $this->find('first', $args);
      
      if(!$grp || $rename) {
        // Proceed with the save
        
        $this->clear();
        
        $data = array();
        $data['CoGroup'] = $attrs;
        $data['CoGroup']['co_id'] = $coId;
        $data['CoGroup']['name'] = $gname;
        
        if(!empty($grp['CoGroup']['id'])) {
          // Insert the key of the existing record.
          // All we really need to update is the name and description,
          // but then we have to deal with required fields and save vs update.
          // So for now we reset the metadata if it happened to have been changed.
          
          $data['CoGroup']['id'] = $grp['CoGroup']['id'];
        }
        
        if(!$this->save($data)) {
          throw new RuntimeException(_txt('er.db.save-a', array('CoGroup::addDefaults')));
        }
      }
    }

    return true;
  }
  
  /**
   * Obtain the ID of the CO or COU admin group.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer $coId    CO ID
   * @param  String  $couId   COU ID, within $coId
   * @return Integer CO Group ID
   * @throws InvalidArgumentException
   */
  
  public function adminCoGroupId($coId, $couId=null) {
    $args = array();
    $args['conditions']['CoGroup.co_id'] = $coId;
    // For the CO Admin group, $couId must be null
    $args['conditions']['CoGroup.cou_id'] = $couId;
    $args['conditions']['CoGroup.group_type'] = GroupEnum::Admins;
    $args['conditions']['CoGroup.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;
    
    $coAdminGroup = $this->Co->CoGroup->find('first', $args);

    if(!empty($coAdminGroup['CoGroup']['id'])) {
      return $coAdminGroup['CoGroup']['id'];
    }
    
    throw new InvalidArgumentException(_txt('er.gr.nf', array('admins')));
  }
  
  /**
   * Actions to take after a save operation is executed.
   *
   * @since  COmanage Registry v3.3.0
   * @param  boolean $created True if a new record was created (rather than update)
   * @param  array   $options As passed into Model::save()
   */

  public function afterSave($created, $options = array()) {
    // Maybe assign identifiers, but only for new Groups
    if($created 
       && !empty($this->data['CoGroup']['id'])
       && isset($this->data['CoGroup']['auto'])   // CO-1829
       && !$this->data['CoGroup']['auto']) {
      $this->Identifier->assign('CoGroup', $this->data['CoGroup']['id'], null);
    }

    return true;
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v2.0.0
   */

  public function beforeSave($options = array()) {
    if(!empty($this->data['CoGroup']['id'])) {
      // On edit, we don't want to allow certain metadata to be changed.
      
      $args = array();
      $args['conditions']['CoGroup.id'] = $this->data['CoGroup']['id'];
      $args['contain'] = false;
  
      $curdata = $this->find('first', $args);
      
      if(!empty($curdata)) {
        // We don't allow group_type or auto to be changed
        
        if(!empty($curdata['CoGroup']['auto'])) {
          $this->data['CoGroup']['auto'] = $curdata['CoGroup']['auto'];
        }
        
        if(!empty($curdata['CoGroup']['group_type'])) {
          $this->data['CoGroup']['group_type'] = $curdata['CoGroup']['group_type'];
        }
      }
    }
    
    return true;
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
   * Determine if the group is an admin group for COU.
   * 
   * @since COmanage Registry v0.9.3
   * @param Array representing CoGroup
   * @return Boolean true if admin group
   */
  
  public function isCouAdminGroup($group) {
    return ($group['CoGroup']['group_type'] == GroupEnum::Admins
            && !empty($group['CoGroup']['cou_id']));
  }
  
  /**
   * Determine if the group is an admin or members group for a COU.
   * 
   * @since COmanage Registry v0.9.3
   * @param Array representing CoGroup
   * @return Boolean true if admin or members group
   */
  
  public function isCouAdminOrMembersGroup($group) {
    $ret = $this->isCouAdminGroup($group)
           || $this->isCouMembersGroup($group);
    
    return $ret;
  }

  /**
   * Determine if the group is a members group (all or active) for a CO.
   * 
   * @since COmanage Registry v0.9.4
   * @param Array representing CoGroup
   * @return Boolean true if members group
   */
  
  public function isCoMembersGroup($group) {
    return (($group['CoGroup']['group_type'] == GroupEnum::ActiveMembers
             || $group['CoGroup']['group_type'] == GroupEnum::AllMembers)
            && empty($group['CoGroup']['cou_id']));
  }
  
  /**
   * Determine if the group is a members group (all or active) for a COU.
   * 
   * @since COmanage Registry v0.9.3
   * @param Array representing CoGroup
   * @return Boolean true if members group
   */
  
  public function isCouMembersGroup($group) {
    return (($group['CoGroup']['group_type'] == GroupEnum::ActiveMembers
             || $group['CoGroup']['group_type'] == GroupEnum::AllMembers)
            && !empty($group['CoGroup']['cou_id']));
  }
  
 /**
   * Determine if a CO Group is read only.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id CO Group ID
   * @return True if the CO Group is read only, false otherwise
   * @throws InvalidArgumentException
   */

  public function readOnly($id) {
    // A CO Group is read only if the auto field is true.

    // XXX This isn't quite right. The metadata (name, description) should be
    // editable even though the members can't be.

    $auto = $this->field('auto', array('CoGroup.id' => $id));
    
    return (bool)$auto;
  }
  
  /**
   * Reconcile CO Person memberships in a regular group.
   * 
   * @since COmanage Registry 3.3.0
   * @param Integer CoGroup Id
   * @param String  Whether to disable safeties (only supported for automatic groups)
   * @return true on success
   * @throws InvalidArgumentException
   */
  
  public function reconcile($id, $safeties="on") {
    // First find the group
    $args = array();
    $args['conditions']['CoGroup.id'] = $id;
    $args['contain'][] = 'CoGroupNesting';
    
    $group = $this->find('first', $args);
    
    if(empty($group['CoGroup'])) {
      throw new InvalidArgumentException(_txt('er.gr.nf', array($id)));
    }
    
    // If this is an automatic group, hand off to reconcileAutomaticGroup()
    if($group['CoGroup']['auto']) {
      return $this->reconcileAutomaticGroup($group, $safeties);
    }
    
    // XXX we run into a similar problem here as in CoPipeline, which is that
    // we can't have more than one CoGroupMember per CoPerson+GoGroup. While we
    // can work around that here, it does mean that it's going to be hard to tell
    // via the UI what direct, indirect, and automatic memberships a CO Person
    // should have. This will need to get fixed in v5.0.0. (CO-1585)
    // Until this is fixed, this could cause problems with some edge cases
    // unlikely to occur in most deployments.
    
    $args = array();
    $args['conditions']['CoGroupMember.co_group_id'] = $id;
    // Since we're not checking validity dates here, an expired group membership
    // will prevent a nested membership from manifesting. Is that a bug or a feature?
    // Will it change in v5?
    $args['contain'] = false;
    
    $tMembers = $this->CoGroupMember->find('all', $args);

    $nMembers = array();
    
    foreach($group['CoGroupNesting'] as $n) {
      // Pull the list of members of each nested group. This approach won't scale
      // well to very large groups, but we can't use subselects because Cake doesn't
      // support them natively, and buildStatement isn't directly supported by
      // ChangelogBehavior. Joins aren't much more elegant.
      
      $args = array();
      $args['conditions']['CoGroupMember.co_group_id'] = $n['co_group_id'];
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
      $args['fields'] = array('co_person_id', 'id');
      
      $nMembers[ $n['id'] ] = $this->CoGroupMember->find('list', $args);
    }
    
    // We start by deleting any no-longer-valid memberships, in case another group
    // will re-grant eligibility. While we're here, build a hash of co person
    // to group nestings.
    
    $tMembersByPerson = array();
    
    foreach($tMembers as $t) {
      if(!$t['CoGroupMember']['co_group_nesting_id']) {
        // This record is not from a nesting, so skip it
        continue;
      }
      
      if(!isset($nMembers[ $t['CoGroupMember']['co_group_nesting_id'] ][ $t['CoGroupMember']['co_person_id'] ])) {
        // Remove the CoGroupMember record
        $this->CoGroupMember->syncNestedMembership($group['CoGroup'], $t['CoGroupMember']['co_group_nesting_id'], $t['CoGroupMember']['co_person_id'], false);
      } else {
        $tMembersByPerson[ $t['CoGroupMember']['co_person_id'] ] = $t['CoGroupMember']['id'];
      }
    }
    
    // Pull the list of members of the nested group ($n) that are not already in
    // the target group ($id) and add them.
    
    foreach($group['CoGroupNesting'] as $n) {
      foreach($nMembers[ $n['id'] ] as $ncopid => $gmid) {
        if(!isset($tMembersByPerson[$ncopid])) {
          // For each person in $nMembers but not $tMembers, add them.
          $this->CoGroupMember->syncNestedMembership($group['CoGroup'], $n['id'], $ncopid, true);
          
          // Also update $tMembers so we don't add them again from another group.
          $tMembersByPerson[$ncopid] = $gmid;
        }
      }
    }
    
    // For now, at least, we don't trigger reconciliation of the parent group,
    // leaving that in the hands of the admin.
    
    return true;
  }
  
  /**
   * Reconcile CO Person memberships in an automatic group.
   * 
   * @since COmanage Registry 0.9.3
   * @param  Array  Array of CO Group info to reconcile
   * @param  String Whether to disable safety checks
   * @return true on success
   * @throws InvalidArgumentException
   */
  
  protected function reconcileAutomaticGroup($group, $safeties="on") {
    // Determine the set of people who should be in the target group.
    // Currently we only support ActiveMembers and AllMembers.
    
    $args = array();
    // This logic is similar to CoPersonRole::reconcileCouMembersGroupMemberships()
    // and CoPerson::afterSave().
    if($group['CoGroup']['group_type'] == GroupEnum::ActiveMembers) {
      $args['conditions']['CoPerson.status'] = array(StatusEnum::Active, StatusEnum::GracePeriod);
    } else {
      $args['conditions']['NOT']['CoPerson.status'] = StatusEnum::Deleted;
    }
    $args['conditions']['CoPerson.co_id'] = $group['CoGroup']['co_id'];
    if(!empty($group['CoGroup']['cou_id'])) {
      $args['joins'][0]['table'] = 'co_person_roles';
      $args['joins'][0]['alias'] = 'CoPersonRole';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoPersonRole.co_person_id';
      $args['conditions']['CoPersonRole.cou_id'] = $group['CoGroup']['cou_id'];
      if($group['CoGroup']['group_type'] == GroupEnum::ActiveMembers) {
        $args['conditions']['CoPersonRole.status'] = array(StatusEnum::Active, StatusEnum::GracePeriod);
      } else {
        $args['conditions']['NOT']['CoPersonRole.status'] = StatusEnum::Deleted;
      }
    }
    $args['contain'] = false;
    
    $coPeople = $this->Co->CoPerson->find('all', $args);
    $members = array();
    
    if($safeties != 'off') {
      // Determine the set of people currently in the target group
      $args = array();
      $args['conditions']['CoGroupMember.co_group_id'] = $group['CoGroup']['id'];
      $args['conditions']['CoGroupMember.co_group_id'] = $group['CoGroup']['id'];
      $args['fields'] = array('CoGroupMember.co_person_id', 'CoGroupMember.id' );
      $args['contain'] = false;
      
      $members = $this->Co->CoGroup->CoGroupMember->find('list', $args);
    }
    
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
      $data['CoGroupMember']['co_group_id'] = $group['CoGroup']['id'];
      $data['CoGroupMember']['co_person_id'] = $coPersonId;
      $data['CoGroupMember']['member'] = true;
      $data['CoGroupMember']['owner'] = false;
      
      $this->Co->CoPerson->CoGroupMember->clear();
      $this->Co->CoPerson->CoGroupMember->save($data); 
    }
    
    return true;  
  }
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $coId  CO ID to constrain search to
   * @param  string  $q     String to search for
   * @param  integer $limit Search limit
   * @return Array Array of search results, as from find('all)
   */
  
  public function search($coId, $q, $limit) {
    // Tokenize $q on spaces
    $tokens = explode(" ", $q);
    
    $args = array();
    foreach($tokens as $t) {
      $args['conditions']['AND'][] = array(
        'OR' => array(
          'LOWER(CoGroup.name) LIKE' => '%' . strtolower($t) . '%'
        )
      );
    }
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['order'] = array('CoGroup.name');
    $args['limit'] = $limit;
    $args['contain'] = false;
    
    return $this->find('all', $args);
  }
}
