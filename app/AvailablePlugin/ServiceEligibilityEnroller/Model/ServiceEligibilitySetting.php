<?php
/**
 * COmanage Registry Service Eligibility Setting Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class ServiceEligibilitySetting extends AppModel {
  // Document foreign keys
  
  // Add behaviors
  public $actsAs = array('Containable'); //, 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");
  
  // Default display field for cake generated views
  public $displayField = "co_id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'allow_multiple' => array(
      'rule' => 'boolean',
      'required' => true,
      'allowEmpty' => false
    ),
    'require_selection' => array(
      'rule' => 'boolean',
      'required' => true,
      'allowEmpty' => false
    )
  );
}