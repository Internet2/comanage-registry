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
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");     // A CO Enrollment Flow is attached to a CO
  
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
    'self_enroll' => array(
      'rule' => array('boolean')
    ),
    'admin_enroll' => array(
      'rule' => array('inList', array(AdministratorEnum::NoAdmin,
                                      AdministratorEnum::CoOrCouAdmin,
                                      AdministratorEnum::CoAdmin))
    ),
    'approval_required' => array(
      'rule' => array('boolean')
    ),
    'notify_on_early_provision' => array(
      'rule' => 'email',
      'required' => false
    ),
    'notify_on_provision' => array(
      'rule' => 'email',
      'required' => false
    ),
    'notify_on_active' => array(
      'rule' => 'email',
      'required' => false
    ),
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Active,
                                      StatusEnum::Suspended))
    )
  );
}
