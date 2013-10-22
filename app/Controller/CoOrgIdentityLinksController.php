<?php
/**
 * COmanage Registry CO Org Identity Link Controller
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoOrgIdentityLinksController extends StandardController {
 // Class name, used by Cake
  public $name = "CoOrgIdentityLinks";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'id' => 'asc'
    )
  );
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.2
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    // Check that the IDs (CO Person, Org Person) provided point to existing entities.
    
    if(empty($reqdata['CoOrgIdentityLink']['co_person_id']))
    {
      $this->restResultHeader(403, "CoPerson Does Not Exist");
      return(false);
    }
    
    $this->CoOrgIdentityLink->CoPerson->contain();
    $coPerson = $this->CoOrgIdentityLink->CoPerson->findById($reqdata['CoOrgIdentityLink']['co_person_id']);
    
    if(empty($coPerson))
    {
      $this->restResultHeader(403, "CoPerson Does Not Exist");
      return(false);
    }
    
    if(empty($reqdata['CoOrgIdentityLink']['org_identity_id']))
    {
      $this->restResultHeader(403, "OrgIdentity Does Not Exist");
      return(false);
    }
    
    // Can't contain OrgIdentity completely since Name is used for display
    $this->CoOrgIdentityLink->OrgIdentity->contain('PrimaryName');
    $orgIdentity = $this->CoOrgIdentityLink->OrgIdentity->findById($reqdata['CoOrgIdentityLink']['org_identity_id']);
    
    if(empty($orgIdentity))
    {
      $this->restResultHeader(403, "OrgIdentity Does Not Exist");
      return(false);
    }
    
    // Check that an org identity being added is not already a member of the CO.
    // (A person can't be added to the same CO twice... that's what Person Roles
    // are for.) Note the UI check is in co_people_controller.
    
    if($this->CoOrgIdentityLink->CoPerson->orgIdIsCoPerson($coPerson['CoPerson']['co_id'],
                                                           $orgIdentity['OrgIdentity']['id']))
    {
      $this->restResultHeader(403, "OrgIdentity Already Linked");
      return(false);
    }
    
    return(true);
  }
  
  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v0.7
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */
  
  public function generateHistory($action, $newdata, $olddata) {
    if($this->restful) {
      switch($action) {
        case 'add':
          // We try to record an Org Identity ID, but this will only exist for non-REST operations
          $this->CoOrgIdentityLink->CoPerson->HistoryRecord->record($newdata['CoOrgIdentityLink']['co_person_id'],
                                                                    null,
                                                                    $newdata['CoOrgIdentityLink']['org_identity_id'],
                                                                    $this->Session->read('Auth.User.co_person_id'),
                                                                    ActionEnum::CoPersonOrgIdLinked);
          break;
        case 'delete':
          // In most cases, unlinking preceeds deletion of an identity/person that will therefore
          // cause this history record to be deleted. However, if multiple identities are linked
          // to a person and one is deleted, we want to record that since it will probably stick around.
          $this->CoOrgIdentityLink->CoPerson->HistoryRecord->record($olddata['CoOrgIdentityLink']['co_person_id'],
                                                                    null,
                                                                    $olddata['CoOrgIdentityLink']['org_identity_id'],
                                                                    $this->Session->read('Auth.User.co_person_id'),
                                                                    ActionEnum::CoPersonOrgIdUnlinked);
          break;
        case 'edit':
          // As of v0.7, we don't really have a use case for editing a link.
          break;
      }
    }
    
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.2
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Person Source?
    $p['add'] = $roles['cmadmin'];
    
    // Delete an existing Person Source?
    $p['delete'] = $roles['cmadmin'];
    
    // Edit an existing Person Source?
    $p['edit'] = $roles['cmadmin'];
    
    // View all existing Person Sources?
    $p['index'] = $roles['cmadmin'];
          
    // View an existing Person Source?
    $p['view'] = $roles['cmadmin'];

    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.1
   */
  
  function performRedirect() {
    // Generally speaking, this controller isn't called via the web interface.
    // An exception is unlinking an org identity, so we redirect back to the CO Person.
    
    if(isset($this->CoOrgIdentityLink->data['CoPerson']['id'])) {
      $this->redirect(array('controller' => 'co_people',
                            'action' => 'edit',
                            $this->CoOrgIdentityLink->data['CoPerson']['id'],
                            'co' => Sanitize::html($this->cur_co['Co']['id'])));
    } else {
      $this->redirect(array('controller' => 'co_people',
                            'action' => 'index',
                            'co' => Sanitize::html($this->cur_co['Co']['id'])));
    }
  }
}
