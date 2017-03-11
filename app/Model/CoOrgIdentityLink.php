<?php
/**
 * COmanage Registry CoOrgIdentityLink Model
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
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoOrgIdentityLink extends AppModel {
  // Define class name for cake
  public $name = "CoOrgIdentityLink";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // A CO Org Identity Link is attached to one CO Person
    "CoPerson",
    // A CO Org Identity Link is attached to one Org Identity
    "OrgIdentity"
  );
  
  // Default display field for cake generated views
  public $displayField = "CoOrgIdentityLink.id";
  
  // Default ordering for find operations
  public $order = array("CoOrgIdentityLink.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_person_id' => array(
      'rule' => 'numeric'
    ),
    'org_identity_id' => array(
      'rule' => 'numeric'
    )
  );
}
