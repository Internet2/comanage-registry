<?php
/**
 * COmanage Registry CO Email List Model
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
class CoEmailList extends AppModel {
  // Define class name for cake
  public $name = "CoEmailList";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  
  public $belongsTo = array(
    "Co",
    "AdminsCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'co_group_id'
    ),
    "MembersCoGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'co_group_id'
    ),
    "ModeratorsGroup" => array(
      'className' => 'CoGroup',
      'foreignKey' => 'co_group_id'
    )
  );
  
  public $hasOne = array(
  );
  
  public $hasMany = array(
    "HistoryRecord" => array(
      'dependent' => true
    )
  );
   
  // Default display field for cake generated views
  public $displayField = "name";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order?
//  public $order = array("CoGroup.name");
  
  public $actsAs = array('Containable',
                         'Provisioner',
                         'Changelog' => array('priority' => 5));

  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'name' => array(
      // We need the name to be RFC 5322 local-part compliant, but the
      // default email validation rules assume a full address so we can't
      // use them. We're actually a little stricter than we need to be here,
      // to avoid having to worry about quoting. We should be stricter with
      // ".", which can't lead, trail, or have 2+ consecutive.
      'rule' => '/[a-zA-Z0-9.-_]+/',
      'required' => true,
      'allowEmpty' => false
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'allowEmpty' => false
    ),
    'admins_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'members_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'moderators_co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'SuspendableStatusEnum'
  );
}
