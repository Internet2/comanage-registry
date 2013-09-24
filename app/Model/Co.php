<?php
/**
 * COmanage Registry CO Model
 *
 * Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
class Co extends AppModel {
  // Define class name for cake
  public $name = "Co";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $hasMany = array(
    // A CO has zero or more enrollment flows
    "CoEnrollmentFlow" => array('dependent' => true),
    // A CO has zero or more extended attributes
    "CoExtendedAttribute" => array('dependent' => true),
    // A CO has zero or more groups
    "CoGroup" => array('dependent' => true),
    // A CO can have zero or more CO people
    "CoIdentifierAssignment" => array('dependent' => true),
    "CoPerson" => array('dependent' => true),
    // A CO can have zero or more petitions
    "CoPetition" => array('dependent' => true),
    // A CO can have zero or more provisioning targets
    "CoProvisioningTarget" => array('dependent' => true),
    "CoTermsAndConditions" => array('dependent' => true),
    // A CO has zero or more COUs
    "Cou" => array('dependent' => true),
    // A CO has zero or more OrgIdentities, depending on if they are pooled.
    // It's OK to make the model dependent, because if they are pooled the
    // link won't be there to delete.
    "OrgIdentity" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "name";
  
  // Default ordering for find operations
  public $order = array("Co.name");
  
  // Validation rules for table elements
  public $validate = array(
    'name' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'description' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array('A', 'S')),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'status_t'
  );
}
