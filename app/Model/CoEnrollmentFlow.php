<?php
/**
 * COmanage Registry CO Enrollment Attribute Model
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
    "CoEnrollmentFlowAuthzCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'authz_co_group_id'
    ),
    "CoEnrollmentFlowAuthzCou" => array(
      'className' => 'Cou',
      'foreignKey' => 'authz_cou_id'
    ),
    "Co"
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
  public $order = array("CoEnrollmentFlow.name");
  
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
    'confirm_email' => array(
      'rule' => array('boolean')
    ),
    'require_authn' => array(
      'rule' => array('boolean')
    ),
    'notify_on_early_provision' => array(
      'rule' => 'email',
      'allowEmpty' => true
    ),
    'notify_on_provision' => array(
      'rule' => 'email',
      'allowEmpty' => true
    ),
    'notify_on_active' => array(
      'rule' => 'email',
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Active,
                                      StatusEnum::Suspended))
    )
  );
  
  /**
   * Obtain Enrollment Flows a given CO person is authorized to run.
   * This method implements a Custom Find type.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @return Array CoEnrollmentFlow information, as returned by find
   */
  
  protected function _findAuthorized($state, $query, $results = array()) {
    if($state == 'before') {
      // Called before the find is performed
      
      // We don't do anything special here
      
      return $query;
    } elseif($state == 'after') {
      // Called after the find is performed
      
      // Walk through the returned Enrollment Flows and see if the specified CO Person
      // is authorized. If the CO Person is a CMP or CO Admin, they are always authorized.
      
      $filteredResults = array();
      
      foreach($results as $coEF) {
        if($this->authorize($coEF, $query['authorizeCoPersonId'])) {
          $filteredResults[] = $coEF;
        }
      }
      
      return $filteredResults;
    }
  }
  
  /**
   * Determine if a CO Person is authorized to run an Enrollment Flow.
   *
   * @since  COmanage Registry v0.7
   * @param  Array CO Enrollment Flow, as returned by find
   * @param  Integer CO Person ID
   * @return Boolean True if the CO Person is authorized, false otherwise
   */
  
  public function authorize($coEF, $coPersonId) {
    // If no authz is required, return true before we bother with any other checks
    
    if($coEF['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::None) {
      // No authz required
      return true;
    }
    
    $CoRole = ClassRegistry::init('CoRole');
    
    // If CO Person is a CO admin, they are always authorized
    
    if($CoRole->isCoAdmin($coPersonId, $coEF['CoEnrollmentFlow']['co_id'])) {
      return true;
    }
    
    switch($coEF['CoEnrollmentFlow']['authz_level']) {
      case EnrollmentAuthzEnum::CoAdmin:
        // We effectively already handled this, above
        break;
      case EnrollmentAuthzEnum::CoGroupMember:
        if($CoRole->isCoGroupMember($coPersonId, $coEF['CoEnrollmentFlow']['co_id'], $coEF['CoEnrollmentFlow']['authz_co_group_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::CoOrCouAdmin:
        if($CoRole->isCoOrCouAdmin($coPersonId, $coEF['CoEnrollmentFlow']['co_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::CoPerson:
        if($CoRole->isCoPerson($coPersonId, $coEF['CoEnrollmentFlow']['co_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::CouAdmin:
        if($CoRole->isCouAdmin($coPersonId, $coEF['CoEnrollmentFlow']['co_id'], $coEF['CoEnrollmentFlow']['authz_cou_id'])) {
          return true;
        }
        break;
      case EnrollmentAuthzEnum::CouPerson:
        if($CoRole->isCouPerson($coPersonId, $coEF['CoEnrollmentFlow']['co_id'], $coEF['CoEnrollmentFlow']['authz_cou_id'])) {
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
   * @return Boolean True if the CO Person is authorized, false otherwise
   */
  
  public function authorizeById($coEfId, $coPersonId) {
    // Retrieve the Enrollment Flow and pass it along
    
    $args = array();
    $args['conditions']['CoEnrollmentFlow.id'] = $coEfId;
    $args['conditions']['CoEnrollmentFlow.status'] = StatusEnum::Active;
    $args['contain'] = false;
    
    $ef = $this->find('first', $args);
    
    if(empty($ef)) {
      return false;
    }
    
    return $this->authorize($ef, $coPersonId);
  }
}
