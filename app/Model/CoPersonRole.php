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
  public $actsAs = array('Containable', 'Normalization', 'Provisioner');
  
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
   * Execute logic after a CO Person Role save operation.
   * For now manage membership of CO Person in COU members group.
   *
   * @since  COmanage Registry v0.9.3
   * @param  boolean true if a new record was created (rather than update)
   * @param  array, the same passed into Model::save()
   * @return none
   */
  
  public function afterSave($created, $options) {
    // Manage CO person membership in the COU members group.
    
    // Since the Provisioner Behavior will only provision group memberships
    // for CO People with an Active status we do not need to manage 
    // membership in the members group based on status here.  So we only
    // add a CO Person to the COU members group whenever we detect
    // the CO Person Record has a COU.
    
    if(empty($this->data[$this->alias]['cou_id'])) {
      return;
    }
    
    $couid = $this->data[$this->alias]['cou_id'];
    
    // The saved data may have been contained and not have what we need
    // so find the model (CoPersonRole) data again and include COU and
    // group memberships to be used later.
    $args = array();
    $args['conditions'][$this->alias . '.id'] = $this->data[$this->alias]['id'];
    $args['contain'][] = 'Cou';
    $args['contain']['CoPerson']['CoGroupMember'] = 'CoGroup';
    $copersonrole = $this->find('first', $args);
    
    // Find the members group for the COU.    
    $args = array();
    $args['conditions']['CoGroup.name'] = 'members:' . $copersonrole['Cou']['name'];
    $args['conditions']['CoGroup.co_id'] = $copersonrole['CoPerson']['co_id'];
    $args['contain'] = false;
    $membersgroup = $this->CoPerson->CoGroupMember->CoGroup->find('first', $args);
    
    // Check to make sure the members group exists
    if(!empty($membersgroup)) {
      // Find all COU names for the CO so that we can manage memberships in
      // the associated members groups, since we might have to delete a memberhip
      // if the COU is changing for this role.
      $args = array();
      $args['conditions']['Cou.co_id'] = $copersonrole['CoPerson']['co_id'];
      $args['contain'] = false;
      $cous = $this->CoPerson->Co->Cou->find('all', $args);
      
      // Loop over any existing memberships to determine if already 
      // a member of the COU members group, and any existing membership
      // for another COU that may have to be deleted if the role is changing
      // COUs.
      $alreadyMember = false;
      
      foreach($copersonrole['CoPerson']['CoGroupMember'] as $membership) {
        if($membership['co_group_id'] == $membersgroup['CoGroup']['id']) {
          $alreadyMember = true;
        } else {
          foreach($cous as $cou) {
            $couName = $cou['Cou']['name'];
            // If a member in some other COU members group then delete that membership.
            if(($membership['CoGroup']['name'] == 'members:' . $couName)
               && ($couName !=  $copersonrole['Cou']['name'])) {
              $this->CoPerson->CoGroupMember->delete($membership['id']);
            }
          }
        }
      }
      
      if($alreadyMember) {
        return;
      }
      
      // Create the membership in the members group.
      $this->CoPerson->CoGroupMember->clear();
      $data = array();
      $data['CoGroupMember']['co_group_id'] = $membersgroup['CoGroup']['id'];
      $data['CoGroupMember']['co_person_id'] = $copersonrole[$this->alias]['co_person_id'];
      $data['CoGroupMember']['member'] = true;
              
      $this->CoPerson->CoGroupMember->save($data);
    }
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
        // Flag role as expired
        $this->data['CoPersonRole']['status'] = StatusEnum::Active;
      } elseif(strtotime($this->data['CoPersonRole']['valid_from']) > time()
         && $this->data['CoPersonRole']['status'] == StatusEnum::Active) {
        // Flag role as expired
        $this->data['CoPersonRole']['status'] = StatusEnum::Pending;
      }
    }
    
    if(!empty($this->data['CoPersonRole']['valid_through'])) {
      if(strtotime($this->data['CoPersonRole']['valid_through']) < time()
         && $this->data['CoPersonRole']['status'] == StatusEnum::Active) {
        // Flag role as expired
        $this->data['CoPersonRole']['status'] = StatusEnum::Expired;
      } elseif(strtotime($this->data['CoPersonRole']['valid_through']) > time()
         && $this->data['CoPersonRole']['status'] == StatusEnum::Expired) {
        // Flag role as expired
        $this->data['CoPersonRole']['status'] = StatusEnum::Active;
      }
    }
  }
}