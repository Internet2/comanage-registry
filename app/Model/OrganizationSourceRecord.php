<?php
/**
 * COmanage Registry Organization Source Record Model
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class OrganizationSourceRecord extends AppModel {
  // Define class name for cake
  public $name = "OrganizationSourceRecord";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(//'CoDepartment',  -- not yet implemented
                            'Organization',
                            'OrganizationSource');
  
  public $hasMany = array();
  
  // Default display field for cake generated views
  public $displayField = "source_key";
  
  // Validation rules for table elements
  public $validate = array(
    'organization_source_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'source_key' => array(
      'content' => array(
        'rule' => 'notBlank',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'source_record' => array(
      'content' => array(
        'rule' => 'notBlank',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'last_update' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'organization_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    /* not yet implemented
    'co_department_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )*/
  );
}