<?php
/**
 * COmanage Registry CO Group Model
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
class CoGroup extends AppModel {
  // Define class name for cake
  public $name = "CoGroup";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $hasMany = array(
    // A CoGroup has zero or more members
    "CoGroupMember" => array('dependent' => true)
  );

  public $belongsTo = array("Co");           // A CoGroup is attached to one CO
   
  // Default display field for cake generated views
  public $displayField = "name";
  
  // Default ordering for find operations
  public $order = array("CoGroup.name");
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'name' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'message' => 'A name must be provided'
    ),
    'description' => array(
      'rule' => '/.*/',
      'required' => false
    ),
    'open' => array(
      'rule' => array('boolean')
    ),
    'status' => array(
      'rule' => array('inList', array(StatusEnum::Active,
                                      StatusEnum::Suspended)),
      'required' => true,
      'message' => 'A valid status must be selected'
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'status_t'
  );
}
