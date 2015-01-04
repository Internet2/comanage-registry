<?php
/**
 * COmanage Registry CO Enrollment Attribute Default Model
 *
 * Copyright (C) 2013-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoEnrollmentAttributeDefault extends AppModel {
  // Define class name for cake
  public $name = "CoEnrollmentAttributeDefault";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("CoEnrollmentAttribute");
  
  // Default display field for cake generated views
  public $displayField = "value";
  
  // Default ordering for find operations
  public $order = array("value");
  
  // Validation rules for table elements
  public $validate = array(
    'affiliation'  => array(
      'required'   => false,
      'allowEmpty' => true,
      // This should probably use a dynamic validation rule instead
      'rule'       => '/.*/'
    ),
    'value' => array(
      'rule' => 'notEmpty',
      'required'   => true,
      'allowEmpty' => true,
      'message'    => 'A value must be provided'
    ),
    'modifiable' => array(
      'rule'       => array('boolean'),
      'required'   => true,
      'allowEmpty' => false
    )
  );
}