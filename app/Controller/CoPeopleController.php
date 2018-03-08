<?php
/**
 * COmanage Registry CO People Controller
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoPeopleController extends StandardController {
  public $name = "CoPeople";
  
  public $helpers = array('Time', 'Permission');
  
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

  // We need Name and Person Role on delete
  public $delete_contains = array(
    'CoPersonRole',
    'Name',
    'PrimaryName'
  );
  
  // Use edit_contains to select the associated models we need for canvas.
  public $edit_contains = array(
    'CoGroupMember' => array('CoGroup'),
    'CoNsfDemographic',
    'CoOrgIdentityLink' => array('OrgIdentity' => array('Identifier', 'PrimaryName')),
    'CoPersonRole' => array('CoPetition', 'Cou', 'order' => 'CoPersonRole.ordr ASC'),
    // This deep nesting will allow us to display the source of the attribute
    'EmailAddress' => array('SourceEmailAddress' => array('OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource')))),
    'Identifier' => array('CoProvisioningTarget',
                          'SourceIdentifier' => array('OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource')))),
    'Name' => array('SourceName' => array('OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource')))),
    'PrimaryName',
    'SshKey',
    'Url' => array('SourceUrl' => array('OrgIdentity' => array('OrgIdentitySourceRecord' => array('OrgIdentitySource')))),
  );
  
  // We need various related models for index and search
  public $view_contains = array(
    'Co',
    'CoPersonRole' => array('CoPetition', 'Cou'),
    'EmailAddress',
    'Identifier',
    'Name',
    'PrimaryName',
    'Url'
  );
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $pool_org_identities set
   *
   * @since  COmanage Registry v0.1
   */
  
  public function beforeFilter() {
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
   *
   * @since  COmanage Registry v0.4
   */

  public function beforeRender() {
    if(!$this->request->is('restful')){
      // Determine if there are any Enrollment Flows for this CO and if so pass
      // them to the view. Currently, we don't check for COU-specific flows. 
      
      $args = array();
      $args['conditions']['CoEnrollmentFlow.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['CoEnrollmentFlow.status'] = EnrollmentFlowStatusEnum::Active;
      $args['contain'] = false;
      
      $this->set('co_enrollment_flows', $this->Co->CoEnrollmentFlow->find('all', $args));
      
      // Determine if there are any identifier assignments for this CO.
      
      $args = array();
      $args['conditions']['CoIdentifierAssignment.co_id'] = $this->cur_co['Co']['id'];
      $args['contain'] = false;
      
      $this->set('co_identifier_assignments', $this->Co->CoIdentifierAssignment->find('all', $args));
      
      // Determine if there are any terms and conditions for this CO.
      
      $args = array();
      $args['conditions']['CoTermsAndConditions.co_id'] = $this->cur_co['Co']['id'];
      $args['contain'] = false;
      
      $this->set('vv_co_tandc_count', $this->Co->CoTermsAndConditions->find('count', $args));
      
      // Show NSF Demographics?
      $this->set('vv_enable_nsf_demo', $this->Co->CoSetting->nsfDemgraphicsEnabled($this->cur_co['Co']['id']));
      
      // Mappings for extended types
      $this->set('vv_email_addresses_types', $this->CoPerson->EmailAddress->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_identifiers_types', $this->CoPerson->Identifier->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_urls_types', $this->CoPerson->Url->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_cop_name_types', $this->CoPerson->Name->types($this->cur_co['Co']['id'], 'type'));
      $this->set('vv_copr_affiliation_types', $this->CoPerson->CoPersonRole->types($this->cur_co['Co']['id'], 'affiliation'));
      
      // List of current COUs
      $this->set('vv_cous', $this->CoPerson->Co->Cou->allCous($this->cur_co['Co']['id']));
      
      // Are any authenticators defined for this CO?
      
      $args = array();
      $args['conditions']['Authenticator.co_id'] = $this->cur_co['Co']['id'];
      $args['contain'] = false;
      
      $this->set('vv_authenticator_count', $this->Co->Authenticator->find('count', $args));
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
  
  protected function calculateImpliedCoId($data = null) {
    if(($this->action == "invite"
        // The first pass through link will not include a CO Person ID, but the second will
        || ($this->action == "link" && empty($this->request->params['passed'][0])))
       && !empty($this->request->params['named']['orgidentityid'])) {
      if(isset($this->viewVars['pool_org_identities']) && $this->viewVars['pool_org_identities']) {
        // When org identities are pooled, accept the CO ID from the URL
        
        if(isset($this->request->params['named']['co'])) {
          return $this->request->params['named']['co'];
        } else {
          throw new InvalidArgumentException(_txt('er.co.specify'));
        }
      } else {
        // The CO ID is implied from the org identity
        $coId = $this->CoPerson->CoOrgIdentityLink->OrgIdentity->field('co_id',
                                                                       array('id' => $this->request->params['named']['orgidentityid']));
        
        if($coId) {
          return $coId;
        } else {
          throw new InvalidArgumentException(_txt('er.notfound',
                                                  array(_txt('ct.org_identities.1'),
                                                        filter_var($this->request->params['named']['orgidentityid'],FILTER_SANITIZE_SPECIAL_CHARS))));
        }
      }
    } elseif($this->action == "match"
             && !empty($this->request->params['named']['coef'])) {
      // Pull the CO from the Enrollment Flow
      
      $coId = $this->CoPerson->Co->CoEnrollmentFlow->field('co_id',
                                                           array('id' => $this->request->params['named']['coef']));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_enrollment_flows.1'),
                                                      filter_var($this->request->params['named']['coef'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    } elseif($this->action == "select"
             && !empty($this->request->params['named']['copetitionid'])) {
      // Pull the CO from the Petition
      
      $coId = $this->CoPerson->Co->CoPetition->field('co_id',
                                                     array('id' => $this->request->params['named']['copetitionid']));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_petitions.1'),
                                                      filter_var($this->request->params['named']['copetitionid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }
    
    return parent::calculateImpliedCoId();
  }

  /**
   * Generate the canvas view.
   * - precondition: <id> must exist
   * - postcondition: $<object>s set (with one member) if found
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v0.9
   * @param  Integer CO Person identifier
   */
  
  public function canvas($id) {
    // This is pretty similar to the standard view or edit methods.
    
    if(!$this->request->is('restful') && $this->request->is('get')) {
      $this->edit($id);
    }
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.1
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  public function checkDeleteDependencies($curdata) {
    // Check if the target person is a member of any COU that the current user
    // does not have permissions over. If so, fail.
    
    foreach($curdata['CoPersonRole'] as $pr)
    {
      if(isset($pr['cou_id']) && $pr['cou_id'] != "")
      {
        if(!isset($this->viewVars['permissions']['cous'][ $pr['cou_id'] ]))
        {
          // Find the name of the COU
          
          $couname = $this->CoPerson->Co->Cou->field('name', array('Cou.id' => $pr['cou_id']));
          
          if(!$couname) {
            $couname = "(?)";
          }
          
          if($this->request->is('restful')) {
            // At the moment, we're unlikely to get here since REST authz is
            // currently all or nothing.
            
            $this->Api->restResultHeader(499, "CouPerson Exists In Unowned COU");
          }
          
          $this->Flash->set(_txt('er.coumember',
                                 array(generateCn($curdata['PrimaryName']),
                                       filter_var($couname,FILTER_SANITIZE_SPECIAL_CHARS))),
                            array('key' => 'error'));
          
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
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(499, "CoPersonRole Exists");
      } else {
        $this->Flash->set(_txt('er.copr.exists',
                               array(generateCn($curdata['PrimaryName']))),
                          array('key' => 'error'));
      }
      
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

  public function checkWriteDependencies($reqdata, $curdata = null) {
    // Check that an org identity being added is not already a member of the CO.
    // (A person can't be added to the same CO twice... that's what Person Roles
    // are for.) Note the REST check is in co_org_identity_links_controller.
    
    if(!$this->request->is('restful')
       && $this->action != 'edit'
       && (!$curdata
           ||
           ($reqdata['CoOrgIdentityLink'][0]['org_identity_id']
            != $curdata['CoOrgIdentityLink'][0]['org_identity_id'])))
    {
      if($this->CoPerson->orgIdIsCoPerson($this->cur_co['Co']['id'],
                                          $reqdata['CoOrgIdentityLink'][0]['org_identity_id']))
      {
        $this->Flash->set(_txt('er.cop.member',
                               array(generateCn($this->data['PrimaryName']),
                                     $this->cur_co['Co']['name'])),
                          array('key' => 'error'));
        
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
  
  public function compare($id) {
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
    if(!$this->request->is('restful')) {
      if($this->request->is('get')) {
        $coperson = $this->CoPerson->findForExpunge($id);
        
        if(!empty($coperson)) {
          // Populate data for the view
          $this->set('vv_co_person', $coperson);
          $this->set('title_for_layout', _txt('op.expunge-a', array(generateCn($coperson['PrimaryName']))));
        } else {
          $this->Flash->set(_txt('er.cop.unk-a', array($id)), array('key' => 'error'));
          $this->performRedirect();
        }
      } else {
        // Perform the expunge
        
        try {
          $this->CoPerson->expunge($id, $this->Session->read('Auth.User.co_person_id'));
          $this->Flash->set(_txt('rs.expunged'), array('key' => 'success'));
        }
        catch(Exception $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
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
 
  public function generateDisplayKey($c = null) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    
    if(isset($c[$req][$model->displayField])) {
      return $c[$req][$model->displayField];
    } elseif(isset($this->request->data['PrimaryName'])) {
      return generateCn($this->request->data['PrimaryName']);
    } elseif(isset($c['PrimaryName'])) {
      return generateCn($c['PrimaryName']);
    } elseif($this->action == 'edit') {
      // Pull the PrimaryName (we're probably here from an edit directly on canvas)
      $args = array();
      $args['conditions']['CoPerson.id'] = $this->request->data['CoPerson']['id'];
      $args['contain'][] = 'PrimaryName';
      
      $p = $this->CoPerson->find('first', $args);
      
      if($p) {
        return generateCn($p['PrimaryName']);
      }
    }
    
    return "(?)";
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
        
        if(!$this->request->is('restful')) {
          // Add a record indicating the link took place
          $this->CoPerson->HistoryRecord->record($this->CoPerson->id,
                                                 null,
                                                 $newdata['CoOrgIdentityLink'][0]['org_identity_id'],
                                                 $this->Session->read('Auth.User.co_person_id'),
                                                 ActionEnum::CoPersonOrgIdLinked);
        }
        break;
      case 'delete':
        $this->CoPerson->HistoryRecord->record($this->CoPerson->id,
                                               null,
                                               (isset($olddata['CoOrgIdentityLink'][0]['org_identity_id'])
                                               ? $olddata['CoOrgIdentityLink'][0]['org_identity_id'] : null),
                                               $this->Session->read('Auth.User.co_person_id'),
                                               ActionEnum::CoPersonDeletedManual);
        break;
      case 'edit':
        $this->CoPerson->HistoryRecord->record($this->CoPerson->id,
                                               null,
                                               (isset($newdata['CoOrgIdentityLink'][0]['org_identity_id'])
                                                ? $newdata['CoOrgIdentityLink'][0]['org_identity_id'] : null),
                                               $this->Session->read('Auth.User.co_person_id'),
                                               ActionEnum::CoPersonEditedManual,
                                               _txt('en.action', null, ActionEnum::CoPersonEditedManual) . ": " .
                                               $this->CoPerson->changesToString($newdata, $olddata, $this->cur_co['Co']['id']));
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
    
    if($this->request->is('restful')
       && (isset($this->request->query['given'])
           || isset($this->request->query['family']))) {
      $this->match();
    } else {
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
  
  public function invite() {
    $args = array();
    $args['conditions']['OrgIdentity.id'] = $this->request->params['named']['orgidentityid'];
    $args['contain'] = array('PrimaryName');
    
    $orgp = $this->CoPerson->CoOrgIdentityLink->OrgIdentity->find('first', $args);
    
    if(!empty($orgp))
    {
      if(!$this->request->is('restful')) {
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
      $this->Flash->set(_txt('op.orgp-unk-a', array($this->request->params['named']['orgidentityid'])), array('key' => 'error'));
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
  
  public function isAuthorized() {
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
       && ($this->action == 'canvas'
           || $this->action == 'compare'
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
    
    // Assign (autogenerate) Identifiers? (Same logic is in IdentifiersController)
    $p['assign'] = ($roles['cmadmin']
                    || ($managed && ($roles['coadmin'] || $roles['couadmin'])));
    
    // Access the canvas for a CO Person? (Basically 'view' but with links)
    $p['canvas'] = ($roles['cmadmin']
                    || ($roles['coadmin'] || $roles['couadmin'])
                    || $self);
    
    // Compare CO attributes and Org attributes?
    $p['compare'] = ($roles['cmadmin']
                     || ($roles['coadmin'] || $roles['couadmin'])
                     || $self);
    
    // Delete an existing CO Person?
    // A COU admin should be able to delete a CO Person, but not if they have any roles
    // associated with a COU the admin isn't responsible for. We'll catch that in
    // checkDeleteDependencies.
    $p['delete'] = ($roles['cmadmin']
                    || ($roles['coadmin'] || $roles['couadmin']));
    
    // Expunge is basically delete, but since it clears history we restrict it to coadmins.
    // (Before expanding this to COU Admins, read the note in CoPerson:expunge regarding
    // checkDeleteDependencies.)
    $p['expunge'] = ($roles['cmadmin'] || ($managed && $roles['coadmin']));
    
    // Edit an existing CO Person? This allows changes to cm_co_people.
    // For now, we restrict this to CO admins, though plausibly it should be
    // expanded to COU admins with a valid role to manage. Also, as of v1.0.0
    // self service timezone updating is permitted.
    $p['edit'] = ($roles['cmadmin']
                  || ($managed && $roles['coadmin'])
                  || ($self));
    
    // Are we allowed to edit our own record?
    // If we're an admin, we act as an admin, not self.
    $p['editself'] = $self && !$roles['cmadmin'] && !$roles['coadmin'] && !$roles['couadmin'];
    
    // View history? This correlates with HistoryRecordsController
    $p['history'] = ($roles['cmadmin']
                     || $roles['coadmin']
                     || ($managed && $roles['couadmin']));
    
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
    
    // View job history? This correlates with CoJobHistoryRecordsController
    $p['jobhistory'] = ($roles['cmadmin'] || $roles['admin']);
    
    // Link an Org Identity to a CO Person?
    $p['link'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Match against existing CO People?
    // Note this same permission exists in CO Petitions
    
    // Some operations are authorized according to the flow configuration.
    $flowAuthorized = false;
    
    // If an enrollment flow was specified, check the authorization for that flow
    
    $p['match'] = false;
    $p['select'] = false;
    
    if(isset($this->request->named['coef'])) {
      $flowAuthorized = $this->CoPerson->Co->CoPetition->CoEnrollmentFlow->authorizeById($this->request->named['coef'],
                                                                                         $roles['copersonid'],
                                                                                         $this->Session->read('Auth.User.username'),
                                                                                         $this->Role);
      
      $p['match_policy'] = $this->CoPerson->Co->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                                                    array('CoEnrollmentFlow.id' => $this->request->named['coef']));
      $p['match'] = (($roles['cmadmin'] || $flowAuthorized)
                     &&
                     ($p['match_policy'] == EnrollmentMatchPolicyEnum::Advisory
                      || $p['match_policy'] == EnrollmentMatchPolicyEnum::Automatic));
    }
    
    if(!empty($this->request->params['named']['copetitionid'])) {
      $ef = $this->CoPerson->Co->CoPetition->field('co_enrollment_flow_id',
                                                   array('CoPetition.id' => $this->request->params['named']['copetitionid']));
      
      if($ef) {
        $flowAuthorized = $this->CoPerson->Co->CoPetition->CoEnrollmentFlow->authorizeById($ef,
                                                                                           $roles['copersonid'],
                                                                                           $this->Session->read('Auth.User.username'),
                                                                                           $this->Role);
        
        $p['match_policy'] = $this->CoPerson->Co->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                                                      array('CoEnrollmentFlow.id' => $ef));
        
        // Select generates a complete people picker
        $p['select'] = (($roles['cmadmin'] || $flowAuthorized)
                        &&
                        $p['match_policy'] == EnrollmentMatchPolicyEnum::Select);
      }
    }
    
    // View notifications where CO person is subject?
    $p['notifications-subject'] = ($roles['cmadmin']
                                   || ($managed && ($roles['coadmin'] || $roles['couadmin'])));
    
    // View petitions?
    $p['petitions'] = ($roles['cmadmin']
                       || ($managed && ($roles['coadmin'] || $roles['couadmin'])));
    
    // (Re)provision an existing CO Person?
    $p['provision'] = ($roles['cmadmin']
                       || ($roles['coadmin'] || $roles['couadmin']));
    
    // Relink an Org Identity or Role to a different CO Person?
    $p['relink'] = $roles['cmadmin'] || $roles['coadmin'];
    
    if($self
       && (!$roles['cmadmin'] && !$roles['coadmin'] && !$roles['couadmin'])) {
      // Pull self service permissions if not an admin
      
      $p['selfsvc'] = $this->Co->CoSelfServicePermission->findPermissions($this->cur_co['Co']['id']);
    } else {
      $p['selfsvc'] = false;
    }
    
    // View an existing CO Person?
    $p['view'] = ($roles['cmadmin']
                  || ($roles['coadmin'] || $roles['couadmin'])
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
   * Identify the target CO Person for a linking operations. Linking takes an Org Identity
   * with no CO attachment and attaches it to the CO.
   *
   * @param Integer $copersonid CO Person ID to move record from
   * @since  COmanage Registry v0.9.1
   */

  public function link($copersonid=null) {
    if(!$this->request->is('restful')) {
      // We basically want the index behavior
      
      $this->index();
      
      // But we also need to pass a bit extra data (and also for the confirmation page)
      
      if(!empty($this->request->params['named']['orgidentityid'])) {
        $args = array();
        $args['conditions']['OrgIdentity.id'] = $this->request->params['named']['orgidentityid'];
        $args['contain'] = array('CoPetition', 'PrimaryName');
        
        $this->set('vv_org_identity', $this->CoPerson->CoOrgIdentityLink->OrgIdentity->find('first', $args));
        $this->set('title_for_layout', _txt('op.link'));
      }
      
      if(!empty($copersonid)) {
        $args = array();
        $args['conditions']['CoPerson.id'] = $copersonid;
        $args['contain'] = 'PrimaryName';
        
        $this->set('vv_co_person', $this->CoPerson->find('first', $args));
      }
    }
  }
  
  /**
   * Perform a match against existing records.
   *
   * @since  COmanage Registry v0.5
   */
  
  public function match() {
    $criteria['Name.given'] = "";
    $criteria['Name.family'] = "";
    
    if($this->request->is('restful')) {
      if(isset($this->request->query['given'])) {
        $criteria['Name.given'] = $this->request->query['given'];
      }
      if(isset($this->request->query['family'])) {
        $criteria['Name.family'] = $this->request->query['family'];
      }
      
      // XXX We didn't validate CO ID exists here. (This is normally done by
      // StandardController.)
      
      $this->set('co_people',
                 $this->Api->convertRestResponse($this->CoPerson->match($this->request->query['coid'],
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
  
  public function paginationConditions() {
    $pagcond = array();
    
    // Set page title
    $this->set('title_for_layout', _txt('fd.co_people.search'));

    // Use server side pagination
    
    if($this->requires_co) {
      $pagcond['conditions']['CoPerson.co_id'] = $this->cur_co['Co']['id'];
    }
    
    // Filtering by name operates using any name, so preferred or other names
    // can also be searched. However, filter by letter ("familyNameStart") only
    // works on PrimaryName so that the results match the index list.
    
    // Filter by Given name
    if(!empty($this->params['named']['Search.givenName'])) {
      $searchterm = strtolower($this->params['named']['Search.givenName']);
      // We set up LOWER() indices on these columns (CO-1006)
      $pagcond['conditions']['LOWER(Name.given) LIKE'] = "%$searchterm%";
    }
    
    // Filter by Family name
    if(!empty($this->params['named']['Search.familyName'])) {
      $searchterm = strtolower($this->params['named']['Search.familyName']);
      $pagcond['conditions']['LOWER(Name.family) LIKE'] = "%$searchterm%";
    }
    
    if(!empty($this->params['named']['Search.givenName'])
       || !empty($this->params['named']['Search.familyName'])) {
      $pagcond['joins'][] = array(
        'table' => 'names',
        'alias' => 'Name',
        'type' => 'INNER',
        'conditions' => array(
          'Name.co_person_id=CoPerson.id' 
        )
      );
    }
    
    // Filter by start of Primary Family name (starts with searchterm)
    if(!empty($this->params['named']['Search.familyNameStart'])) {
      $searchterm = strtolower($this->params['named']['Search.familyNameStart']);
      $pagcond['conditions']['LOWER(PrimaryName.family) LIKE'] = "$searchterm%";
    }
    
    // Filter by email address
    if(!empty($this->params['named']['Search.mail'])) {
      $searchterm = strtolower($this->params['named']['Search.mail']);
      $pagcond['conditions']['LOWER(EmailAddress.mail) LIKE'] = "%$searchterm%";
      $pagcond['joins'][] = array(
        'table' => 'email_addresses',
        'alias' => 'EmailAddress',
        'type' => 'INNER',
        'conditions' => array(
          'EmailAddress.co_person_id=CoPerson.id' 
        )
      );
      
      // See also the note below about searching org identities for identifiers.
    }
    
    // Filter by identifier
    if(!empty($this->params['named']['Search.identifier'])) {
      $searchterm = strtolower($this->params['named']['Search.identifier']);
      $pagcond['conditions']['LOWER(Identifier.identifier) LIKE'] = "%$searchterm%";
      $pagcond['joins'][] = array(
        'table' => 'identifiers',
        'alias' => 'Identifier',
        'type' => 'INNER',
        'conditions' => array(
          'Identifier.co_person_id=CoPerson.id' 
        )
      );
      
      // We also want to search on identifiers attached to org identities.
      // This requires a fairly complicated join that doesn't quite work right
      // and that Cake doesn't really support in our current model configuration.
      // This probably needs to be implemented as part of CO-819, or perhaps
      // using a custom paginator.
    }
    
    // Filter by status
    if(!empty($this->params['named']['Search.status'])) {
      $searchterm = $this->params['named']['Search.status'];
      $pagcond['conditions']['CoPerson.status'] = $searchterm;
    }
    
    // Filter by COU
    if(!empty($this->params['named']['Search.couid'])) {
      // If a CO Person has more than one role, this search will cause them go show up once
      // per role in the results (select co_people.id,co_person_roles.id where co_person_role.cou_id=#
      // will generate one row per co_person_role_id). In order to fix this, we can use
      // aggregate functions and grouping, like this:
      //      $pagcond['fields'] = array('CoPerson.id',
      //                                 'MIN(CoPersonRole.id)');
      //      $pagcond['group'] = array('CoPerson.id', 'Co.id', 'PrimaryName.family', 'PrimaryName.given');
      // This produces the correct results, however Cake then goes into an infinite loop
      // trying to pull some related data for the results. So for now, we just leave duplicates
      // in the search results.
      $pagcond['conditions']['CoPersonRole.cou_id'] = $this->params['named']['Search.couid'];
      $pagcond['joins'][] = array(
        'table' => 'co_person_roles',
        'alias' => 'CoPersonRole',
        'type' => 'INNER',
        'conditions' => array(
          'CoPersonRole.co_person_id=CoPerson.id' 
        )
      );
    }
    
    // We need to manually add this in for some reason. (It should have been
    // added automatically by Cake based on the CoPerson Model definition of
    // PrimaryName.)
    $pagcond['conditions']['PrimaryName.primary_name'] = true;
    
    return $pagcond;
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.1
   */
  
  public function performRedirect() {
    // On add, redirect to send view for notification of invite
          
    if($this->action == 'add') {
      $this->redirect(array('controller' => 'co_invites',
                            'action' => 'send',
                            'copersonid' => $this->CoPerson->id,
                            'co' => $this->cur_co['Co']['id']));
    } elseif($this->action == 'edit') {
      // Redirect to canvas
      $this->redirect(array('controller' => 'co_people',
                            'action' => 'canvas',
                            $this->CoPerson->id));
    } else {
      parent::performRedirect();
    }
  }
  
  /**
   * Obtain provisioning status for CO Person
   *
   * @param  integer CO Person ID
   * @since  COmanage Registry v0.8
   */
  
  public function provision($id) {
    if(!$this->request->is('restful')) {
      // Pull some data for the view to be able to render
      $this->set('co_provisioning_status', $this->CoPerson->provisioningStatus($id));
      
      $args = array();
      $args['conditions']['CoPerson.id'] = $id;
      $args['contain'] = array(
        'PrimaryName',
        'CoGroupMember',
        'CoOrgIdentityLink' => array('OrgIdentity' => array('OrgIdentitySourceRecord')),
        'Identifier'
      );
      
      $this->set('co_person', $this->CoPerson->find('first', $args));
      $this->set('title_for_layout',
                 _txt('fd.prov.status.for',
                      array(filter_var(generateCn($this->viewVars['co_person']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS))));
    }
  }
  
  /**
   * Regenerate a form after validation/save fails.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.4
   */
  
  public function regenerateForm() {
    // co_people/add needs to go back to co_people/invite
    
    if($this->request->params['action'] == 'add') {
      $r['controller'] = 'co_people';
      $r['action'] = 'invite';
      if(isset($this->request->data['CoOrgIdentityLink'][0]['org_identity_id'])) {
        $r['orgidentityid'] = filter_var($this->request->data['CoOrgIdentityLink'][0]['org_identity_id'],FILTER_SANITIZE_SPECIAL_CHARS);
      }
      $r['co'] = $this->cur_co['Co']['id'];
      
      $this->redirect($r);
    }
    
    return true;
  }
  
  /**
   * Identify the target CO Person for a relinking operations. Relinking takes an
   * Org Identity and moves it from one CO Person to another.
   *
   * @param Integer $copersonid CO Person ID to move record from
   * @since  COmanage Registry v0.9.1
   */

  public function relink($copersonid) {
    if(!$this->request->is('restful')) {
      // We basically want the index behavior
      
      $this->index();
      
      // But we also need to pass a bit extra data (and also for the confirmation page)
      
      if(!empty($this->request->params['named']['linkid'])) {
        $args = array();
        $args['conditions']['CoOrgIdentityLink.id'] = $this->request->params['named']['linkid'];
        $args['contain']['CoPerson'] = 'PrimaryName';
        $args['contain']['OrgIdentity'] = array('CoPetition', 'PrimaryName');
        
        $this->set('vv_co_org_identity_link', $this->CoPerson->CoOrgIdentityLink->find('first', $args));
      }
      
      if(!empty($this->request->params['named']['copersonroleid'])) {
        $args = array();
        $args['conditions']['CoPersonRole.id'] = $this->request->params['named']['copersonroleid'];
        $args['contain']['CoPerson'] = 'PrimaryName';
        $args['contain'][] = 'CoPetition';
        
        $this->set('vv_co_person_role', $this->CoPerson->CoPersonRole->find('first', $args));
      }
      
      if(!empty($this->request->params['named']['tocopersonid'])) {
        $args = array();
        $args['conditions']['CoPerson.id'] = $this->request->params['named']['tocopersonid'];
        $args['contain'][] = 'PrimaryName';
        
        $this->set('vv_to_co_person', $this->CoPerson->find('first', $args));
        $this->set('title_for_layout', _txt('op.relink'));
      }
    }
  }
  
  /**
   * Insert search parameters into URL for index.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.8
   */
  
  public function search() {
    // If a petition ID is provided, we're in select mode
    if(!empty($this->data['CoPetition']['id'])) {
      $url['action'] = 'select';
      $url['copetitionid'] = filter_var($this->data['CoPetition']['id'], FILTER_SANITIZE_SPECIAL_CHARS);
    } else {
      // Back to the index
      $url['action'] = 'index';
    }
    
    // build a URL will all the search elements in it
    // the resulting URL will be 
    // example.com/registry/co_people/index/Search.givenName:albert/Search.familyName:einstein
    foreach($this->data['Search'] as $field=>$value){
      if(!empty($value)) {
        $url['Search.'.$field] = $value; 
      }
    }
    
    if($url['action'] == 'index') {
      // Insert CO into URL. Note this also prevents truncation of email address searches (CO-1271).
      $url['co'] = $this->cur_co['Co']['id'];
    }

    // redirect the user to the url
    $this->redirect($url, null, true);
  }

  /**
   * Identify the target CO Person for an enrollment flow.
   *
   * @since  COmanage Registry v0.9.4
   */

  public function select() {
    if(!$this->request->is('restful')) {
      // We basically want the index behavior, but we set some view vars to allow
      // the enrollment flow breadcrumbs to render
      
      // Map the petition ID to an enrollment flow to obtain the configured petition steps
      
      if(!empty($this->request->params['named']['copetitionid'])) {
        $efId = $this->CoPerson->Co->CoPetition->field('co_enrollment_flow_id',
                                                       array('CoPetition.id' => $this->request->params['named']['copetitionid']));
        
        if($efId) {
          $steps = $this->CoPerson->Co->CoPetition->CoEnrollmentFlow->configuredSteps($efId);
          
          $this->set('vv_configured_steps', $steps);
          $this->set('vv_current_step', 'selectEnrollee');
        }
      }      
      
      $this->index();
    }
  }
}
