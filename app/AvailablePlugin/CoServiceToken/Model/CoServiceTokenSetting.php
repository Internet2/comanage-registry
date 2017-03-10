<?php
/**
 * COmanage Registry CO Service Token Setting Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoServiceTokenSetting extends AppModel {
  // Define class name for cake
  public $name = "CoServiceTokenSetting";

  // Current schema version for API
  public $version = "1.0";

  // Add behaviors
  public $actsAs = array('Containable');

  // Association rules from this model to other models
  public $belongsTo = array(
    // Inverse relation is set in Controller
    "CoService"
  );

  // Default display field for cake generated views
  public $displayField = "co_service_id";

  // Validation rules for table elements
  public $validate = array(
    'co_service_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'enabled' => array(
      'rule' => 'boolean',
      'required' => false,
      'allowEmpty' => true
    ),
    'token_type' => array(
      'rule' => array('inList', array(CoServiceTokenTypeEnum::Plain08,
                                      CoServiceTokenTypeEnum::Plain15)),
      'required' => false,
      'allowEmpty' => true
    )
  );
}