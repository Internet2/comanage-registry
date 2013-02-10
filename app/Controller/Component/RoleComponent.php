<?php
/**
 * COmanage Registy Role Component Model
 *
 * Copyright (C) 2012-3 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-3 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
 
class RoleComponent extends Component {
  public $components = array("Session");
  
  // Cache of checks we've already performed
  private $cache = array();
  
  /**
   * Cached CO ID lookup.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID
   * @return Integer CO ID for CO Person ID
   * @throws InvalidArgumentException
   */
  
  protected function cachedCoIdLookup($coPersonId) {
    if(isset($this->cache['coperson'][$coPersonId]['co_id'])) {
      return $this->cache['coperson'][$coPersonId]['co_id'];
    }
    
    $CoPerson = ClassRegistry::init('CoPerson');
    
    $coId = $CoPerson->field('co_id', array('CoPerson.id' => $coPersonId));
    
    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.cop.unk-a', array($coPersonId)));
    }
    
    $this->cache['coperson'][$coPersonId]['co_id'] = $coId;
    
    return $coId;
  }
  
  /**
   * Cached CO ID lookup by CO Group.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Group ID
   * @return Integer CO ID for CO Group ID
   * @throws InvalidArgumentException
   */
  
  protected function cachedCoIdLookupByCoGroup($coGroupId) {
    if(isset($this->cache['cogroup'][$coGroupId]['co_id'])) {
      return $this->cache['cogroup'][$coGroupId]['co_id'];
    }
    
    $CoGroup = ClassRegistry::init('CoGroup');
    
    $coId = $CoGroup->field('co_id', array('CoGroup.id' => $coGroupId));
    
    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.gr.nf', array($coGroupId)));
    }
    
    $this->cache['cogroup'][$coGroupId]['co_id'] = $coId;
    
    return $coId;
  }
  
  /**
   * Internal function to handle a cached group membership check.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  String Group name or SQL pattern to check
   * @param  String SQL parameter (eg: "LIKE") to use in search conditions
   * @param  Integer CO Group ID
   * @param  Boolean Check for ownership instead of membership
   * @return Boolean True if the CO Person is in the matching group, false otherwise
   */
  
  protected function cachedGroupCheck($coPersonId, $groupName="", $searchParam="", $groupId=null, $owner=false) {
    // Since cachedGroupGet is also cached, we don't need to do another cache here
    
    $groups = $this->cachedGroupGet($coPersonId, $groupName, $searchParam, $groupId, $owner);
    
    return (boolean)count($groups);
  }
  
  /**
   * Internal function to handle a cached group membership get.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID
   * @param  String Group name or SQL pattern to check
   * @param  String SQL parameter (eg: "LIKE") to use in search conditions
   * @param  Integer CO Group ID
   * @param  Boolean Check for ownership instead of membership
   * @return Array Array of CO Groups as returned by find()
   */
  
  protected function cachedGroupGet($coPersonId, $groupName="", $searchParam="", $groupId=null, $owner=false) {
    // First check the cache (note: $condKey is something like "CoGroup.name LIKE")
    
    $condKey = null;
    $condValue = null;
    $groupRole = ($owner ? 'owner' : 'member');
    
    if($groupName != "") {
      $condKey = 'CoGroup.name' . ($searchParam != "" ? (" " . $searchParam) : "");
      $condValue = $groupName;
    } elseif($groupId != null) {
      $condKey = 'CoGroup.id';
      $condValue = $groupId;
    }
    
    if(isset($this->cache['coperson'][$coPersonId][$condKey][$condValue][$groupRole])) {
      return $this->cache['coperson'][$coPersonId][$condKey][$condValue][$groupRole];
    }
    
    $CoGroup = ClassRegistry::init('CoGroup');
    
    $args = array();
    $args['joins'][0]['table'] = 'co_group_members';
    $args['joins'][0]['alias'] = 'CoGroupMember';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoGroup.id=CoGroupMember.co_group_id';
    if($condValue != null) {
      $args['conditions'][$condKey] = $condValue;
    }
    $args['conditions']['CoGroup.status'] = StatusEnum::Active;
    $args['conditions']['CoGroupMember.co_person_id'] = $coPersonId;
    $args['conditions']['CoGroupMember.'.$groupRole] = 1;
    $args['contain'] = false;
    
    $groups = $CoGroup->find('all', $args);
    
    // Add this result to the cache
    
    $this->cache['coperson'][$coPersonId][$condKey][$condValue][$groupRole] = $groups;
    
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
    
    $CoPerson = ClassRegistry::init('CoPerson');
    
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
    
    $member = $CoPerson->find('count', $args);
    
    // Add this result to the cache
    
    if($couId) {
      $this->cache['coperson'][$coPersonId][$coId]['CouPerson'][$couId] = (boolean)$member;
    } else {
      $this->cache['coperson'][$coPersonId][$coId]['CoPerson'] = (boolean)$member;
    }
    
    return (boolean)$member;
  }
  
  /**
   * Determine which COmanage platform roles the current user has.
   * - precondition: UsersController::login has run
   *
   * @since  COmanage Registry v0.1 (in AppController through v0.7)
   * @return Array An array with values of 'true' if the user has the specified role or 'false' otherwise, with possible keys of
   * - cmadmin: COmanage platform administrator
   * - coadmin: Administrator of the current CO
   * - couadmin: Administrator of one or more COUs within the current CO 
   * - admincous: COUs for which user is an Administrator (list of COU IDs and Names)
   * - comember: Member of the current CO
   * - admin: Valid admin in any CO
   * - subadmin: Valid admin for any COU
   * - user: Valid user in any CO (ie: to the platform)
   * - apiuser: Valid API (REST) user (for now, API users are equivalent to cmadmins)
   * - orgidentityid: Org Identity ID of current user (or false)
   * - copersonid: CO Person ID of current user in current CO (or false, including if co person is not in current CO)
   * - orgidentities: Array of Org Identities for current user
   */
  
  public function calculateCMRoles() {
    // We basically translate from the currently logged in info as determined by
    // UsersController to role information as determined by CoRole.
    
    global $group_sep;
    
    $ret = array(
      'cmadmin' => false,
      'coadmin' => false,
      'couadmin' => false,
      'admincous' => null,
      'comember' => false,
      'admin' => false,
      'subadmin' => false,
      'user' => false,
      'apiuser' => false,
      'orgidentityid' => false,
      'copersonid' => false,
      'orgidentities' => null
    );
    
    $coId = null;     // CO ID as requested by user
    $coPersonId = null;
    $username = null;
    
    if($this->Session->check('Auth.User.username')) {
      $username = $this->Session->read('Auth.User.username');
    }
    
    // API user or Org Person?
    
    if($this->Session->check('Auth.User.api_user_id')) {
      $ret['apiuser'] = true;
      $ret['cmadmin'] = true;  // API users are currently platform admins (CO-91)
      
      // Return here to avoid triggering a bunch of RoleComponent queries that
      // may fail since api users are not currently enrolled in any CO.
      
      return $ret;
    } elseif($this->Session->check('Auth.User.org_identities')) {
      $ret['orgidentities'] = $this->Session->read('Auth.User.org_identities');
    }
    
    // Is this user a CMP admin?
    
    if($username != null) {
      $ret['cmadmin'] = $this->identifierIsCmpAdmin($username);
    }
    
    // Pull the current CO from our invoking controller
    $controller = $this->_Collection->getController();
    
    $coId = $controller->cur_co['Co']['id'];
    
    // Figure out the revelant CO Person ID for the current user and the current CO
    
    $CoPerson = ClassRegistry::init('CoPerson');
    
    try {
      // XXX We should pass an identifier type that was somehow configured (see also CoRole->identifierIs*Admin)
      $coPersonId = $CoPerson->idForIdentifier($coId, $username, null, true);
    }
    catch(Exception $e) {
      // Not really clear that we should fail here
      //throw new InvalidArgumentException($e->getMessage());
    }
    
    // Is this user a member of the current CO?
    // We only want to populate $ret['copersonid'] if this CO Person ID is in the current CO
    
    if($coPersonId && $coId && $this->isCoPerson($coPersonId, $coId)) {
      $ret['copersonid'] = $coPersonId;
      $ret['comember'] = true;
    }
    
    // Also store the co_person_id directly in the session to make it easier to find.
    // The above check will only succeed if $coPersonId has an active role, but (at
    // least for now) we set the session value regardless.
    
    if($coPersonId) {
      $this->Session->write('Auth.User.co_person_id', $coPersonId);
    }
    
    // Is this user an admin of the current CO?
    
    if($ret['comember'] && $coPersonId) {
      $ret['coadmin'] = $this->isCoAdmin($coPersonId);
      
      // Is this user an admin of a COU within the current CO?
      
      $ret['admincous'] = null;
      
      try {
        $ret['admincous'] = $this->couAdminFor($coPersonId);
      }
      catch(InvalidArgumentException $e) {
        // Not really clear we should do anything with this error
      }
      
      $ret['couadmin'] = !empty($ret['admincous']);
    }
    
    // Is the user an admin of any CO?
    
    $ret['admin'] = ($ret['coadmin'] || $this->identifierIsCoAdmin($username));
    
    // Is the user a COU admin for any CO?
    
    $ret['subadmin'] = ($ret['couadmin'] || $this->identifierIsCouAdmin($username));
    
    // Is the user a platform user?
    
    if($this->Session->check('Auth.User.name')) {
      $ret['user'] = true;
    }
    
    return $ret;
  }
  
  /**
   * Determine what COUs a CO Person is a COU Admin for. Note this function will return
   * no COUs if the CO Person is a CO Admin but not a COU Admin.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID
   * @return Array List COU IDs and Names
   * @throws InvalidArgumentException
   */
  
  public function couAdminFor($coPersonId) {
    global $group_sep;
    
    $couNames = array();
    $childCous = array();
    
    try {
      $coId = $this->cachedCoIdLookup($coPersonId);
    }
    catch(InvalidArgumentException $e) {
      throw new InvalidArgumentException($e->getMessage());
    }
    
    // First pull the COUs $coPersonId is explicitly an admin for
    
    $couGroups = $this->cachedGroupGet($coPersonId, "admin" . $group_sep . "%", "LIKE");
    
    // What we actually have are the groups associated with each COU for which
    // coPersonId is an admin.
    
    $Cou = ClassRegistry::init('Cou');
    
    foreach($couGroups as $couGroup) {
      $couName = substr($couGroup['CoGroup']['name'],
                        strpos($couGroup['CoGroup']['name'], $group_sep) + 1);
      
      // Pull the COU and its children (if any)
      
      try {
        $childCous = $Cou->childCous($couName, $coId, true);
      }
      catch(InvalidArgumentException $e) {
        throw new InvalidArgumentException($e->getMessage());
      }
    }
    
    return $childCous;
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
    
    $CoPerson = ClassRegistry::init('CoPerson');
    
    $coPersonIds = null;
    $isAdmin = false;
    
    try {
      // XXX We should accept a configuration to specify which identifier type to be querying
      // (see also AppController::CalculateCMRoles)
      $coPersonIds = $CoPerson->idsForIdentifier($identifier, null, true);
    }
    catch(Exception $e) {
      // At the moment, an exception will just result in us returning false
      //throw new InvalidArgumentException($e->getMessage());
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
      
      $isAdmin = (boolean)$CoPerson->Co->CoGroup->find('count', $args);
    }
    
    // Cache the result
    $this->cache['identifier'][$identifier][$adminType] = $isAdmin;
    
    return $isAdmin;
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
    
    $CoPerson = ClassRegistry::init('CoPerson');
    
    $coPersonIds = null;
    $coPerson = null;
    
    try {
      // XXX We should accept a configuration to specify which identifier type to be querying
      // (see also AppController::CalculateCMRoles)
      $coPersonIds = $CoPerson->idsForIdentifier($identifier, null, true);
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
      
      $coPerson = $CoPerson->find('first', $args);
    }
    
    // Now that we have the right data, we can hand off to cachedGroupCheck.
    
    if(isset($coPerson['CoPerson'])) {
      $isAdmin = $this->cachedGroupCheck($coPerson['CoPerson']['id'],
                                         "admin");
      
      // Cache the result
      $this->cache['identifier'][$identifier]['cmpadmin'] = $isAdmin;
      
      return $isAdmin;
    }
    
    return false;
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
   * @param  Integer CO ID that CO Person is an Admin for, or null for any CO
   * @return Boolean True if the CO Person is a CO Administrator, false otherwise
   */
  
  public function isCoAdmin($coPersonId, $coId=null) {
    // A person is a CO Admin if they are a member of the "admin" group for the specified CO.
    
    if($coId) {
      // First check that $coPersonId is in $coId
      
      if(!$this->isCoPerson($coPersonId, $coId)) {
        return false;
      }
    }
    
    // XXX define "admin" somewhere? CO-457 (also used in other places in this file)
    return $this->cachedGroupCheck($coPersonId, "admin");
  }
  
  /**
   * Determine if a CO Person is a CO Administrator for another CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID of potential CO Admin
   * @param  Integer CO Person ID of subject
   * @param  Integer CO ID
   * @return Boolean True if the CO Person is a CO Administrator for the subject, false otherwise
   * @throws InvalidArgumentException
   */
  
  public function isCoAdminForCoPerson($coPersonId, $subjectCoPersonId) {
    // Look up the CO ID and hand off to the other checks
    
    try {
      $coId = $this->cachedCoIdLookup($coPersonId);
      $sCoId = $this->cachedCoIdLookup($subjectCoPersonId);
    }
    catch(InvalidArgumentException $e) {
      throw new InvalidArgumentException($e->getMessage());
    }
    
    // Make sure both are in the same CO. We don't do a role check since a person
    // can be a CO admin over someone without any roles (unlike a COU admin).
    
    return (($coId == $sCoId) && $this->isCoAdmin($coPersonId));
  }
  
  /**
   * Determine if a CO Person is a member of a CO Group.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @return Boolean True if the CO Person is a CO Administrator, false otherwise
   */
  
  public function isCoGroupMember($coPersonId, $coGroupId) {
    return $this->cachedGroupCheck($coPersonId, "", "", $coGroupId);
  }
  
  /**
   * Determine if a CO Person is a CO or COU Administrator.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID that CO Person is an Admin for, or null for any CO
   * @return Boolean True if the CO Person is a CO or COU Administrator, false otherwise
   */
  
  public function isCoOrCouAdmin($coPersonId, $coId=null) {
    // A person is a CO Admin if they are a member of the "admin" group for the specified CO.
    // A person is a COU Admin if they are a member of an "admin:*" group within the specified CO.
    
    global $group_sep;
    
    if($coId) {
      // First check that $coPersonId is in $coId
      
      if(!$this->isCoPerson($coPersonId, $coId)) {
        return false;
      }
    }
    
    // For code readability, we do this as separate checks rather than passing an OR
    // condition to cachedGroupCheck(). This may result in two DB calls, but it may not
    // since chances are we've already cached the results to isCoAdmin() (if we're being
    // called from CoEnrollmentFlow::authorize(), at least).
    
    // XXX define "admin" somewhere? CO-457
    if($this->cachedGroupCheck($coPersonId, "admin")) {
      return true;
    }
    
    return $this->cachedGroupCheck($coPersonId, "admin" . $group_sep . "%", "LIKE");
  }
  
  /**
   * Determine if a CO Person is a CO or COU Administrator for another CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID of potential CO(U) Admin
   * @param  Integer CO Person ID of subject
   * @param  Integer CO ID
   * @return Boolean True if the CO Person is a CO or COU Administrator for the subject, false otherwise
   */
  
  public function isCoOrCouAdminForCoPerson($coPersonId, $subjectCoPersonId) {
    if($this->isCoAdminForCoPerson($coPersonId, $subjectCoPersonId)) {
      return true;
    } else {
      return $this->isCouAdminForCoPerson($coPersonId, $subjectCoPersonId);
    }
  }
  
  /**
   * Determine if a CO Person is a CO(U) Administrator for a CO Person Role.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID of potential CO(U) Admin
   * @param  Integer CO Person Role ID of subject
   * @return Boolean True if the CO Person is a CO(U) Administrator for the subject, false otherwise
   */
   
  public function isCoOrCouAdminForCoPersonRole($coPersonId, $subjectCoPersonRoleId) {
    // Look up the CO Person ID for the subject and then hand off the request.
    
    $CoPersonRole = ClassRegistry::init('CoPersonRole');
    
    $args = array();
    $args['conditions']['CoPersonRole.id'] = $subjectCoPersonRoleId;
    $args['contain'] = false;
    
    $copr = $CoPersonRole->find('first', $args);
    
    if($copr && isset($copr['CoPersonRole']['co_person_id'])) {
      return $this->isCoOrCouAdminForCoPerson($coPersonId, $copr['CoPersonRole']['co_person_id']);
    } else {
      return false;
    }
  }
  
  /**
   * Determine if a CO Person is a CO(U) Administrator for an Org Identity.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID of potential CO(U) Admin
   * @param  Integer Org Identity ID of subject
   * @return Boolean True if the CO Person is a CO(U) Administrator for the subject, false otherwise
   */
   
  public function isCoOrCouAdminForOrgIdentity($coPersonId, $subjectOrgIdentityId) {
    // A person is an admin if org identities are pooled or if the subject and the CO person
    // are in the CO. First check that they're even an admin at all.
    
    if($this->isCoAdmin($coPersonId)
       || $this->isCouAdmin($coPersonId)) {
      $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
      
      if($CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
        // All CO and COU Admins can manage all org identities
        
        return true;
      } else {
        // Is $subjectOrgIdentityId in $coPersonId's CO?
        
        $OrgIdentity = ClassRegistry::init('OrgIdentity');
        
        $subjectCoId = $OrgIdentity->field('co_id', array('OrgIdentity.id' => $subjectOrgIdentityId));
        
        if($subjectCoId && ($subjectCoId == $this->cachedCoIdLookup($coPersonId))) {
          return true;
        }
      }
    }
    
    return false;
  }
  
  /**
   * Determine if a person is in a CO. A person is a CO Person if they have at least one
   * valid role within the CO.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer CO ID
   * @param  Boolean Require active role
   * @return Boolean True if the person is in the CO, false otherwise
   */
  
  public function isCoPerson($coPersonId, $coId, $requireRole=true) {
    if($requireRole) {
      return $this->cachedPersonRoleCheck($coPersonId, $coId, null, true);
    } else {
      
    }
  }
  
  /**
   * Determine if a CO Person is a COU Administrator for a specified COU. Note this function
   * will return false if CO Person is a CO Administrator, but not a COU Administrator.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  Integer COU ID, or NULL to determine if a COU Admin for any COU
   * @return Boolean True if the CO Person is a COU Administrator for the specified COU, false otherwise
   */
  
  public function isCouAdmin($coPersonId, $couId=null) {
    // A person is a COU Admin if they are a member of the "admin:COU Name" group within the specified CO.
    
    global $group_sep;
    
    if($couId) {
      // We need to find the name of the COU first.
      
      $couName = "";
      
      if(isset($this->cache['cou'][$couId])) {
        $couName = $this->cache['cou'][$couId]['Cou']['name'];
      } else {
        $Cou = ClassRegistry::init('Cou');
        
        $args = array();
        $args['conditions']['Cou.id'] = $couId;
        $args['contain'] = false;
        
        $c = $Cou->find('first', $args);
        
        // Cache the results
        
        if(isset($c['Cou']['name'])) {
          $this->cache['cou'][$couId] = $c;
          $couName = $c['Cou']['name'];
        } else {
          return false;
        }
      }
      
      return $this->cachedGroupCheck($coPersonId, "admin" . $group_sep . $couName);
    } else {
      // We don't need to walk the tree since we only care if a person is a COU Admin
      // for *any* group, not which groups (which would require getting the child COUs).
      
      return $this->cachedGroupCheck($coPersonId, "admin" . $group_sep . "%", "LIKE");
    }
  }
  
  /**
   * Determine if a CO Person is a COU Administrator for another CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID of potential COU Admin
   * @param  Integer CO Person ID of subject
   * @return Boolean True if the CO Person is a COU Administrator for the subject, false otherwise
   * @throws InvalidArgumentException
   */
   
  public function isCouAdminForCoPerson($coPersonId, $subjectCoPersonId) {
    // Find the person's CO
    
    try {
      $coId = $this->cachedCoIdLookup($coPersonId);
    }
    catch(InvalidArgumentException $e) {
      throw new InvalidArgumentException($e->getMessage());
    }
    
    // Next, pull the COUs for which $coPersonId is a COU admin
    $adminCous = $this->couAdminFor($coPersonId);
    
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
  
  /**
   * Determine if a CO Person is can administer a CO Group.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID of potential admin
   * @param  Integer CO Group ID
   * @return Boolean True if the CO Person can administer the CO Group, false otherwise
   * @throws InvalidArgumentException
   */
  
  public function isGroupManager($coPersonId, $coGroupId) {
    // A person is a group manager if (1) they are an owner of the group or (2) they
    // are a CO admin for the CO of the group. Currently, we do not treat COU admins as
    // superusers for groups.
    
    if($this->cachedGroupCheck($coPersonId, "", "", $coGroupId, true)) {
      return true;
    }
    
    // Pull the CO Group CO ID, then see if $coPersonId is an admin
    
    $coId = $this->cachedCoIdLookupByCoGroup($coGroupId);
    
    return $this->isCoAdmin($coPersonId, $coId);
  }
}