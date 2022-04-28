<?php
/**
 * COmanage Registry Service Eligibility Enroller Model
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

class ServiceEligibilityEnroller extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "enroller";
  
  // Document foreign keys
  public $cmPluginHasMany = array(
    "Co" => array("ServiceEligibilitySetting"),
    "CoPersonRole" => array("ServiceEligibility"),
    "CoService" => array("ServiceEligibility")
  );
  
  // Add behaviors
  public $actsAs = array('Containable', 'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array("CoEnrollmentFlowWedge");
  
  public $hasMany = array(
    "ServiceEligibility" => array('dependent' => true)
  );
  
  // Default display field for cake generated views
  public $displayField = "id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_enrollment_flow_wedge_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.1.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array(
      "canvas" => array(_txt('ct.service_eligibilities.1') =>
        array('icon'       => 'tag',
              'controller' => 'service_eligibilities',
              'action'     => 'index')
      ),
      "coconfig" => array(_txt('ct.service_eligibility_settings.pl') =>
        array('icon'       => 'account_circle',
              'controller' => 'service_eligibility_settings',
              'action'     => 'index')
      )
    );
  }
}
