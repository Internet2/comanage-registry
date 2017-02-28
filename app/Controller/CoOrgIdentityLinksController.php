<?php
/**
 * COmanage Registry CO Org Identity Link Controller
 *
 * Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
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
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
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
      $this->Api->restResultHeader(403, "CoPerson Does Not Exist");
      return false;
    }
    
    $this->CoOrgIdentityLink->CoPerson->contain();
    $coPerson = $this->CoOrgIdentityLink->CoPerson->findById($reqdata['CoOrgIdentityLink']['co_person_id']);
    
    if(empty($coPerson))
    {
      $this->Api->restResultHeader(403, "CoPerson Does Not Exist");
      return false;
    }
    
    if(empty($reqdata['CoOrgIdentityLink']['org_identity_id']))
    {
      $this->Api->restResultHeader(403, "OrgIdentity Does Not Exist");
      return false;
    }
    
    // Can't contain OrgIdentity completely since Name is used for display
    $this->CoOrgIdentityLink->OrgIdentity->contain('PrimaryName');
    $orgIdentity = $this->CoOrgIdentityLink->OrgIdentity->findById($reqdata['CoOrgIdentityLink']['org_identity_id']);
    
    if(empty($orgIdentity))
    {
      $this->Api->restResultHeader(403, "OrgIdentity Does Not Exist");
      return false;
    }
    
    if($this->request->is('restful') || $this->action == 'add') {
      // Check that an org identity being added is not already a member of the CO.
      // (A person can't be added to the same CO twice... that's what Person Roles
      // are for.) Note the UI check is in co_people_controller.
      
      // Also, for a relink (only possible via UI) old and new org identities will
      // be the same.
      
      if($this->CoOrgIdentityLink->CoPerson->orgIdIsCoPerson($coPerson['CoPerson']['co_id'],
                                                             $orgIdentity['OrgIdentity']['id']))
      {
        $this->Api->restResultHeader(403, "OrgIdentity Already Linked");
        return false;
      }
    }
    
    return true;
  }
 
  /**
   * Generate a display key to be used in messages such as "Item Added".
   *
   * @since  COmanage Registry v0.9.1
   * @param  Array A cached object (eg: from prior to a delete)
   * @return string A string to be included for display.
   */
 
  public function generateDisplayKey($c = null) {
    // A message like "Pat Lee deleted" probably isn't right, something like
    // "Link deleted" is better.
    
    return _txt('ct.co_org_identity_links.1');
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
        // An edit is a relink, or rather an unlink followed by a link.
        $this->CoOrgIdentityLink->CoPerson->HistoryRecord->record($olddata['CoOrgIdentityLink']['co_person_id'],
                                                                  null,
                                                                  $olddata['CoOrgIdentityLink']['org_identity_id'],
                                                                  $this->Session->read('Auth.User.co_person_id'),
                                                                  ActionEnum::CoPersonOrgIdUnlinked);
        $this->CoOrgIdentityLink->CoPerson->HistoryRecord->record($newdata['CoOrgIdentityLink']['co_person_id'],
                                                                  null,
                                                                  $newdata['CoOrgIdentityLink']['org_identity_id'],
                                                                  $this->Session->read('Auth.User.co_person_id'),
                                                                  ActionEnum::CoPersonOrgIdLinked);
        break;
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
    
    // Is this a record we can manage?
    $managed = false;
    
    if(isset($roles['copersonid']) && $roles['copersonid']) {
      if(isset($this->request->params['pass'][0])) {   // Edit, delete, view
        // Pull the link and see if copersonid can manage both the org identity and
        // the CO Person.
        
        $args = array();
        $args['conditions']['CoOrgIdentityLink.id'] = $this->request->params['pass'][0];
        $args['contain'] = false;
        
        $lnk = $this->CoOrgIdentityLink->find('first', $args);
        
        if(!empty($lnk)) {
          $managed = $this->Role->isCoAdminForCoPerson($roles['copersonid'],
                                                       $lnk['CoOrgIdentityLink']['co_person_id'])
                     && $this->Role->isCoOrCouAdminForOrgIdentity($roles['copersonid'],
                                                                  $lnk['CoOrgIdentityLink']['org_identity_id']);
        }
        
        if(!empty($this->request->data['CoOrgIdentityLink'])) {   // Edit
          $managed = $managed
                     && $this->Role->isCoAdminForCoPerson($roles['copersonid'],
                                                          $this->request->data['CoOrgIdentityLink']['co_person_id'])
                     && $this->Role->isCoOrCouAdminForOrgIdentity($roles['copersonid'],
                                                                  $this->request->data['CoOrgIdentityLink']['org_identity_id']);
        }
      } elseif(!empty($this->request->data['CoOrgIdentityLink'])) {   // Add
        $managed = $this->Role->isCoAdminForCoPerson($roles['copersonid'],
                                                     $this->request->data['CoOrgIdentityLink']['co_person_id'])
                   && $this->Role->isCoOrCouAdminForOrgIdentity($roles['copersonid'],
                                                                $this->request->data['CoOrgIdentityLink']['org_identity_id']);
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Person Source?
    $p['add'] = ($roles['cmadmin']
                 || ($managed && $roles['coadmin']));
    
    // Delete an existing Person Source?
    $p['delete'] = ($roles['cmadmin']
                    || ($managed && $roles['coadmin']));
    
    // Edit an existing Person Source?
    $p['edit'] = ($roles['cmadmin']
                  || ($managed && $roles['coadmin']));
    
    // View all existing Person Sources?
    $p['index'] = $roles['cmadmin'];
          
    // View an existing Person Source?
    $p['view'] = ($roles['cmadmin']
                  || ($managed && $roles['coadmin']));
    
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
    // An exception is unlinking or relinking an org identity, so we redirect
    // back to the CO Person.
    
    if(isset($this->CoOrgIdentityLink->data['CoPerson']['id'])) {
      $this->redirect(array('controller' => 'co_people',
                            'action' => 'canvas',
                            $this->CoOrgIdentityLink->data['CoPerson']['id']));
    } elseif(isset($this->request->data['CoOrgIdentityLink']['co_person_id'])) {
      // On relink we redirect to the new CO Person, partly because it's easier for
      // us to access that co person id and partly because that's where we pushed the
      // identity to.
      $this->redirect(array('controller' => 'co_people',
                            'action' => 'canvas',
                            $this->request->data['CoOrgIdentityLink']['co_person_id']));
    } else {
      $this->redirect(array('controller' => 'co_people',
                            'action' => 'index',
                            'co' => filter_var($this->cur_co['Co']['id'],FILTER_SANITIZE_SPECIAL_CHARS)));
    }
  }
}
