<?php
/**
 * COmanage Registry CO LDAP Provisioner Attribute Model
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
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoLdapProvisionerAttribute extends AppModel {
  // Define class name for cake
  public $name = "CoLdapProvisionerAttribute";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "LdapProvisioner.CoLdapProvisionerTarget"
  );
    
  // Default display field for cake generated views
  public $displayField = "attribute";
  
  // Validation rules for table elements
  public $validate = array(
    'co_ldap_provisioner_target_id' => array(
      'rule' => 'numeric'
// Do not require, so SaveAll correctly handles when saved with CoLdapProvisionerTarget
//      'required' => true,
    ),
    'co_ldap_provisioner_attribute_grouping_id' => array(
      'rule' => 'numeric'
// Do not require, so SaveAll correctly handles when saved with CoLdapProvisionerTarget
//      'required' => true,
    ),
    'attribute' => array(
      'rule' => 'notBlank'
    ),
    'type' => array(
      'rule' => '/.*/'
    ),
    'export' => array(
      'rule' => 'boolean'
    ),
    'use_org_value' => array(
      'rule' => 'boolean'
    )
  );
}
