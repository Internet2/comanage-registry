<?php
/**
 * COmanage Registry CO Group Member Model
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
  
class CoGroupMember extends AppModel {
  // Define class name for cake
  public $name = "CoGroupMember";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A CoGroupMember is attached to one CoGroup
    "CoGroup",
    // A CoGroupMember is attached to one CoPerson
    "CoPerson");
  
  // Default display field for cake generated views
  public $displayField = "co_person_id";
  
  // Default ordering for find operations
  public $order = array("co_person_id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_group_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => true
    ),
    'member' => array(
      'rule' => array('boolean')
    ),
    'owner' => array(
      'rule' => array('boolean')
    )
  );
}
