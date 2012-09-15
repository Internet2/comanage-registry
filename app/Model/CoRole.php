<?php
/**
 * COmanage Registy CO Role Model
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoRole extends AppModel {
  public $name = 'CoRole';
  
  // This model currently queries other models for data. This might change (or be
  // supplemented) later.
  public $useTable = false;
  
  // Cache of checks we've already performed
  private $cache = array();
  
  /**
   * Internal function to handle a cached group membership check.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @param  String Group name or SQL pattern to check
   * @param  String SQL parameter (eg: "LIKE") to use in search conditions
   * @param  Integer CO Group ID
   * @return Boolean True if the CO Person is in the matching group, false otherwise
   */
  
  protected function cachedGroupCheck($coPersonId, $coId, $groupName="", $searchParam="", $groupId=null) {
    // First check the cache (note: $condKey is something like "CoGroup.name LIKE")
    
    $condKey = null;
    $condValue = null;
    
    if($groupName != "") {
      $condKey = 'CoGroup.name' . ($searchParam != "" ? (" " . $searchParam) : "");
      $condValue = $groupName;
    } else {
      $condKey = 'CoGroup.id';
      $condValue = $groupId;
    }
    
    if(isset($this->cache['coperson'][$coPersonId][$coId][$condKey][$condValue])) {
      return $this->cache['coperson'][$coPersonId][$coId][$condKey][$condValue];
    }
    
    // We cheat here and define a belongsTo relationship that doesn't actually exist
    // in the database. (But this is conceptually accurate, and allows access to
    // the model.) We do so dynamically so as not to screw up anything else.
    
    $this->bindModel(array('belongsTo' => array('CoGroup')));
    
    $args = array();
    $args['joins'][0]['table'] = 'co_group_members';
    $args['joins'][0]['alias'] = 'CoGroupMember';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoGroup.id=CoGroupMember.co_group_id';
    $args['conditions'][$condKey] = $condValue;
    $args['conditions']['CoGroup.status'] = StatusEnum::Active;
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['conditions']['CoGroupMember.member'] = 1;
    $args['contain'] = false;
    
    $member = $this->CoGroup->find('count', $args);
    
    $this->unbindModel(array('belongsTo' => array('CoGroup')));
    
    // Add this result to the cache
    
    $this->cache['coperson'][$coPersonId][$coId][$condKey][$condValue] = (boolean)$member;
    
    return (boolean)$member;
  }
  
  /**
   * Internal function to handle a cached person role check.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @param  Integer COU ID
   * @return Boolean True if the CO Person has a matching role, false otherwise
   */
  
  protected function cachedPersonRoleCheck($coPersonId, $coId, $couId=null) {
    // First check the cache
    
    if($couId) {
      if(isset($this->cache['coperson'][$coPersonId][$coId]['CouPerson'][$couId])) {
        return $this->cache['coperson'][$coPersonId][$coId]['CouPerson'][$couId];
      }
    } else {
      if(isset($this->cache['coperson'][$coPersonId][$coId]['CoPerson'])) {
        return $this->cache['coperson'][$coPersonId][$coId]['CoPerson'];
      }
    }
    
    // We cheat here and define a belongsTo relationship that doesn't actually exist
    // in the database. (But this is conceptually accurate, and allows access to
    // the model.) We do so dynamically so as not to screw up anything else.
    
    $this->bindModel(array('belongsTo' => array('CoPerson')));
    
    $args = array();
    $args['joins'][0]['table'] = 'co_person_roles';
    $args['joins'][0]['alias'] = 'CoPersonRole';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoPersonRole.co_person_id';
    $args['conditions']['CoPerson.id'] = $coPersonId;
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['conditions']['CoPerson.status'] = StatusEnum::Active;
    $args['conditions']['CoPersonRole.status'] = StatusEnum::Active;
    if($couId) {
      $args['conditions']['CoPersonRole.cou_id'] = $couId;
    }
    $args['contain'] = false;
    
    $member = $this->CoPerson->find('count', $args);
    
    $this->unbindModel(array('belongsTo' => array('CoPerson')));
    
    // Add this result to the cache
    
    if($couId) {
      $this->cache['coperson'][$coPersonId][$coId]['CouPerson'][$couId] = (boolean)$member;
    } else {
      $this->cache['coperson'][$coPersonId][$coId]['CoPerson'] = (boolean)$member;
    }
    
    return (boolean)$member;
  }

  /**
   * Determine if a CO Person is a CO Administrator.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @return Boolean True if the CO Person is a CO Administrator, false otherwise
   */
  
  public function isCoAdmin($coPersonId, $coId) {
    // A person is a CO Admin if they are a member of the "admin" group for the specified CO.
    
    // XXX define "admin" somewhere? CO-457
    return $this->cachedGroupCheck($coPersonId, $coId, "admin");
  }

  /**
   * Determine if a CO Person is a member of a CO Group.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @return Boolean True if the CO Person is a CO Administrator, false otherwise
   */
  
  public function isCoGroupMember($coPersonId, $coId, $coGroupId) {
    return $this->cachedGroupCheck($coPersonId, $coId, "", "", $coGroupId);
  }

  /**
   * Determine if a CO Person is a CO or COU Administrator.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @return Boolean True if the CO Person is a CO or COU Administrator, false otherwise
   */
  
  public function isCoOrCouAdmin($coPersonId, $coId) {
    // A person is a CO Admin if they are a member of the "admin" group for the specified CO.
    // A person is a COU Admin if they are a member of an "admin:*" group within the specified CO.
    
    // For code readability, we do this as separate checks rather than passing an OR
    // condition to cachedGroupCheck(). This may result in two DB calls, but it may not
    // since chances are we've already cached the results to isCoAdmin() (if we're being
    // called from CoEnrollmentFlow::authorize(), at least).
    
    // XXX define "admin" somewhere? CO-457
    if($this->cachedGroupCheck($coPersonId, $coId, "admin")) {
      return true;
    }
    
    return $this->cachedGroupCheck($coPersonId, $coId, "admin:%", "LIKE");
  }
  
  /**
   * Determine if a person is in a CO. A person is a CO Person if they have at least one
   * valid role within the CO.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @return Boolean True if the person is in the CO, false otherwise
   */
  
  public function isCoPerson($coPersonId, $coId) {
    return $this->cachedPersonRoleCheck($coPersonId, $coId);
  }

  /**
   * Determine if a CO Person is a CO or COU Administrator.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @return Boolean True if the CO Person is a CO or COU Administrator, false otherwise
   */
  
  public function isCouAdmin($coPersonId, $coId, $couId) {
    // A person is a COU Admin if they are a member of the "admin:COU Name" group within the specified CO.
    
    // We need to find the name of the COU first.
    
    $couName = "";
    
    if(isset($this->cache['cou'][$couId])) {
      $couName = $this->cache['cou'][$couId]['Cou']['name'];
    } else {
      // We cheat here and define a belongsTo relationship that doesn't actually exist
      // in the database. (But this is conceptually accurate, and allows access to
      // the model.) We do so dynamically so as not to screw up anything else.
      
      $this->bindModel(array('belongsTo' => array('Cou')));
      
      $args = array();
      $args['conditions']['Cou.id'] = $couId;
      $args['contain'] = false;
      
      $cou = $this->Cou->find('first', $args);
      
      $this->unbindModel(array('belongsTo' => array('Cou')));
      
      // Cache the results
      
      if(isset($cou['Cou']['name'])) {
        $this->cache['cou'][$couId] = $cou;
        $couName = $cou['Cou']['name'];
      } else {
        return false;
      }
    }
    
    return $this->cachedGroupCheck($coPersonId, $coId, "admin:" . $couName);
  }
  
  /**
   * Determine if a CO Person is in a COU.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @param  Integer COU ID
   * @return Boolean True if the person is in the COU, false otherwise
   */
  
  public function isCouPerson($coPersonId, $coId, $couId) {
    return $this->cachedPersonRoleCheck($coPersonId, $coId, $couId);
  }
}