<?php
/**
 * COmanage Registry CO Identifier Validator Model
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

class CoIdentifierValidator extends AppModel {
  // Define class name for cake
  public $name = "CoIdentifierValidator";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    'Co',
    'CoExtendedType'
  );
    
  // Default display field for cake generated views
  public $displayField = "description";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'description' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'plugin' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_extended_type_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'allowEmpty' => false
    )
  );
}

