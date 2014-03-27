<?php
/**
 * COmanage Registry CO People Controller
 *
 * Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoPeopleController extends StandardController {
  public $name = "CoPeople";
  
  public $helpers = array('Time');
  
  // When using additional models, we must also specify our own
  public $uses = array('CoPerson', 'CmpEnrollmentConfiguration');
  
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'PrimaryName.family' => 'asc',
      'PrimaryName.given' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  // For CO Person group renderings, we need all CoGroup data, so we need more recursion
  public $edit_recursion = 2;
  public $view_recursion = 2;
  // We also need Name on delete
  public $delete_recursion = 2;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $pool_org_identities set
   * - postcondition: $sponsors set
   *
   * @since  COmanage Registry v0.1
   */
  
  function beforeFilter() {
    // This controller may or may not require a CO, depending on how
    // the CMP Enrollment Configuration is set up. Check and adjust before
    // beforeFilter is called.
    
    // We need this to render links to the org identity (which may or may
    // not need the co id carried).
    
    $this->set('pool_org_identities', $this->CmpEnrollmentConfiguration->orgIdentitiesPooled());
    
    parent::beforeFilter();
  }

  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   * - postcondition: Set $sponsors
   *
   * @since  COmanage Registry v0.4
   */

  function beforeRender() {
    if(!$this->restful){
      // generate list of sponsors
      $this->set('sponsors', $this->CoPerson->sponsorList($this->cur_co['Co']['id']));
      
      // Determine if there are any Enrollment Flows for this CO and if so pass
      // them to the view. Currently, we don't check for COU-specific flows. 
      
      $args = array();
      $args['conditions']['CoEnrollmentFlow.co_id'] = $this->cur_co['Co']['id'];
      $args['contain'][] = false;
      
      $this->set('co_enrollment_flows', $this->Co->CoEnrollmentFlow->find('all', $args));
      
      // Determine if there are any identifier assignments for this CO.
      
      $args = array();
      $args['conditions']['CoIdentifierAssignment.co_id'] = $this->cur_co['Co']['id'];
      $args['contain'][] = false;
      
      $this->set('co_identifier_assignments', $this->Co->CoIdentifierAssignment->find('all', $args));
      
      // Determine if there are any terms and conditions for this CO.
      
      $args = array();
      $args['conditions']['CoTermsAndConditions.co_id'] = $this->cur_co['Co']['id'];
      $args['contain'][] = false;
      
      $this->set('vv_co_tandc_count', $this->Co->CoTermsAndConditions->find('count', $args));
    }
    
    parent::beforeRender();
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.8.5
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId() {
    if($this->action == "invite"
       && !empty($this->request->params['named']['orgidentityid'])) {
      $coId = $this->CoPerson->CoOrgIdentityLink->OrgIdentity->field('co_id',
                                                                     array('id' => $this->request->params['named']['orgidentityid']));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.org_identities.1'),
                                                      Sanitize::html($this->request->params['named']['orgidentityid']))));
      }
    }
    
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    // Check if the target person is a member of any COU that the current user
    // does not have permissions over. If so, fail.
    
    foreach($curdata['CoPersonRole'] as $pr)
    {
      if(isset($pr['cou_id']) && $pr['cou_id'] != "")
      {
        if(!isset($this->viewVars['permissions']['cous'][ $pr['cou_id'] ]))
        {
          // Find the name of the COU
          
          $couname = "(?)";
          
          foreach($this->cur_co['Cou'] as $cou) {
            if($cou['id'] == $pr['cou_id']) {
              $couname = $cou['name'];
              break;
            }
          }
          
          if($this->restful) {
            // At the moment, we're unlikely to get here since REST authz is
            // currently all or nothing.
            
            $this->restResultHeader(499, "CouPerson Exists In Unowned COU");
          }
          
          $this->Session->setFlash(_txt('er.coumember',
                                   array(generateCn($curdata['PrimaryName']),
                                         Sanitize::html($couname))),
                                   '', array(), 'error');
          
          return false;
        }
      }
    }
    
    // Check that the target person has no CO Person Roles. We do this check
    // (which makes the above check slightly redundant) for two reasons:
    // (1) to make it harder to accidentally blow away a record
    // (2) the cascading delete will fail because we don't dynamically bind
    //     Co#ExtendedAttributes here (but CoPersonRole does)
    
    if(!empty($curdata['CoPersonRole'])) {
      if($this->restful)
        $this->restResultHeader(499, "CoPersonRole Exists");
      else
        $this->Session->setFlash(_txt('er.copr.exists',
                                      array(generateCn($curdata['PrimaryName']))),
                                 '', array(), 'error');
      
      return false;
    }
    
    return true;
  }

  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */

  function checkWriteDependencies($reqdata, $curdata = null) {
    // Check that an org identity being added is not already a member of the CO.
    // (A person can't be added to the same CO twice... that's what Person Roles
    // are for.) Note the REST check is in co_org_identity_links_controller.
    
    if(!$this->restful
       && (!$curdata
           ||
           ($reqdata['CoOrgIdentityLink'][0]['org_identity_id']
            != $curdata['CoOrgIdentityLink'][0]['org_identity_id'])))
    {
      if($this->CoPerson->orgIdIsCoPerson($this->cur_co['Co']['id'],
                                          $reqdata['CoOrgIdentityLink'][0]['org_identity_id']))
      {
        $this->Session->setFlash(_txt('er.cop.member',
                                 array(generateCn($this->data['PrimaryName']),
                                       $this->cur_co['Co']['name'])),
                                 '', array(), 'error');
        
        $redirect['controller'] = 'co_people';
        $redirect['action'] = 'index';
        $redirect['co'] = $this->cur_co['Co']['id'];
        $this->redirect($redirect);
        
        // We won't actually accomplish anything with this return
        return(false);
      }
    }
    
    return true;      
  }

  /**
   * Retrieve CO and Org attributes for comparison.
   * - precondition: <id> must exist
   * - postcondition: $<object>s set (with one member) if found
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.1
   * @param  Integer CO Person identifier
   */
  
  function compare($id) {
    // This is pretty similar to the standard view or edit methods.
    // We'll just retrieve and set the Org Person, then invoke view.
    // (We could invoke edit instead, presumably.)
    
    $orgp = $this->CoPerson->CoOrgIdentityLink->OrgIdentity->find("all",
                                                                  array("conditions" =>
                                                                        array('CoOrgIdentityLink.co_person_id' => $id),
                                                                        "joins" =>
                                                                        array(array('table' => 'co_org_identity_links',
                                                                                    'alias' => 'CoOrgIdentityLink',
                                                                                    'type' => 'INNER',
                                                                                    'conditions' => array('OrgIdentity.id=CoOrgIdentityLink.org_identity_id')))));
    
    if(!empty($orgp))
    {
      $this->set("org_identities", $orgp);
      
      $this->view($id);
    }
  }

  /**
   * Expunge (delete with intelligent clean up) a CO Person.
   * - precondition: <id> must exist
   * - postcondition: $<object>s set (with one member) if found
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.8.5
   * @param  Integer CO Person identifier
   */
  
  public function expunge($id) {
    if(!$this->restful) {
      if($this->request->is('get')) {
        $coperson = $this->CoPerson->findForExpunge($id);
        
        if(!empty($coperson)) {
          // Populate data for the view
          $this->set('vv_co_person', $coperson);
          $this->set('title_for_layout', _txt('op.expunge-a', array(generateCn($coperson['PrimaryName']))));
        } else {
          $this->Session->setFlash(_txt('er.cop.unk-a', array($id)), '', array(), 'error');
          $this->performRedirect();
        }
      } else {
        // Perform the expunge
        
        try {
          $this->CoPerson->expunge($id, $this->Session->read('Auth.User.co_person_id'));
          $this->Session->setFlash(_txt('rs.expunged'), '', array(), 'success');
        }
        catch(Exception $e) {
          $this->Session->setFlash($e->getMessage(), '', array(), 'error');
        }
        
        $this->performRedirect();
      }
    }
    // XXX else we don't currently support REST
  }
  
  /**
   * Generate a display key to be used in messages such as "Item Added".
   *
   * @since  COmanage Registry v0.1
   * @param  Array A cached object (eg: from prior to a delete)
   * @return string A string to be included for display.
   */
 
  function generateDisplayKey($c = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;

    if(isset($c[$req][$model->displayField]))
      return($c[$req][$model->displayField]);
    elseif(isset($this->request->data['PrimaryName']))
      return(generateCn($this->request->data['PrimaryName']));
    elseif(isset($c['PrimaryName']))
      return(generateCn($c['PrimaryName']));
    else
      return("(?)");
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
        $this->CoPerson->HistoryRecord->record($this->CoPerson->id,
                                               null,
                                               (isset($newdata['CoOrgIdentityLink'][0]['org_identity_id'])
                                               ? $newdata['CoOrgIdentityLink'][0]['org_identity_id'] : null),
                                               $this->Session->read('Auth.User.co_person_id'),
                                               ActionEnum::CoPersonAddedManual);
        
        if(!$this->restful) {
          // Add a record indicating the link took place
          $this->CoPerson->HistoryRecord->record($this->CoPerson->id,
                                                 null,
                                                 $newdata['CoOrgIdentityLink'][0]['org_identity_id'],
                                                 $this->Session->read('Auth.User.co_person_id'),
                                                 ActionEnum::CoPersonOrgIdLinked);
        }
        break;
      case 'delete':
        // We don't handle delete since the CO person and its associated history
        // is about to be deleted
        break;
      case 'edit':
        $this->CoPerson->HistoryRecord->record($this->CoPerson->id,
                                               null,
                                               $newdata['CoOrgIdentityLink'][0]['org_identity_id'],
                                               $this->Session->read('Auth.User.co_person_id'),
                                               ActionEnum::CoPersonEditedManual,
                                               _txt('en.action', null, ActionEnum::CoPersonEditedManual) . ": " .
                                               $this->changesToString($newdata, $olddata, array('CoPerson', 'PrimaryName')));
        break;
    }
    
    return true;
  }
  
  /**
   * Obtain all CO People, or perform a match
   *
   * @since  COmanage Registry v0.5
   */

  public function index() {
    // We need to check if we're being asked to do a match via the REST API, and
    // if so dispatch it. Otherwise, just invoke the standard behavior.
    
    if($this->restful
       && (isset($this->request->query['given'])
           || isset($this->request->query['family']))) {
      $this->match();
    } else {
      // Set containable behavior for Paginator since parent will call
      // Paginator->paginate('CoPerson') and the view for index does not need
      // all the information returned.
      //
      // Fixes CO-262 https://bugs.internet2.edu/jira/browse/CO-262
      $this->paginate['contain'] = array('Co.id', 'PrimaryName', 'EmailAddress', 'CoInvite.CoPetition', 'CoPersonRole');

      parent::index();
      // Set page title
      $this->set('title_for_layout', _txt('fd.people', array($this->cur_co['Co']['name'])));
    }
  }
  
  /**
   * Invite the person identified by the Org Identity to a CO.
   * - precondition: orgidentity and co set in $this->request->params
   * - postcondition: $co_people set
   * - postcondition: ID of CO to invite to
   *
   * @since  COmanage Registry v0.1
   */
  
  function invite() {
    $orgp = $this->CoPerson->CoOrgIdentityLink->OrgIdentity->findById($this->request->params['named']['orgidentityid']);
    
    if(!empty($orgp))
    {
      if(!$this->restful)
      {
        // Set page title
        $this->set('title_for_layout', _txt('op.inv-a', array(generateCn($orgp['PrimaryName']))));
      }

      // Construct a CoPerson from the OrgIdentity.  We only populate defaulted values.
      
      $cop['PrimaryName'] = $orgp['PrimaryName'];
      $cop['CoOrgIdentityLink'][0]['org_identity_id'] = $orgp['OrgIdentity']['id'];
      
      $this->set('co_people', array(0 => $cop));
    }
    else
    {
      $this->Session->setFlash(_txt('op.orgp-unk-a', array($this->request->params['named']['orgidentityid'])), '', array(), 'error');
      $this->redirect(array('action' => 'index', 'co' => $this->cur_co['Co']['id']));
    }
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Is this our own record?
    $self = false;
    
    if($roles['comember'] && $roles['copersonid'] &&
       ((isset($this->request->params['pass'][0]) && ($roles['copersonid'] == $this->request->params['pass'][0]))
        ||
       ($this->action == 'editself' && isset($roles['copersonid'])))) {
      $self = true;
    }
    
    // Is this a record we can manage?
    $managed = false;
    
    if(isset($roles['copersonid'])
       && $roles['copersonid']
       && isset($this->request->params['pass'][0])
       && ($this->action == 'compare'
           || $this->action == 'delete'
           || $this->action == 'edit'
           || $this->action == 'expunge'
           || $this->action == 'view')) {
      $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                        $this->request->params['pass'][0]);
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new CO Person?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
    $p['enroll'] = $p['add'];
    // Via invite?
    $p['invite'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
    
    // Compare CO attributes and Org attributes?
    $p['compare'] = ($roles['cmadmin']
                     || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                     || $self);
    
    // Delete an existing CO Person?
    // A COU admin should be able to delete a CO Person, but not if they have any roles
    // associated with a COU the admin isn't responsible for. We'll catch that in
    // checkDeleteDependencies.
    $p['delete'] = ($roles['cmadmin']
                    || ($managed && ($roles['coadmin'] || $roles['couadmin'])));
    
    // Expunge is basically delete, but since it clears history we restrict it to coadmins.
    // (Before expanding this to COU Admins, read the note in CoPerson:expunge regarding
    // checkDeleteDependencies.)
    $p['expunge'] = ($roles['cmadmin'] || ($managed && $roles['coadmin']));
    
    // Edit an existing CO Person?
    $p['edit'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $self);
    
    // Are we allowed to edit our own record?
    // If we're an admin, we act as an admin, not self.
    $p['editself'] = $self && !$roles['cmadmin'] && !$roles['coadmin'] && !$roles['couadmin'];
    
    // View all existing CO People (or a COU's worth)?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
    $p['search'] = $p['index'];

    
    if($this->action == 'index' && $p['index']) {
      // For rendering index, we currently assume that anyone who can view the
      // index can manipulate all records. This is fine for CMP and CO admins,
      // but a COU Admin can't edit role data for which they are not the admin.
      // (See also CO-505.)
      
      // It might be nice to pull all the people in the COU and pass a list
      // of CO Role IDs, but that would require pulling all the person role
      // records twice (again later in StandardController::index()). Since
      // $p['admincous'] has the appropriate COUs listed, will let the view
      // do a bit of work when rendering.
      
      // These permissions are person-level, and are probably not exactly right.
      // Specifically, delete could be problematic since a COU admin can't
      // delete a person with a COU role that the admin doesn't manage.
      // For now, we'll catch that in checkDeleteDependencies, and let the view
      // worry about what to render by checking the list of COUs.
      
      $p['compare'] = true;
      $p['delete'] = true;
      $p['edit'] = true;
      $p['view'] = true;
    }
    
    // Match against existing CO People?
    // Note this same permission exists in CO Petitions
    
    // Some operations are authorized according to the flow configuration.
    $flowAuthorized = false;
    
    // If an enrollment flow was specified, check the authorization for that flow
    
    $p['match'] = false;
    
    if(isset($this->request->named['coef'])) {
      $flowAuthorized = $this->CoPerson->Co->CoPetition->CoEnrollmentFlow->authorizeById($this->request->named['coef'],
                                                                                         $roles['copersonid'],
                                                                                         $this->Role);
      
      $p['match_policy'] = $this->CoPerson->Co->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                                                    array('CoEnrollmentFlow.id' => $this->request->named['coef']));
      $p['match'] = ($flowAuthorized &&
                     ($p['match_policy'] == EnrollmentMatchPolicyEnum::Advisory
                      || $p['match_policy'] == EnrollmentMatchPolicyEnum::Automatic));
    }
    
    // (Re)provision an existing CO Person?
    $p['provision'] = ($roles['cmadmin']
                       || ($managed && ($roles['coadmin'] || $roles['couadmin'])));
    
    // View an existing CO Person?
    $p['view'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $self);
    
    // Determine which COUs a person can manage.
    
    if($roles['cmadmin'] || $roles['coadmin'])
      $p['cous'] = $this->CoPerson->CoPersonRole->Cou->allCous($this->cur_co['Co']['id']);
    elseif(!empty($roles['admincous']))
      $p['cous'] = $roles['admincous'];
    else
      $p['cous'] = array();
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a match against existing records.
   *
   * @since  COmanage Registry v0.5
   */
  
  function match() {
    $criteria['Name.given'] = "";
    $criteria['Name.family'] = "";
    
    if($this->restful) {
      if(isset($this->request->query['given'])) {
        $criteria['Name.given'] = $this->request->query['given'];
      }
      if(isset($this->request->query['family'])) {
        $criteria['Name.family'] = $this->request->query['family'];
      }
      
      // XXX We didn't validate CO ID exists here. (This is normally done by
      // StandardController.)
      
      $this->set('co_people',
                 $this->convertResponse($this->CoPerson->match($this->request->query['coid'],
                                                               $criteria)));
    } else {
      if(isset($this->params['named']['given'])) {
        $criteria['Name.given'] = $this->params['named']['given'];
      }
      if(isset($this->params['named']['family'])) {
        $criteria['Name.family'] = $this->params['named']['family'];
      }
      
      $this->set('matches', $this->CoPerson->match($this->cur_co['Co']['id'], $criteria));
    }
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.8
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    $pagcond = array();
    
    // Set page title
    $this->set('title_for_layout', _txt('ct.co_people.se'));

    // Use server side pagination
    
    if($this->requires_co) {
      $pagcond['Co.id'] = $this->cur_co['Co']['id'];
    }

    // Filter by given name
    if(!empty($this->params['named']['Search.givenName'])) {
      $searchterm = $this->params['named']['Search.givenName'];
      $pagcond['PrimaryName.given LIKE'] = "%$searchterm%";
    }

    // Filter by Family name
    if(!empty($this->params['named']['Search.familyName'])) {
      $searchterm = $this->params['named']['Search.familyName'];
      $pagcond['PrimaryName.family LIKE'] = "%$searchterm%";
    }

    // Filter by status
    if(!empty($this->params['named']['Search.status'])) {
      $searchterm = $this->params['named']['Search.status'];
      $pagcond['CoPerson.status'] = $searchterm;
    }
 
    return($pagcond);
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.1
   */
  
  function performRedirect() {
    // On add, redirect to send view for notification of invite
          
    if($this->action == 'add')
      $this->redirect(array('controller' => 'co_invites',
                            'action' => 'send',
                            'copersonid' => $this->CoPerson->id,
                            'co' => $this->cur_co['Co']['id']));
    else
      parent::performRedirect();
  }
  
  /**
   * Obtain provisioning status for CO Person
   *
   * @param  integer CO Person ID
   * @since  COmanage Registry v0.8
   */
  
  function provision($id) {
    if(!$this->restful) {
      // Pull some data for the view to be able to render
      $this->set('co_provisioning_status', $this->CoPerson->provisioningStatus($id));
      
      $args = array();
      $args['conditions']['CoPerson.id'] = $id;
      $args['contain'][] = 'PrimaryName';
      
      $this->set('co_person', $this->CoPerson->find('first', $args));
    }
  }
  
  /**
   * Regenerate a form after validation/save fails.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.4
   */
  
  function regenerateForm() {
    // co_people/add needs to go back to co_people/invite
    
    if($this->request->params['action'] == 'add') {
      $r['controller'] = 'co_people';
      $r['action'] = 'invite';
      if(isset($this->request->data['CoOrgIdentityLink'][0]['org_identity_id'])) {
        $r['orgidentityid'] = Sanitize::html($this->request->data['CoOrgIdentityLink'][0]['org_identity_id']);
      }
      $r['co'] = $this->cur_co['Co']['id'];
      
      $this->redirect($r);
    }
    
    return true;
  }

  /**
   * Insert search parameters into URL for index.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.8
   */
  function search() {
    // the page we will redirect to
    $url['action'] = 'index';
     
    // build a URL will all the search elements in it
    // the resulting URL will be 
    // example.com/registry/co_people/index/Search.givenName:albert/Search.familyName:einstein
    foreach ($this->data['Search'] as $field=>$value){
      if(!empty($value))
        $url['Search.'.$field] = $value; 
    }
    // Insert CO into URL
    $url['co'] = $this->cur_co['Co']['id'];

    // redirect the user to the url
    $this->redirect($url, null, true);
  }
}
