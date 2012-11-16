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
    // Since cachedGroupGet is also cached, we don't need to do another cache here
    
    $groups = $this->cachedGroupGet($coPersonId, $coId, $groupName, $searchParam, $groupId);
    
    return (boolean)count($groups);
  }
  
  /**
   * Internal function to handle a cached group membership get.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @param  String Group name or SQL pattern to check
   * @param  String SQL parameter (eg: "LIKE") to use in search conditions
   * @param  Integer CO Group ID
   * @return Array Array of CO Groups as returned by find()
   */
  
  protected function cachedGroupGet($coPersonId, $coId, $groupName="", $searchParam="", $groupId=null) {
    // First check the cache (note: $condKey is something like "CoGroup.name LIKE")
    
    $condKey = null;
    $condValue = null;
    
    if($groupName != "") {
      $condKey = 'CoGroup.name' . ($searchParam != "" ? (" " . $searchParam) : "");
      $condValue = $groupName;
    } elseif($groupId != null) {
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
    if($condValue != null) {
      $args['conditions'][$condKey] = $condValue;
    }
    $args['conditions']['CoGroup.status'] = StatusEnum::Active;
    $args['conditions']['CoGroup.co_id'] = $coId;
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['conditions']['CoGroupMember.member'] = 1;
    $args['contain'] = false;
    
    $groups = $this->CoGroup->find('all', $args);
    
    $this->unbindModel(array('belongsTo' => array('CoGroup')));
    
    // Add this result to the cache
    
    $this->cache['coperson'][$coPersonId][$coId][$condKey][$condValue] = $groups;
    
    return $groups;
  }
  
  /**
   * Internal function to handle a cached person role check.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @param  Integer COU ID
   * @param  Boolean Whether to check only active roles or all roles
   * @return Boolean True if the CO Person has a matching role, false otherwise
   */
  
  protected function cachedPersonRoleCheck($coPersonId, $coId, $couId=null, $active=true) {
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
    if($active) {
      $args['conditions']['CoPerson.status'] = StatusEnum::Active;
      $args['conditions']['CoPersonRole.status'] = StatusEnum::Active;
    }
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
   * Determine what COUs a CO Person is a COU Admin for. Note this function will return
   * no COUs if the CO Person is a CO Admin but not a COU Admin.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @return Array List COU IDs and Names
   * @throws InvalidArgumentException
   */
  
  public function couAdminFor($coPersonId, $coId) {
    global $group_sep;
    
    $couNames = array();
    $childCous = array();
    
    // First pull the COUs $coPersonId is explicitly an admin for
    
    $couGroups = $this->cachedGroupGet($coPersonId, $coId, "admin" . $group_sep . "%", "LIKE");
    
    // What we actually have are the groups associated with each COU for which
    // coPersonId is an admin.
    
    $this->bindModel(array('belongsTo' => array('Cou')));
    
    foreach($couGroups as $couGroup) {
      $couName = substr($couGroup['CoGroup']['name'],
                        strpos($couGroup['CoGroup']['name'], $group_sep) + 1);
      
      // Pull the COU and its children (if any)
      
      try {
        $childCous = $this->Cou->childCous($couName, $coId, true);
      }
      catch(InvalidArgumentException $e) {
        throw new InvalidArgumentException($e->getMessage());
      }
    }
    
    $this->unbindModel(array('belongsTo' => array('Cou')));
    
    return $childCous;
  }
  
  /**
   * Determine if an identifier is associated with a CMP Administrator.
   *
   * @since  COmanage Registry v0.8
   * @param  String Identifier
   * @return Boolean True if the identifier is associated with a CMP administrator, false otherwise
   * @todo   Honor identifier type
   * @throws InvalidArgumentException
   */
  
  public function identifierIsCmpAdmin($identifier) {
    // First check the cache
    
    if(isset($this->cache['identifier'][$identifier]['cmpadmin'])) {
      return $this->cache['identifier'][$identifier]['cmpadmin'];
    }
    
    // Find the CO Person IDs for this identifier
    
    $this->bindModel(array('belongsTo' => array('CoPerson')));
    
    $coPersonIds = null;
    $coPerson = null;
    
    try {
      // XXX We should accept a configuration to specify which identifier type to be querying
      // (see also AppController::CalculateCMRoles)
      $coPersonIds = $this->CoPerson->idsForIdentifier($identifier, null, true);
    }
    catch(Exception $e) {
      // At the moment, an exception will just result in us returning false
      throw new InvalidArgumentException($e->getMessage());
    }
    
    // We now have a list of CO Person IDs, and need to figure out which one correlates to the
    // COmanage CO.
    
    if(!empty($coPersonIds)) {
      $args = array();
      $args['joins'][0]['table'] = 'cos';
      $args['joins'][0]['alias'] = 'Co';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoPerson.co_id=Co.id';
      $args['conditions']['Co.name'] = 'COmanage';
      $args['conditions']['Co.status'] = StatusEnum::Active;
      $args['conditions']['CoPerson.id'] = $coPersonIds;
      $args['contain'] = false;
      
      $coPerson = $this->CoPerson->find('first', $args);
    }
    
    $this->unbindModel(array('belongsTo' => array('CoPerson')));
    
    // Now that we have the right data, we can hand off to cachedGroupCheck.
    
    if(isset($coPerson['CoPerson'])) {
      $isAdmin = $this->cachedGroupCheck($coPerson['CoPerson']['id'],
                                         $coPerson['CoPerson']['co_id'],
                                         "admin");
      
      // Cache the result
      $this->cache['identifier'][$identifier]['cmpadmin'] = $isAdmin;
      
      return $isAdmin;
    }
    
    return false;
  }

  /**
   * Determine if an identifier is associated with an Administrator for any CO or COU.
   *
   * @since  COmanage Registry v0.8
   * @param  String Identifier
   * @param  String Type of check to perform ('coadmin' or 'couadmin')
   * @return Boolean True if the identifier is associated with a CO administrator, false otherwise
   * @todo   Honor identifier type
   * @throws InvalidArgumentException
   */
  
  protected function identifierIsAdmin($identifier, $adminType) {
    global $group_sep;
    
    // First check the cache
    
    if(isset($this->cache['identifier'][$identifier][$adminType])) {
      return $this->cache['identifier'][$identifier][$adminType];
    }
    
    // Find the CO Person IDs for this identifier
    
    $this->bindModel(array('belongsTo' => array('CoPerson')));
    
    $coPersonIds = null;
    $isAdmin = false;
    
    try {
      // XXX We should accept a configuration to specify which identifier type to be querying
      // (see also AppController::CalculateCMRoles)
      $coPersonIds = $this->CoPerson->idsForIdentifier($identifier, null, true);
    }
    catch(Exception $e) {
      // At the moment, an exception will just result in us returning false
      throw new InvalidArgumentException($e->getMessage());
    }
    
    // We now have a list of CO Person IDs, and need to see if any of them are an admin
    
    if(!empty($coPersonIds)) {
      $args = array();
      $args['joins'][0]['table'] = 'co_group_members';
      $args['joins'][0]['alias'] = 'CoGroupMember';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoGroup.id=CoGroupMember.co_group_id';
      $args['conditions']['CoGroupMember.co_person_id'] = $coPersonIds;
      if($adminType == 'coadmin') {
        $args['conditions']['CoGroup.name'] = 'admin';
      } else {
        $args['conditions']['CoGroup.name LIKE'] = 'admin' . $group_sep . '%';
      }
      $args['conditions']['CoGroup.status'] = StatusEnum::Active;
      $args['contain'] = false;
      
      $isAdmin = (boolean)$this->CoPerson->Co->CoGroup->find('count', $args);
    }
    
    $this->unbindModel(array('belongsTo' => array('CoPerson')));
    
    // Cache the result
    $this->cache['identifier'][$identifier][$adminType] = $isAdmin;
    
    return $isAdmin;
  }
  
  /**
   * Determine if an identifier is associated with an Administrator for any CO.
   *
   * @since  COmanage Registry v0.8
   * @param  String Identifier
   * @return Boolean True if the identifier is associated with a CO administrator, false otherwise
   * @todo   Honor identifier type
   * @throws InvalidArgumentException
   */
  
  public function identifierIsCoAdmin($identifier) {
    return $this->identifierIsAdmin($identifier, 'coadmin');
  }
  
  /**
   * Determine if an identifier is associated with an Administrator for any COU.
   *
   * @since  COmanage Registry v0.8
   * @param  String Identifier
   * @return Boolean True if the identifier is associated with a CO administrator, false otherwise
   * @todo   Honor identifier type
   * @throws InvalidArgumentException
   */
  
  public function identifierIsCouAdmin($identifier) {
    return $this->identifierIsAdmin($identifier, 'couadmin');
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
    
    // XXX define "admin" somewhere? CO-457 (also used in other places in this file)
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
    
    global $group_sep;
    
    // For code readability, we do this as separate checks rather than passing an OR
    // condition to cachedGroupCheck(). This may result in two DB calls, but it may not
    // since chances are we've already cached the results to isCoAdmin() (if we're being
    // called from CoEnrollmentFlow::authorize(), at least).
    
    // XXX define "admin" somewhere? CO-457
    if($this->cachedGroupCheck($coPersonId, $coId, "admin")) {
      return true;
    }
    
    return $this->cachedGroupCheck($coPersonId, $coId, "admin" . $group_sep . "%", "LIKE");
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
   * Determine if a CO Person is a COU Administrator for a specified COU. Note this function
   * will return false if CO Person is a CO Administrator, but not a COU Administrator.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @param  Integer COU ID
   * @return Boolean True if the CO Person is a COU Administrator for the specified COU, false otherwise
   */
  
  public function isCouAdmin($coPersonId, $coId, $couId) {
    // A person is a COU Admin if they are a member of the "admin:COU Name" group within the specified CO.
    
    global $group_sep;
    
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
    
    return $this->cachedGroupCheck($coPersonId, $coId, "admin" . $group_sep . $couName);
  }
  
  /**
   * Determine if a CO Person is a COU Administrator for another CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID of potential COU Admin
   * @param  Integer CO Person ID of subject
   * @param  Integer CO ID
   * @return Boolean True if the CO Person is a COU Administrator for the subject, false otherwise
   */
   
  public function isCouAdminForCoPerson($coPersonId, $subjectCoPersonId, $coId) {
    // First, pull the COUs for which $coPersonId is a COU admin
    $adminCous = $this->couAdminFor($coPersonId, $coId);
    
    // Next, walk through the list seeing if $subjectCoPersonId is a member. We do
    // one SQL query per COU, but an optimization that could be done is the query
    // WHERE cou_id IN (array_keys($adminCous)).
    
    foreach(array_keys($adminCous) as $couId) {
      // We accept statuses other than Active, since (eg) a COU Admin might want to view
      // the history of someone who is pending or expired.
      
      if($this->cachedPersonRoleCheck($subjectCoPersonId, $coId, $couId, false)) {
        // Match found, no need to continue
        return true;
      }
    }
    
    return false;
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