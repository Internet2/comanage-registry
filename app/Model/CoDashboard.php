<?php
/**
 * COmanage Registry CO Dashboard Model
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class CoDashboard extends AppModel {
  // Define class name for cake
  public $name = "CoDashboard";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "CoDashboardVisibilityCoGroup" => array(
      'className' => 'CoDashboard',
      'foreignKey' => 'visibility_co_group_id'
    )
  );
  
  public $hasMany = array(
    'CoDashboardWidget' => array('dependent' => true),
    'CoSetting'
  );
  
  // Default display field for cake generated views
  public $displayField = "name";
  
  public $actsAs = array('Changelog' => array('priority' => 5),
                         'Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'name' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => false,
      'allowEmpty' => true
    ),
    'visibility' => array(
      'rule' => array('inList', array(VisibilityEnum::CoAdmin,
                                      VisibilityEnum::CoGroupMember,
                                      VisibilityEnum::CoMember,
                                      VisibilityEnum::Unauthenticated)),
      'required' => true,
      'allowEmpty' => false
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'allowEmpty' => false
    ),
    'primary_dashboard' => array(
      'rule' => 'boolean'
    )
  );
  
  /**
   * Determine if a CO Person is authorized to render a Dashboard.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Array         $db         Dashboard, as returned by find
   * @param  Integer       $coPersonId CO Person ID
   * @param  RoleComponent $Role
   * @return Boolean True if the CO Person is authorized, false otherwise
   */

  public function authorize($db, $coPersonId, $Role) {
    // If no authz is required, return true before we bother with any other checks

    if($db['CoDashboard']['visibility'] == VisibilityEnum::Unauthenticated) {
      // No authz required
      return true;
    }

    // If CO Person is a CO admin, they are always authorized

    if($coPersonId
       && $Role->isCoAdmin($coPersonId, $db['CoDashboard']['co_id'])) {
      return true;
    }
    
    switch($db['CoDashboard']['visibility']) {
      case VisibilityEnum::CoAdmin:
        return $Role->isCoAdmin($coPersonId, $db['CoDashboard']['co_id']);
        break;
      case VisibilityEnum::CoGroupMember:
        return $Role->isCoGroupMember($coPersonId, $db['CoDashboard']['visibility_co_group_id']);
        break;
      case VisibilityEnum::CoMember:
        return $Role->isCoPerson($coPersonId, $db['CoDashboard']['co_id']);
        break;
      case VisibilityEnum::Unauthenticated:
        return true;
        break;
    }
    
    // No matching Authz found
    return false;
  }
}
