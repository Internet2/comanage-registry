<?php
/**
 * COmanage Registry CO Enrollment Source Model
 *
 * Copyright (C) 2016 SCG
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
 * @copyright     Copyright (C) 2016 SCG
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoEnrollmentSource extends AppModel {
  // Define class name for cake
  public $name = "CoEnrollmentSource";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoEnrollmentFlow",
    "OrgIdentitySource"
  );
  
  // Default display field for cake generated views
  public $displayField = "CoEnrollmentSource.id";
  
  // Default ordering for find operations
  public $order = array("CoEnrollmentSource.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_enrollment_flow_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'org_identity_source_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'org_identity_mode' => array(
      'rule' => array('inList',
                      array(EnrollmentOrgIdentityModeEnum::OISAuthenticate,
// Claim mode currently not supported (CO-1280)
//                            EnrollmentOrgIdentityModeEnum::OISClaim,
                            EnrollmentOrgIdentityModeEnum::OISSearch,
                            EnrollmentOrgIdentityModeEnum::OISSearchRequired,
                            EnrollmentOrgIdentityModeEnum::OISSelect,
                            EnrollmentOrgIdentityModeEnum::None)),
      'required' => true,
      'allowEmpty' => false
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );
}