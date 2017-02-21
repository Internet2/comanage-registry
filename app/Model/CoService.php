<?php
/**
 * COmanage Registry CO Service Model
 *
 * Copyright (C) 2016-17 SURFnet BV
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2016-17 SURFnet BV
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
class CoService extends AppModel {
  // Define class name for cake
  public $name = "CoService";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "CoGroup"
  );
  
  // Default display field for cake generated views
  public $displayField = "description";
  
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
    'co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'service_url' => array(
      'rule' => array('url', true),
      'required' => false,
      'allowEmpty' => true
    ),
    'contact_email' => array(
      'rule' => array('email'),
      'required' => false,
      'allowEmpty' => true
    ),
    'entitlement_uri' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'allowEmpty' => true
    )
  );
  
  /**
   * Map a list of groups to the entitlements they are associated with.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer String CO ID
   * @param  Array Array of CO Group IDs
   * @return Array Array of entitlements, keyed on CO Service ID
   */
  
  public function mapCoGroupsToEntitlements($coId, $coGroupIds) {
    $args = array();
    $args['conditions']['CoService.co_id'] = $coId;
    $args['conditions']['OR']['CoService.co_group_id'] = $coGroupIds;
    $args['conditions']['OR'][] = 'CoService.co_group_id IS NULL';
    $args['conditions']['CoService.status'] = SuspendableStatusEnum::Active;
    $args['conditions'][] = 'CoService.entitlement_uri IS NOT NULL';
    $args['fields'] = array('CoService.id', 'CoService.entitlement_uri');
    $args['contain'] = false;
    
    return $this->find('list', $args);
  }
  
  /**
   * Find CO Services visible to the specified CO Person.
   *
   * @since  COmanage Registry v1.1.0
   * @param  RoleComponent
   * @param  Integer $coId       CO ID
   * @param  Integer $coPersonId CO Person ID, or null for public services
   * @return Array Array of CO Services
   */
  
  public function findServicesByPerson($Role, $coId, $coPersonId=null) {
    // First determine which visibilities to retrieve. Unlike most other cases,
    // we do NOT treat admins specially. They can either look in the configuration
    // if they need to see the complete list.
    
    $visibility = array(VisibilityEnum::Unauthenticated);
    $groups = null;
    
    if($coPersonId) {
      // Is this person an admin?
      
      if($Role->isCoAdmin($coPersonId, $coId)) {
        $visibility[] = VisibilityEnum::CoAdmin;
      }
      
      if($Role->isCoPerson($coPersonId, $coId)) {
        $visibility[] = VisibilityEnum::CoMember;
        
        // The join on CoGroupMember would be way too complicated, it'd be easier
        // to just pull two queries and merge. Instead, we'll just pull everything
        // flagged for CoGroupMember and then filter the results manually based on
        // the person's groups.
        $visibility[] = VisibilityEnum::CoGroupMember;
        
        $groups = $this->Co->CoGroup->findForCoPerson($coPersonId, null, null, null, false);
      }
    }
    
    $args = array();
    $args['conditions']['CoService.co_id'] = $coId;
    $args['conditions']['CoService.visibility'] = $visibility;
    $args['conditions']['CoService.status'] = SuspendableStatusEnum::Active;
    $args['order'] = 'CoService.description';
    $args['contain'] = false;
    
    $services = $this->find('all', $args);
    $groupIds = null;
    
    if(!empty($groups) && !empty($services) && $coPersonId) {
      // If $coPersonId is not set, there won't be any services with a CoGroupMember visibility
      
      $groupIds = Hash::extract($groups, '{n}.CoGroup.id');
    }
    
    // Walk the list of services and remove any with a group_id that doesn't match
    
    for($i = count($services) - 1;$i >= 0;$i--) {
      if($services[$i]['CoService']['visibility'] == VisibilityEnum::CoGroupMember
         && $services[$i]['CoService']['co_group_id']
         && !in_array($services[$i]['CoService']['co_group_id'], $groupIds)) {
        unset($services[$i]);
      }
    }
    
    return $services;
  }
}
