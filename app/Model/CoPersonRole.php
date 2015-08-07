<?php
/**
 * COmanage Registry CO Person Role Model
 *
 * Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
    )        // foreign key to sponsor
  );
  
  public $hasMany = array(
    // A person can have one or more address
    "Address" => array('dependent' => true),
    "CoPetition" => array(
      'dependent' => true,
      'foreignKey' => 'enrollee_co_person_role_id'
    ),
    // It's probably not right to delete history records, but generally CO person roles shouldn't be deleted
    "HistoryRecord" => array('dependent' => true),
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
      )
    ),
    'o' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'ou' => array(
      'content' => array(
        'rule' => array('maxLength', 128),
        'required' => false,
        'allowEmpty' => true
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
  
  /**
   * Execute logic after a CO Person Role delete operation.
   * For now manage membership of CO Person in COU members groups.
   *
   * @since  COmanage Registry v0.9.3
   * @return none
   */
  
  public function afterDelete() {
    // Manage CO person membership in the COU members group.
    $this->reconcileCouMembersGroupMemberships($this->data, $this->alias);
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
    $this->reconcileCouMembersGroupMemberships($this->data, $this->alias);
  }
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v0.9.3
   */
  
  public function beforeSave($options = array()) {
    // If the validity of the role was changed, change the status appropriately
    
    if(!empty($this->data['CoPersonRole']['valid_from'])) {
      if(strtotime($this->data['CoPersonRole']['valid_from']) < time()
         && $this->data['CoPersonRole']['status'] == StatusEnum::Pending) {
        // Flag role as active
        $this->data['CoPersonRole']['status'] = StatusEnum::Active;
      } elseif(strtotime($this->data['CoPersonRole']['valid_from']) > time()
         && $this->data['CoPersonRole']['status'] == StatusEnum::Active) {
        // Flag role as pending
        $this->data['CoPersonRole']['status'] = StatusEnum::Pending;
      }
    }
    
    if(!empty($this->data['CoPersonRole']['valid_through'])) {
      if(strtotime($this->data['CoPersonRole']['valid_through']) < time()
         && ($this->data['CoPersonRole']['status'] == StatusEnum::Active
             ||
             $this->data['CoPersonRole']['status'] == StatusEnum::GracePeriod)) {
        // Flag role as expired
        $this->data['CoPersonRole']['status'] = StatusEnum::Expired;
      } elseif(strtotime($this->data['CoPersonRole']['valid_through']) > time()
         && $this->data['CoPersonRole']['status'] == StatusEnum::Expired) {
        // Flag role as active
        $this->data['CoPersonRole']['status'] = StatusEnum::Active;
      }
    }
  }

  /**
   * Reconcile memberships in COU members groups based on the 
   * CoPersonRole(s) for a CoPerson and the Cou(s) for those
   * roles.
   *
   * @since  COmanage Registry v0.9.3
   * @param data for the CoPersonRole model that was added or deleted
   * @param alias for the CoPersonRole model
   * @return none
   */
  
  public function reconcileCouMembersGroupMemberships($data, $alias = null) {
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
    $args = array();
    if(isset($data[$modelName]['co_person_id'])) {
	    $args['conditions']['CoPerson.id'] = $data[$modelName]['co_person_id'];
    } elseif(isset($data[$modelName]['id'])) {
      // We have to fetch the role record again in order to get the
      // CO Person ID.
      $xargs = array();
      $xargs['conditions'][$modelName . '.id'] = $data[$modelName]['id'];
      $xargs['contain'] = false;
			$coRole = $this->find('first', $xargs);   	
      $args['conditions']['CoPerson.id'] = $coRole[$modelName]['co_person_id'];
    } else {
    	// Bail out since we have been invoked with no way to find
    	// the role being managed.
    	return;
    }
    $args['contain']['CoPersonRole'] = 'Cou';
    $args['contain']['CoGroupMember'] = 'CoGroup';
    $coPerson = $this->CoPerson->find('first', $args);
    $coPersonId = $coPerson['CoPerson']['id'];
    
    // Loop over roles and find those with a COU. 
    $couMembersGroupNames = array();
    $membershipsToAdd = array();
    foreach($coPerson['CoPersonRole'] as $role) {
    	if(isset($role['cou_id'])) {
        // Use name of the COU to construct name of the COU members group.
    		$couMembersGroupName = 'members:' . $role['Cou']['name'];
    		$couMembersGroupNames[] = $couMembersGroupName;
        // Loop over memberships to see if a member of this members group.
        $isMember = false;
        foreach($coPerson['CoGroupMember'] as $membership) {
        	if($membership['CoGroup']['name'] == $couMembersGroupName && $membership['member']) {
        		$isMember = true;
            break;
        	}	
        }
        if(!$isMember) {
          if(!in_array($couMembersGroupName, $membershipsToAdd)) {
          	$membershipsToAdd[] = $couMembersGroupName;
          }
        }
    	}
    }
    
    // Add memberships and cut history records.
    foreach($membershipsToAdd as $groupName) {
  		$this->CoPerson->CoGroupMember->addByGroupName($coPersonId, $groupName, false);
    }
    
    // Loop over group memberships, pick out those for COU members groups, and
    // reconcile with the list of COU members group names.
    foreach($coPerson['CoGroupMember'] as $membership) {
      $groupName = $membership['CoGroup']['name'];
    	if(strncmp($groupName, 'members:', 8) == 0 && $membership['member']) {
    	  if(!in_array($groupName, $couMembersGroupNames)) {
          // Delete CO person from COU members group and cut a history record.
          $this->CoPerson->CoGroupMember->delete($membership['id']);
          // Cut history record.
          $msgData = array(
            $groupName,
            $membership['CoGroup']['id']
	  );
          $msg = _txt('rs.grm.deleted', $msgData);
          try {
          	$this->CoPerson->HistoryRecord->record(
          		$coPersonId,
                        null,
          		null, 
          		null, 
          		ActionEnum::CoGroupMemberDeleted, 
          		$msg
          		);
          }
          catch (Exception $e) {
            $coPersonId = $copersonrole['CoPerson']['id'];
            $coPersonRoleId = $copersonrole['CoPersonRole']['id'];
            $group = $membership['CoGroup']['name'];
            $msg = "Error creating history record when automatically deleting " .
            	"CO Person ID $coPersonId with CO Person Role ID $coPersonRoleId " .
            	"from group $group: " . $e->getMessage();
            $this->log($msg);
          }                  
    	}	
      }
    }
  }
}
