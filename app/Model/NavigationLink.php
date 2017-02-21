<?php
/**
 * COmanage Registry Navigation Link Model
 *
 * Copyright (C) 2013-17 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013-17 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class NavigationLink extends AppModel {
  // Define class name for cake
  public $name = "NavigationLink";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
    
  // Default display field for cake generated views
  public $displayField = "title";
  
  // Validation rules for table elements
  public $validate = array(
    'description' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),

    'title' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),

    'url' => array(
      'rule' => 'url',
      'required' => false,
      'allowEmpty' => true,
      'message' => 'Please provide a valid URL. Include "http://" (or similar) for off-site links.'
    ),

    'ordr' => array(
      'rule' => 'numeric',
      'allowEmpty' => true
    ),
    'location' => array(
      'rule' => array('inList', array(LinkLocationEnum::topBar)),
      'required' => true
    )
  );
  
}

