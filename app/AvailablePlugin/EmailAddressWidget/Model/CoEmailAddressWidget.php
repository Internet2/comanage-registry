<?php
/**
 * COmanage Registry CO Email Address Widget Model
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoDashboardWidgetBackend", "Model");

class CoEmailAddressWidget extends CoDashboardWidgetBackend {
  // Define class name for cake
  public $name = "CoEmailAddressWidget";
  
  // Add behaviors
  public $actsAs = array('Containable');

  // Association rules from this model to other models
  public $belongsTo = array(
    "CoDashboardWidget"
  );

  // Validation rules for table elements
  public $validate = array(
    'co_dashboard_widget_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
          array('attribute' => 'EmailAddress.type',
            'default' => array(EmailAddressEnum::Delivery,
              EmailAddressEnum::Forwarding,
              EmailAddressEnum::MailingList,
              EmailAddressEnum::Official,
              EmailAddressEnum::Personal,
              EmailAddressEnum::Preferred,
              EmailAddressEnum::Recovery))),
        'required' => false,
        'allowEmpty' => false
      )
    ),
    'co_message_template_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );
}