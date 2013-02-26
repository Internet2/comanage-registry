<?php
/**
 * COmanage Registry CMP Enrollment Attribute Model
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CmpEnrollmentAttribute extends AppModel {
  // Define class name for cake
  public $name = "CmpEnrollmentAttribute";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("CmpEnrollmentConfiguration");     // A CMP Enrollment Attribute is part of a CMP Enrollment Configuration
    
  // Default display field for cake generated views
  public $displayField = "attribute";
  
  // Default ordering for find operations
  public $order = array("attribute");
  
  // Validation rules for table elements
  public $validate = array(
    'attribute' => array(
      'rule' => 'notEmpty',
      'required' => true,
      'message' => 'An attribute must be provided'
    ),
    'type' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'required' => array(
      'rule' => array('range', -2, 2)
    ),
    'ldap_name' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
    'saml_name' => array(
      'rule' => '/.*/',
      'required' => false,
      'allowEmpty' => true
    ),
  );
}
