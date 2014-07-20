<?php
/**
 * COmanage Registry CO Enrollment Attribute Model
 *
 * Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoEnrollmentFlow extends AppModel {
  // Define class name for cake
  public $name = "CoEnrollmentFlow";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Custom find types
  public $findMethods = array('authorized' => true);
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "CoEnrollmentFlowApproverCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'approver_co_group_id'
    ),
    "CoEnrollmentFlowAuthzCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'authz_co_group_id'
    ),
    "CoEnrollmentFlowAuthzCou" => array(
      'className' => 'Cou',
      'foreignKey' => 'authz_cou_id'
    ),
    "CoEnrollmentFlowNotificationCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'notification_co_group_id'
    )
  );
  
  public $hasMany = array(
    // A CO Enrollment Flow has many CO Enrollment Attributes
    "CoEnrollmentAttribute" => array('dependent' => true),
    // A CO Enrollment Flow may have zero or more CO Petitions
    "CoPetition" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "name";
  
  // Default ordering for find operations
// XXX Toss? CO-296
//  public $order = array("CoEnrollmentFlow.name");
  
  // Validation rules for table elements
  public $validate = array(
    'name' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'authz_level' => array(
      'rule' => array('inList',
                      array(EnrollmentAuthzEnum::CoAdmin,
                            EnrollmentAuthzEnum::CoGroupMember,
                            EnrollmentAuthzEnum::CoOrCouAdmin,
                            EnrollmentAuthzEnum::CoPerson,
                            EnrollmentAuthzEnum::CouAdmin,
                            EnrollmentAuthzEnum::CouPerson,
                            EnrollmentAuthzEnum::None))
    ),
    'authz_cou_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'authz_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'match_policy' => array(
      'rule' => array('inList',
                      array(EnrollmentMatchPolicyEnum::Advisory,
                            EnrollmentMatchPolicyEnum::Automatic,
                            EnrollmentMatchPolicyEnum::None,
                            EnrollmentMatchPolicyEnum::Self))
    ),
    'approval_required' => array(
      'rule' => array('boolean')
    ),
    'approver_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'verify_email' => array(
      'rule' => array('boolean')
    ),
    'invitation_validity' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'require_authn' => array(
      'rule' => array('boolean')
    ),
    'notification_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    /*
    'notify_on_early_provision' => array(
      'rule' => 'email',
      'required' => false,
      'allowEmpty' => true
    ),
    'notify_on_provision' => array(
      'rule' => 'email',
      'required' => false,
      'allowEmpty' => true
    ),
    'notify_on_active' => array(
      'rule' => 'email',
      'required' => false,
      'allowEmpty' => true
    ),*/
    'notify_from' => array(
      'rule' => 'email',
      'required' => false,
      'allowEmpty' => true
    ),
    'verification_subject' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'allowEmpty' => false
    ),
    'verification_body' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'allowEmpty' => false
    ),
    'notify_on_approval' => array(
      'rule' => array('boolean')
    ),
    'approval_subject' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'allowEmpty' => false
    ),
    'approval_body' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'allowEmpty' => false
    ),
    'introduction_text' => array(
      'rule' => 'notEmpty',
      'required' => false,
      'allowEmpty' => true
    ),
    'redirect_on_submit' => array(
      'rule' => array('url', true),
      'required' => false,
      'allowEmpty' => true
    ),
    'redirect_on_confirm' => array(
      'rule' => array('url', true),
      'required' => false,
      'allowEmpty' => true
    ),
    'ignore_authoritative' => array(
      'rule' => array('boolean')
    ),
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Active,
                                      StatusEnum::Suspended))
    )
  );
  
  /**
   * Determine if a CO Person is authorized to run an Enrollment Flow.
   *
   * @since  COmanage Registry v0.7
   * @param  Array CO Enrollment Flow, as returned by find
   * @param  Integer CO Person ID
   * @param  RoleComponent
   * @return Boolean True if the CO Person is authorized, false otherwise
   */
  
  public function authorize($coEF, $coPersonId, $Role) {
    // If no authz is required, return true before we bother with any other checks
    
    if($coEF['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::None) {
      // No authz required
      return true;
    }
    
    // If CO Person is a CO admin, they are always authorized
    
    if($coPersonId
       && $Role->isCoAdmin($coPersonId, $coEF['CoEnrollmentFlow']['co_id'])) {
      return true;
    }
    
    switch($coEF['CoEnrollmentFlow']['authz_level']) {
      case EnrollmentAuthzEnum::CoAdmin:
        // We effectively already handled this, above
        break;
      case EnrollmentAuthzEnum::CoGroupMember:
        if($coPersonId
           && $Role->isCoGroupMember($coPersonId, $coEF['CoEnrollmentFlow']['authz_co_group_id'])) {
          return true;
        } 
        break;
      case EnrollmentAuthzEnum::CoOrCouAdmin:
        if($coPersonId
           && $Role->isCoOrCouAdmin($coPersonId, $coEF['CoEnrollmentFlow']['co_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::CoPerson:
        if($coPersonId
           && $Role->isCoPerson($coPersonId, $coEF['CoEnrollmentFlow']['co_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::CouAdmin:
        if($coPersonId
           && $Role->isCouAdmin($coPersonId, $coEF['CoEnrollmentFlow']['authz_cou_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::CouPerson:
        if($coPersonId
           && $Role->isCouPerson($coPersonId, $coEF['CoEnrollmentFlow']['co_id'], $coEF['CoEnrollmentFlow']['authz_cou_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::None:
        // We covered this already, above
        break;
    }
    
    // No matching Authz found
    return false;
  }
  
  /**
   * Determine if a CO Person is authorized to run an Enrollment Flow.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Enrollment Flow ID
   * @param  Integer CO Person ID
   * @param  RoleComponent
   * @return Boolean True if the CO Person is authorized, false otherwise
   */
  
  public function authorizeById($coEfId, $coPersonId, $Role) {
    // Retrieve the Enrollment Flow and pass it along
    
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $coEfId;
    $args['conditions']['CoEnrollmentFlow.status'] = StatusEnum::Active;
    $args['contain'] = false;
    
    $ef = $this->find('first', $args);
    
    if(empty($ef)) {
      return false;
    }
    
    return $this->authorize($ef, $coPersonId, $Role);
  }
}
