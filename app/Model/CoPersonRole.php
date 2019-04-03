<?php
/**
 * COmanage Registry CO Person Role Model
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
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoPersonRole extends AppModel {
  // Define class name for cake
  public $name = "CoPersonRole";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Normalization' => array('priority' => 4),
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A CO Org Person To is attached to one COU
    "Cou",
    "CoPerson"=> array(
      'className' => 'CoPerson',
      'foreignKey' => 'co_person_id'
    ),
    // A CO Org Person To is attached to one CO Person    
    "SponsorCoPerson" => array(
      'className' => 'CoPerson',
      'foreignKey' => 'sponsor_co_person_id'
    ),        // foreign key to sponsor
    // A CO Person created from a Pipeline has a Source Org Identity
    "SourceOrgIdentity" => array(
      'className' => 'OrgIdentity',
      'foreignKey' => 'source_org_identity_id'
    )
  );
  
  public $hasMany = array(
    // A person can have one or more address
    "Address" => array('dependent' => true),
    "CoExpirationCount" => array('dependent' => true),
    "CoPetition" => array(
      'dependent' => true,
      'foreignKey' => 'enrollee_co_person_role_id'
    ),
    "HistoryRecord",
    // A person can have one or more telephone numbers
    "TelephoneNumber" => array('dependent' => true)
  );

  // Default display field for cake generated views
  public $displayField = "CoPersonRole.id";
  
// XXX CO-296 Toss default order?
  // Default ordering for find operations
  //  public $order = array("CoPersonRole.id");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'co_person_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => true,
        'message' => 'A CO Person ID must be provided'
      )
    ),
    'cou_id' => array(
      'content' => array(
        'rule' => array('numeric'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'title' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'o' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'ou' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
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
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'content' => array(
        'rule' => array('inList', array(StatusEnum::Active,
                                        StatusEnum::Approved,
                                        StatusEnum::Confirmed,
                                        StatusEnum::Declined,
                                        StatusEnum::Deleted,
                                        StatusEnum::Denied,
                                        StatusEnum::Duplicate,
                                        StatusEnum::Expired,
                                        StatusEnum::GracePeriod,
                                        StatusEnum::Invited,
                                        StatusEnum::Pending,
                                        StatusEnum::PendingApproval,
                                        StatusEnum::PendingConfirmation,
                                        StatusEnum::Suspended))
      )
    ),
    'sponsor_co_person_id' => array(
      'content' => array(
        'rule' => array('numeric'),
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
    'affiliation' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'CoPersonRole.affiliation',
                              'default' => array(AffiliationEnum::Faculty,
                                                 AffiliationEnum::Student,
                                                 AffiliationEnum::Staff,
                                                 AffiliationEnum::Alum,
                                                 AffiliationEnum::Member,
                                                 AffiliationEnum::Affiliate,
                                                 AffiliationEnum::Employee,
                                                 AffiliationEnum::LibraryWalkIn))),
        'required' => true,
        'allowEmpty' => false
      )
    )
  );
  
  // Enum type hints
  
  public $cm_enum_txt = array(
    'status' => 'en.status'
  );
  
  public $cm_enum_types = array(
    'status' => 'StatusEnum'
  );
  
  // To detect if various attributes changed
  protected $cachedData = null;
    
  /**
   * Execute logic after a CO Person Role delete operation.
   * For now manage membership of CO Person in COU members groups.
   *
   * @since  COmanage Registry v0.9.3
   * @return none
   */
  
  public function afterDelete() {
    // Because CoPersonRole is changelog enabled, these references are still valid.
    
    // Pass through provision settings
    $provision = isset($this->_provision) ? $this->_provision : true;
    
    // Recalculate person status
    $coPersonId = $this->field('co_person_id');
    $this->CoPerson->recalculateStatus($coPersonId, $provision);
    
    // Manage CO person membership in the COU members group.
    $this->reconcileCouMembersGroupMemberships($this->id, $this->alias, $provision);
  }
  
  /**
   * Execute logic after a CO Person Role save operation.
   * For now manage membership of CO Person in COU members groups.
   *
   * @since  COmanage Registry v0.9.3
   * @param  boolean true if a new record was created (rather than update)
   * @param  array, the same passed into Model::save()
   * @return none
   */
  
  public function afterSave($created, $options = array()) {
    // Manage CO person membership in the COU members group.
    
    // Pass through provision setting in case we're being run via an enrollment flow
    $provision = true;
    
    if(isset($options['provision'])) {
      $provision = $options['provision'];
    }
    
    // Pull the current record
    $args = array();
    $args['conditions'][$this->alias.'.id'] = $this->id;
    $args['contain'] = array('CoPerson');

    $curdata = $this->find('first', $args);
    
    // If the role status changed, recalculate the person status
    
    if($created || ($this->cachedData[$this->alias]['status']
                    != $curdata[$this->alias]['status'])) {
      $coPersonId = $this->field('co_person_id');
      
      $this->CoPerson->recalculateStatus($coPersonId, $provision);
    }
    
    // Make sure COU Group Memberships are up to date
    $this->reconcileCouMembersGroupMemberships($this->id, $this->alias, $provision);
    
    if(!$created) {
      // Reset any expiration counts
      $this->CoExpirationCount->reset($curdata['CoPerson']['co_id'],
                                      $curdata[$this->alias]['id'],
                                      $affilChanged=($this->cachedData[$this->alias]['affiliation']
                                       != $curdata[$this->alias]['affiliation']),
                                      ($this->cachedData[$this->alias]['cou_id']
                                       != $curdata[$this->alias]['cou_id']),
                                      ($this->cachedData[$this->alias]['sponsor_co_person_id']
                                       != $curdata[$this->alias]['sponsor_co_person_id']),
                                      ($this->cachedData[$this->alias]['status']
                                       != $curdata[$this->alias]['status']),
                                      ($this->cachedData[$this->alias]['valid_through']
                                       != $curdata[$this->alias]['valid_through']));
    }
    
    return true;
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v0.9.3
   */
  
  public function beforeSave($options = array()) {
    // Cache the current record
    $this->cachedData = null;
    
    if(!empty($this->data[$this->alias]['id'])) {
      // We have an existing record
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $this->data[$this->alias]['id'];
      $args['contain'] = false;

      $this->cachedData = $this->find('first', $args);
    }
    
    // Possibly convert the requested timestamps to UTC from browser time.
    // Do this before the strtotime/time calls below, both of which use UTC.
    
    if($this->tz) {
      $localTZ = new DateTimeZone($this->tz);
      
      if(!empty($this->data[$this->alias]['valid_from'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data[$this->alias]['valid_from'], $localTZ);
        
        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data[$this->alias]['valid_from'] = strftime("%F %T", $offsetDT->getTimestamp());
      }
      
      if(!empty($this->data[$this->alias]['valid_through'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data[$this->alias]['valid_through'], $localTZ);
        
        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data[$this->alias]['valid_through'] = strftime("%F %T", $offsetDT->getTimestamp());
      }
    }
    
    // If the validity of the role was changed, change the status appropriately
    
    if(!empty($this->data[$this->alias]['status'])) {
      if(!empty($this->data[$this->alias]['valid_from'])) {
        if(strtotime($this->data[$this->alias]['valid_from']) < time()
           && $this->data[$this->alias]['status'] == StatusEnum::Pending) {
          // Flag role as active
          $this->data[$this->alias]['status'] = StatusEnum::Active;
        } elseif(strtotime($this->data[$this->alias]['valid_from']) > time()
           && $this->data[$this->alias]['status'] == StatusEnum::Active) {
          // Flag role as pending
          $this->data[$this->alias]['status'] = StatusEnum::Pending;
        }
      }
      
      if(!empty($this->data[$this->alias]['valid_through'])) {
        if(strtotime($this->data[$this->alias]['valid_through']) < time()
           && ($this->data[$this->alias]['status'] == StatusEnum::Active
               ||
               $this->data[$this->alias]['status'] == StatusEnum::GracePeriod)) {
          // Flag role as expired
          $this->data[$this->alias]['status'] = StatusEnum::Expired;
        } elseif(strtotime($this->data[$this->alias]['valid_through']) > time()
           && $this->data[$this->alias]['status'] == StatusEnum::Expired) {
          // Flag role as active
          $this->data[$this->alias]['status'] = StatusEnum::Active;
        }
      }
    } else {
      // If status is empty, we're probably in saveField. Ideally, we'd pull the
      // current status, but the only place this is currently called this way
      // is expire(), below.
    }
  }
  
  /**
   * Expire any roles for the specified CO Person ID. Specifically, set the status
   * to Expired and set the valid through date to yesterday, if one was set.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $coPersonId      CO Person ID
   * @param  Integer $couId           COU ID to expire roles for, or null for any role
   * @param  Integer $actorCoPersonId CO Person ID of actor, if interactive
   * @throws InvalidArgumentException
   */
  
  public function expire($coPersonId, $couId=null, $actorCoPersonId=null) {
    // First look for any matching roles
    
    $args = array();
    $args['conditions']['CoPersonRole.co_person_id'] = $coPersonId;
    if($couId) {
      $args['conditions']['CoPersonRole.cou_id'] = $couId;
    }
    $args['contain'] = array('Cou');
    
    $roles = $this->find('all', $args);
    
    if(!empty($roles)) {
      foreach($roles as $role) {
        $this->clear();
        $this->id = $role['CoPersonRole']['id'];
        
        if(!empty($role['CoPersonRole']['valid_through'])) {
          $this->saveField('valid_through', date('Y-m-d H:i:s',time()-1));
        }
        
        $this->saveField('status', StatusEnum::Expired);
        
        // Record history
        
        $this->CoPerson->HistoryRecord->record($coPersonId,
                                               $role['CoPersonRole']['id'],
                                               null,
                                               $actorCoPersonId,
                                               ActionEnum::CoPersonRoleEditedExpiration,
                                               !empty($role['Cou']['name'])
                                               ? _txt('rs.xp.role-a', array($role['Cou']['name']))
                                               : _txt('rs.xp.role'));
      }
    }
  }

  /**
   * Reconcile memberships in COU members groups based on the 
   * CoPersonRole(s) for a CoPerson and the Cou(s) for those roles.
   *
   * @since  COmanage Registry v0.9.3
   * @param  Integer $id           CoPersonRole ID
   * @param  String  $alias        Alias for the CoPersonRole model
   * @param  Boolean $provision    Whether to run provisioners
   * @param  Boolean $personActive If false, role is not eligible for ActiveMembers
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function reconcileCouMembersGroupMemberships($id, $alias=null, $provision=true, $personActive=true) {
    // Since the Provisioner Behavior will only provision group memberships
    // for CO People with an Active status we do not need to manage 
    // membership in the members group based on status here.  
    
    // Find the CO Person and retrieve at the same time all roles
    // and all group memberships.
    if(isset($alias)) {
      $modelName = $alias;
    } else {
      $modelName = 'CoPersonRole';
    }
    
    // Map the CO Person Role ID to a CO Person ID. Because CoPersonRole is
    // changelog enabled, this will work even on a delete or expunge.
    
    $coPersonId = $this->field('co_person_id', array($modelName.'.id' => $id));
    
    if(!$coPersonId) {
      // We're probably deleting the CO
      return;
    }
    
    $couId = $this->field('cou_id', array($modelName.'.id' => $id));
    
    if(!$couId) {
      // There is no COU associated with this role, so nothing to do
      return;
    }
    
    // We need to examine the status of all roles in the COU, not just the current
    // one, to see if the person is eligible for the relevant members group.
    
    $args = array();
    $args['conditions'][$modelName.'.co_person_id'] = $coPersonId;
    $args['conditions'][$modelName.'.cou_id'] = $couId;
    $args['fields'] = array('id', 'status');
    $args['contain'] = false;
    
    $status = $this->find('list', $args);
    
    // This logic is similar to CoGroup::reconcileAutomaticGroup()
    $activeEligible = $personActive && (array_search(StatusEnum::Active, $status) || array_search(StatusEnum::GracePeriod, $status));
    
    // For $allEligible, we need at least one role not Deleted
    $allEligible = false;
    
    foreach($status as $s) {
      if($s != StatusEnum::Deleted) {
        $allEligible = true;
        break;
      }
    }
    
    $this->CoPerson->CoGroupMember->syncMembership(GroupEnum::ActiveMembers, $couId, $coPersonId, $activeEligible, $provision);
    $this->CoPerson->CoGroupMember->syncMembership(GroupEnum::AllMembers, $couId, $coPersonId, $allEligible, $provision);

    // Remove group memberships if the COU ID or CO Person ID has changed.
    if (isset($this->cachedData)) {

      // If the COU has changed, remove memberships for the old COU.
      if (isset($this->cachedData[$this->alias]['cou_id'])) {
        $oldCouId = $this->cachedData[$this->alias]['cou_id'];
        if ($oldCouId !== $couId) {
          $this->CoPerson->CoGroupMember->syncMembership(GroupEnum::ActiveMembers, $oldCouId, $coPersonId, false, $provision);
          $this->CoPerson->CoGroupMember->syncMembership(GroupEnum::AllMembers, $oldCouId, $coPersonId, false, $provision);
        }
      }

      // If the person has changed, remove memberships for the old person.
      if (isset($this->cachedData[$this->alias]['co_person_id'])) {
        $oldCoPersonId = $this->cachedData[$this->alias]['co_person_id'];
        if ($oldCoPersonId !== $coPersonId) {
          $this->CoPerson->CoGroupMember->syncMembership(GroupEnum::ActiveMembers, $couId, $oldCoPersonId, false, $provision);
          $this->CoPerson->CoGroupMember->syncMembership(GroupEnum::AllMembers, $couId, $oldCoPersonId, false, $provision);
        }
      }
    }

  }
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $coId CO ID to constrain search to
   * @param  String  $q    String to search for
   * @return Array Array of search results, as from find('all)
   */
  
  public function search($coId, $q) {
    // Tokenize $q on spaces
    $tokens = explode(" ", $q);
    
    $args = array();
    
    foreach($tokens as $t) {
      $args['conditions']['AND'][] = array(
        // For some reason CoPersonRole.title throws a database error
        'OR' => array(
          'LOWER(title) LIKE' => '%' . strtolower($t) . '%',
          'LOWER(o) LIKE' => '%' . strtolower($t) . '%',
          'LOWER(ou) LIKE' => '%' . strtolower($t) . '%',
        )
      );
    }
    
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['order'] = array('CoPersonRole.title');
    $args['contain']['CoPerson'] = 'PrimaryName';
    
    return $this->find('all', $args);
  }
}
