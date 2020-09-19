<?php
/**
 * COmanage Registry Meem Enroller Model
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class MeemEnroller extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "enroller";
  
  // Document foreign keys
  public $cmPluginHasMany = array(
    "CoGroup" => array(
      "MfaExemptCoGroupId" => array(
        'className' => 'MeemEnroller',
        'foreignKey' => 'mfa_exempt_co_group_id'
      )
    ),
    "CoPerson" => array(
      "MfaStatusCoPersonId" => array(
        'className' => 'MeemMfaStatus',
        'foreignKey' => 'co_person_id'
      )
    ),
    "CoEnrollmentFlow" => array(
      "MfaCoEnrollmentFlowId" => array(
        'className' => 'MeemEnroller',
        'foreignKey' => 'mfa_co_enrollment_flow_id'
      )
    )
  );
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "ApiUser",
    "CoEnrollmentFlowWedge",
    "MfaCoEnrollmentFlow" => array(
      'className' => 'CoEnrollmentFlow',
      'foreignKey' => 'mfa_co_enrollment_flow_id'
    ),
    "MfaExemptCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'mfa_exempt_co_group_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "co_enrollment_flow_wedge_id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_enrollment_flow_wedge_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'env_idp' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'env_mfa' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'mfa_exempt_co_group_id' => array(
      'rule' => array('numeric'),
      'required' => false,
      'allowEmpty' => true
    ),
    'mfa_initial_exemption' => array(
      'rule' => array('numeric'),
      'required' => false,
      'allowEmpty' => true
    ),
    'mfa_co_enrollment_flow_id' => array(
      'rule' => array('numeric'),
      'required' => false,
      'allowEmpty' => true
    ),
    'enable_reminder_page' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'return_url_allowlist' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'api_user_id' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.0.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
}
