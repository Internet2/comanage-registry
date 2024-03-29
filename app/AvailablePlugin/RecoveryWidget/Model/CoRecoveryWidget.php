<?php
/**
 * COmanage Registry CO Recovery Widget Model
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

App::uses("CoDashboardWidgetBackend", "Model");

class CoRecoveryWidget extends CoDashboardWidgetBackend {
  // Define class name for cake
  public $name = "CoRecoveryWidget";
  
  // Add behaviors
  public $actsAs = array('Containable');

  // Association rules from this model to other models
  public $belongsTo = array(
    "CoDashboardWidget",
    "Authenticator"
  );

  public $hasMany = array(
    "AuthenticatorResetToken" => array('dependent' => true)
  );

  // Default display field for cake generated views
  public $displayField = "id";

  // Validation rules for table elements
  public $validate = array(
    'co_dashboard_widget_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'enable_confirmation_resend' => array(
      'rule' => 'boolean',
      'required' => true,
      'allowEmpty' => false
    ),
    'identifier_template_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'authenticator_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'authenticator_reset_validity' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'authenticator_reset_template_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    ),
    'authenticator_success_redirect' => array(
      'rule' => array('url', true),
      'required' => false,
      'allowEmpty' => true
    )
  );
}