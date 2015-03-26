<?php
/**
 * COmanage Registry CO Petition Controller
 *
 * Copyright (C) 2012-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoPetitionsController extends StandardController {
  public $name = "CoPetitions";
  
  public $helpers = array('Time');
  
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'modified' => 'asc'
    ),
    'contain' => array(
      'ApproverCoPerson' => 'PrimaryName',
      'CoEnrollmentFlow',
      'Cou',
      'EnrolleeCoPerson' => 'PrimaryName',
      'PetitionerCoPerson' => 'PrimaryName',
      'SponsorCoPerson' => 'PrimaryName'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // For rendering views, we need more information than just the various ID numbers
  // stored in a petition.
  public $view_contains = array(
    'ApproverCoPerson' => 'PrimaryName',
    'EnrolleeCoPerson' => 'PrimaryName',
    'PetitionerCoPerson' => 'PrimaryName',
    'SponsorCoPerson' => 'PrimaryName',
    'CoPetitionHistoryRecord' => array(
      'ActorCoPerson' => array(
        'PrimaryName'
      )
    ),
    'CoEnrollmentFlow',
    'CoInvite',
    'Cou'
  );
  
  /**
   * Add a CO Petition.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - postcondition: On success, new Object created
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: $<object>_id or $invalid_fields set (REST)
   * - postcondition: $co_enrollment_attributes may be set.
   *
   * @since  COmanage Registry v0.5
   * @throws RuntimeException
   */
  
  function add() {
    if(!$this->request->is('restful')) {
      $enrollmentFlowID = $this->enrollmentFlowID();
      
      // Make sure this enrollment flow is active
      $status = $this->CoPetition->CoEnrollmentFlow->field('status',
                                                           array('CoEnrollmentFlow.id' => $enrollmentFlowID));
      
      if($status != EnrollmentFlowStatusEnum::Active) {
        $this->Session->setFlash(_txt('er.ef.active'), '', array(), 'error');
        $this->performRedirect();
      }
      
      // Set the title to be the name of the enrollment flow
      
      $this->set('title_for_layout',
                 $this->CoPetition->CoEnrollmentFlow->field('name',
                                                            array('CoEnrollmentFlow.id' => $enrollmentFlowID)));
      
      $authnReq = $this->CoPetition->CoEnrollmentFlow->field('require_authn',
                                                             array('CoEnrollmentFlow.id' => $enrollmentFlowID));
      
      if(!$authnReq && !$this->Session->check('Auth.User.name')) {
        // If authentication is not required, and we're not authenticated as
        // a valid user, hide the login/logout button to minimize confusion
        $this->set('noLoginLogout', true);
      }
      
      if($this->request->is('post')) {
        // Set the view var. We need this on both success and failure.
        
        $this->set('co_enrollment_attributes',
                   $this->CoPetition->CoEnrollmentFlow->CoEnrollmentAttribute->enrollmentFlowAttributes($enrollmentFlowID));
        
        try {
          $petitionId = $this->CoPetition->createPetition($enrollmentFlowID,
                                                          $this->cur_co['Co']['id'],
                                                          $this->request->data,
                                                          $this->Session->read('Auth.User.co_person_id'));
          
          $matchPolicy = $this->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                                    array('CoEnrollmentFlow.id' => $enrollmentFlowID));
          
          $authzLevel = $this->CoPetition->CoEnrollmentFlow->field('authz_level',
                                                                   array('CoEnrollmentFlow.id' => $enrollmentFlowID));
          
          if($authzLevel == EnrollmentAuthzEnum::None) {
            // Figure out where to redirect the enrollee to
            $targetUrl = $this->CoPetition->CoEnrollmentFlow->field('redirect_on_submit',
                                                                    array('CoEnrollmentFlow.id' => $enrollmentFlowID));
            
            if(!$targetUrl || $targetUrl == "") {
              // Default redirect is to /, which isn't really a great target
              
              $this->Session->setFlash(_txt('rs.pt.create.self'), '', array(), 'success');
              $targetUrl = "/";
            }
            // else we suppress the flash message, since it may not make sense in context
            // or may appear "randomly" (eg: if the targetUrl is outside the Cake framework)
            
            // Store the CO Petition ID in the session, so the target can pick it up if desired
            $this->Session->write('CoPetition.id', $petitionId);
            
            $this->redirect($targetUrl);
          } elseif($authnReq && $matchPolicy == EnrollmentMatchPolicyEnum::Self) {
            // Clear any session for account linking
            $this->Session->setFlash(_txt('rs.pt.login'), '', array(), 'success');
            $this->redirect("/auth/logout");
          } else {
            // Standard redirect
            $this->Session->setFlash(_txt('rs.pt.create'), '', array(), 'success');
            $this->performRedirect();
          }
        }
        catch(Exception $e) {
          $this->Session->setFlash($e->getMessage(), '', array(), 'error');
        }
      } else {
        parent::add();
      }
    } else {
      // REST API gets standard behavior
      
      parent::add();
    }
  }
  
  /**
   * Approve a petition.
   * - precondition: $id must exist and be in 'Pending Approval' state
   * - postcondition: On error, session flash message set
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.5
   * @param  Integer Petition ID
   */
  
  function approve($id) {
    try {
      $this->CoPetition->updateStatus($id,
                                      StatusEnum::Approved,
                                      $this->Session->read('Auth.User.co_person_id'));
      
      $this->Session->setFlash(_txt('rs.pt.approve'), '', array(), 'success');
    }
    catch(Exception $e) {
      $this->Session->setFlash($e->getMessage(), '', array(), 'error');
    }
    
    $this->performRedirect();
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: If invalid enrollment flow provided, session flash message set
   *
   * @since  COmanage Registry v0.5
   */
  
  function beforeFilter() {
    if(!$this->request->is('restful') && ($this->action == 'add' || $this->action == 'edit')) {
      // Make sure we were given a valid enrollment flow
      
      $args['conditions']['CoEnrollmentFlow.id'] = $this->enrollmentFlowID();
      $args['contain'] = false;
      $ef = $this->CoPetition->CoEnrollmentFlow->find('first', $args);
      
      if(empty($ef)) {
        $this->Session->setFlash(_txt('er.coef.unk'), '', array(), 'error');
      } elseif(isset($ef['CoEnrollmentFlow']['authz_level'])
               && $ef['CoEnrollmentFlow']['authz_level'] == EnrollmentAuthzEnum::None
               && isset($ef['CoEnrollmentFlow']['require_authn'])
               && !$ef['CoEnrollmentFlow']['require_authn']) {
        // If this enrollment flow allows unauthenticated enrollments, drop the auth
        // requirement, but only if authentication is not required for the flow.
        // Only do this for add for the moment, since we don't currently
        // know what it means for an unauthenticated enrollment to be edited without
        // authentication.
        
        if($this->action == 'add' && $this->isAuthorized()) {
          $this->Auth->allow('add');
        }
      }
    }
    
    parent::beforeFilter();
    
    // Dynamically adjust validation rules to include the current CO ID for dynamic types.
    
    $vrule = $this->CoPetition->EnrolleeCoPerson->Identifier->validate['type']['content']['rule'];
    $vrule[1]['coid'] = $this->cur_co['Co']['id'];
    
    $this->CoPetition->EnrolleeCoPerson->Identifier->validator()->getField('type')->getRule('content')->rule = $vrule;
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   * - postcondition: If a CO must be specifed, a named parameter may be set.
   * - postcondition: $co_enrollment_attributes may be set.
   *
   * @since  COmanage Registry v0.5
   */
  
  function beforeRender() {
    if(!$this->request->is('restful')) {
      // Set the enrollment flow ID to make it easier to carry forward through failed submissions
      $this->set('co_enrollment_flow_id', $this->enrollmentFlowID());
      
      if(($this->action == 'add' || $this->action == 'edit' || $this->action == 'view')) {
        $enrollmentFlowID = $this->enrollmentFlowID();
        
        if($this->request->is('get')) {
          // If we processed a post, this will have already been set.
          
          $defaultValues = array();
          
          if($enrollmentFlowID) {
            // Provide default values for name for self enrollment.
            
            $p['match_policy'] = $this->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                                            array('CoEnrollmentFlow.id' => $enrollmentFlowID));
            
            if($p['match_policy'] == EnrollmentMatchPolicyEnum::Self) {
              $defName = $this->Session->read('Auth.User.name');
              
              if(!empty($defName)) {
                // Populate select attributes only
                $defaultValues['EnrolleeOrgIdentity.Name']['honorific'] = $defName['honorific'];
                $defaultValues['EnrolleeOrgIdentity.Name']['given'] = $defName['given'];
                $defaultValues['EnrolleeOrgIdentity.Name']['middle'] = $defName['middle'];
                $defaultValues['EnrolleeOrgIdentity.Name']['family'] = $defName['family'];
                $defaultValues['EnrolleeOrgIdentity.Name']['suffix'] = $defName['suffix'];
              }
            }
          }
          
          $this->loadModel('CmpEnrollmentConfiguration');
          
          $envValues = false;
          $enrollmentAttributes = $this->CoPetition
                                       ->CoEnrollmentFlow
                                       ->CoEnrollmentAttribute
                                       ->enrollmentFlowAttributes($this->enrollmentFlowID(),
                                                                  $defaultValues);
          
          if($this->CmpEnrollmentConfiguration->orgIdentitiesFromCOEF()) {
            // If enrollment flows can populate org identities, then see if we're configured
            // to pull environment variables. If so, for this configuration they simply
            // replace modifiable default values.
            
            $envValues = $this->CmpEnrollmentConfiguration->enrollmentAttributesFromEnv();
            
            if($envValues) {
              // This flow might be configured to ignore authoritative values
              $ignoreAuthValues = $this->CoPetition
                                       ->CoEnrollmentFlow->field('ignore_authoritative',
                                                                 array('CoEnrollmentFlow.id' => $enrollmentFlowID));
              
              if(!$ignoreAuthValues) {
                $enrollmentAttributes = $this->CoPetition
                                             ->CoEnrollmentFlow
                                             ->CoEnrollmentAttribute
                                             ->mapEnvAttributes($enrollmentAttributes,
                                                                $envValues);
              }
            }
          }
          
          $this->set('co_enrollment_attributes', $enrollmentAttributes);
        }
        
        // Pull any relevant Terms and Conditions that must be agreed to. We only do this
        // if authentication is required (otherwise we can't really assert who agreed),
        // and only for CO-wide T&C (ie: those without a COU ID specified). There's not
        // necessarily a reason why we couldn't prompt for COU specific T&C, if the petition
        // adjusted dynamically to the COU being enrolled in, but we don't have a use case
        // for it at the moment.
        
        $authn = $this->CoPetition->CoEnrollmentFlow->field('require_authn',
                                                            array('CoEnrollmentFlow.id' => $enrollmentFlowID));
        
        if($authn) {
          $tArgs = array();
          $tArgs['conditions']['CoTermsAndConditions.co_id'] = $this->cur_co['Co']['id'];
          $tArgs['conditions']['CoTermsAndConditions.cou_id'] = null;
          $tArgs['conditions']['CoTermsAndConditions.status'] = SuspendableStatusEnum::Active;
          $tArgs['contain'] = false;
          
          $this->set('vv_terms_and_conditions',
                     $this->CoPetition->Co->CoTermsAndConditions->find('all', $tArgs));
          
          // Also pass through the T&C Mode
          
          $tcmode = $this->CoPetition
                         ->CoEnrollmentFlow->field('t_and_c_mode',
                                                   array('CoEnrollmentFlow.id' => $enrollmentFlowID));
          
          $this->set('vv_tandc_mode', (!empty($tcmode) ? $tcmode : TAndCEnrollmentModeEnum::ExplicitConsent));
        }
        
        // See if there is introductory text
        
        $introText = $this->CoPetition->CoEnrollmentFlow->field('introduction_text',
                                                                array('CoEnrollmentFlow.id' => $enrollmentFlowID));
        
        if($introText) {
          $this->set('vv_introduction_text', $introText);
        }
        
        // Or conclusion text
        
        $conclText = $this->CoPetition->CoEnrollmentFlow->field('conclusion_text',
                                                                array('CoEnrollmentFlow.id' => $enrollmentFlowID));
        
        if($conclText) {
          $this->set('vv_conclusion_text', $conclText);
        }
      }
      
      if(($this->action == 'edit' || $this->action == 'view')
          && $this->request->is('get')) {
        // This information is already embedded in $co_petitions, but it's easier for the
        // views to access it this way. Also, arguably $co_petitions needs some trimming
        // via containable.
        
        $vArgs = array();
        $vArgs['conditions']['CoPetitionAttribute.co_petition_id'] = $this->CoPetition->id;
        $vArgs['fields'] = array(
          'CoPetitionAttribute.attribute',
          'CoPetitionAttribute.value',
          'CoPetitionAttribute.co_enrollment_attribute_id'
        );
        $vAttrs = $this->CoPetition->CoPetitionAttribute->find("list", $vArgs);
        
        $this->set('co_petition_attribute_values', $vAttrs);
      }
    }
    
    parent::beforeRender();
    
    // Possibly override the title
    
    if($this->action == 'view' || $this->action == 'edit') {
      $this->set('title_for_layout',
                 _txt('op.' . $this->action . '-f',
                      array(_txt('ct.co_petitions.1'),
                            generateCn($this->viewVars['co_petitions'][0]['EnrolleeCoPerson']['PrimaryName']))));
    }
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
    if($this->action == 'add') {
      // Map enrollment flow ID to CO
      
      $coId = $this->CoPetition->CoEnrollmentFlow->field('co_id',
                                                         array('id' => $this->enrollmentFlowID()));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.coef.unk'));
      }
    }
    
    // Or try the default behavior
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Deny a petition.
   * - precondition: $id must exist and be in 'Pending Approval' or 'Pending Confirmation' state
   * - postcondition: On error, session flash message set
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.5
   * @param  Integer Petition ID
   */
  
  function deny($id) {
    try {
      $this->CoPetition->updateStatus($id,
                                      StatusEnum::Denied,
                                      $this->Session->read('Auth.User.co_person_id'));
      
      $this->Session->setFlash(_txt('rs.pt.deny'), '', array(), 'success');
    }
    catch (Exception $e) {
      $this->Session->setFlash($e->getMessage(), '', array(), 'error');
    }
    
    $this->performRedirect();
  }
  
  /**
   * Flag a petition as a duplicate.
   * - precondition: $id must exist and be in 'Pending Approval' or 'Pending Confirmation' state
   * - postcondition: On error, session flash message set
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.9.1
   * @param  Integer Petition ID
   */
  
  public function dupe($id) {
    try {
      $this->CoPetition->updateStatus($id,
                                      StatusEnum::Duplicate,
                                      $this->Session->read('Auth.User.co_person_id'));
      
      $this->Session->setFlash(_txt('rs.pt.dupe'), '', array(), 'success');
    }
    catch (Exception $e) {
      $this->Session->setFlash($e->getMessage(), '', array(), 'error');
    }
    
    $this->performRedirect();
  }
  
  /**
   * Determine the requested Enrollment Flow ID.
   * - precondition: An enrollment flow ID should be specified as a named query parameter or in form data.
   *
   * @since  COmanage Registry v0.5
   * @return Integer CO Enrollment Flow ID if found, or -1 otherwise
   */
  
  function enrollmentFlowID() {
    if(isset($this->request->params['pass'][0])) {
      // Don't trust the coef parameter, but look up the enrollment flow
      // associated with this ID
      
      $coef = $this->CoPetition->field('co_enrollment_flow_id',
                                       array('CoPetition.id' => $this->request->params['pass'][0]));
      
      return ($coef ? $coef : -1);
    } elseif(isset($this->request->params['named']['coef'])) {
      return $this->request->params['named']['coef'];
    } elseif(isset($this->request->data['CoPetition']['co_enrollment_flow_id'])) {
      // We can trust this element since form tampering checks mean it's the
      // same value the view emitted.
      return $this->request->data['CoPetition']['co_enrollment_flow_id'];
    }
    
    return -1;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.5
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Some operations are authorized according to the flow configuration.
    $flowAuthorized = false;
    
    // If an enrollment flow was specified, check the authorization for that flow
    
    if($this->enrollmentFlowID() != -1) {
      $flowAuthorized = $this->CoPetition->CoEnrollmentFlow->authorizeById($this->enrollmentFlowID(), $roles['copersonid'], $this->Role);
    }
    
    // Add a new CO Petition?
    $p['add'] = ($roles['cmadmin'] || $flowAuthorized);
    
    // Approve a CO Petition?
    if($this->enrollmentFlowID() != -1) {
      if(!empty($this->request->params['pass'][0])) {
        $p['approve'] = $roles['cmadmin'] || $this->Role->isApproverForFlow($roles['copersonid'],
                                                                            $this->enrollmentFlowID(),
                                                                            $this->request->params['pass'][0]);
      } else {
        $p['approve'] = $roles['cmadmin'] || $this->Role->isApproverForFlow($roles['copersonid'], $this->enrollmentFlowID());
      }
    } else {
      $p['approve'] = $roles['cmadmin'] || $this->Role->isApprover($roles['copersonid']);
    }
    
    // Delete an existing CO Petition?
    // For now, this is restricted to CMP and CO Admins, until we have a better policy
    $p['delete'] = ($roles['cmadmin']
                    || ($flowAuthorized && $roles['coadmin']));
    
    // Deny an existing CO Petition?
    $p['deny'] = $p['approve'];
    
    // Flag an existing CO Petition as a duplicate?
    $p['dupe'] = $p['deny'];
    
    // Edit an existing CO Petition?
    $p['edit'] = ($roles['cmadmin']
                  || ($flowAuthorized && ($roles['coadmin'] || $roles['couadmin'])));
    
    // Match against existing CO People? If the match policy is Advisory or Automatic, we
    // allow matching to take place as long as $flowAuthorized is also true.
    // Note this same permission exists in CO People
    
    $p['match_policy'] = $this->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                                    array('CoEnrollmentFlow.id' => $this->enrollmentFlowID()));
    $p['match'] = (($roles['cmadmin'] || $flowAuthorized)
                   &&
                   ($p['match_policy'] == EnrollmentMatchPolicyEnum::Advisory
                    || $p['match_policy'] == EnrollmentMatchPolicyEnum::Automatic));
    
    // View all existing CO Petitions?
    // Before adjusting this, see paginationConditions(), below
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'] || $this->Role->isApprover($roles['copersonid']));
    
    // Search all existing CO Petitions?
    $p['search'] = $p['index'];

    // Resend invitations?
    $p['resend'] = ($roles['cmadmin']
                    || ($flowAuthorized && ($roles['coadmin'] || $roles['couadmin'])));
    
    // View an existing CO Petition? We allow the usual suspects to view a Petition, even
    // if they don't have permission to edit it. Also approvers need to be able to see the Petition.
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'] || $p['approve']);
    
    if($this->action == 'index' && $p['index']) {
      // These permissions may not be exactly right, but they only apply when rendering
      // the index view
      
      $p['add'] = true;  // This is really permission to run co_enrollment_flows/select
      $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
      $p['edit'] = $p['delete'];  // For now, delete and edit are restricted
      $p['resend'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']);
      $p['view'] = true;  // Approvers will have the petitions they can see filtered by the controller
    }
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v0.8.3
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    $pagcond = array();
    
    // Use server side pagination
    
    if($this->requires_co) {
      $pagcond['conditions']['CoPetition.co_id'] = $this->cur_co['Co']['id'];
    }
    
    // Filter by status
    if(!empty($this->params['named']['search.status'])) {
      $searchterm = $this->params['named']['search.status'];
      $pagcond['conditions']['CoPetition.status'] = $searchterm;
    }
    
    // Filter by CO Person ID
    if(!empty($this->params['named']['search.copersonid'])) {
      $pagcond['conditions']['CoPetition.enrollee_co_person_id'] = $this->params['named']['search.copersonid'];
    }
    
    // Filter by Org Identity ID
    if(!empty($this->params['named']['search.orgidentityid'])) {
      $pagcond['conditions']['CoPetition.enrollee_org_identity_id'] = $this->params['named']['search.orgidentityid'];
    }
    
    // Potentially filter by enrollment flow ID. Our assumption is that if we make it
    // here the person has authorization to see at least some Petitions. Either they
    // are a CO or COU admin (in which case the following list will be empty) or they
    // are an approver by group (in which case the following list will not be empty).
    // We explicitly consider CMP admins to have the same permissions even if they
    // are not in the CO.
    
    // This isn't exactly right, though... what we really want for COU admins is
    // to know which petitions the admin can approve. However, a COU admin may have
    // approval privileges based on the COU a petition is attached to (rather than
    // the enrollment flow), which requires examining all Petitions. XXX Perhaps a
    // future enhancement.
    
    $coPersonId = $this->Session->read('Auth.User.co_person_id');
    $username = $this->Session->read('Auth.User.username');
    
    if(!$this->Role->isCoOrCouAdmin($coPersonId, $this->cur_co['Co']['id'])
       // We need an explicit check for CMP admin, who should have superuser privs
       && !$this->Role->identifierIsCmpAdmin($username)) {
      // approverFor will return groups even for a CO/COU admin, so don't check it for admins
      $efs = $this->Role->approverFor($coPersonId);
      
      if(!empty($efs)) {
        $pagcond['conditions']['CoPetition.co_enrollment_flow_id'] = $efs;
      } else {
        // We shouldn't normally get here, as isAuthorized should filter anyone without
        // an approval role, but just in case we'll insert an invalid ID that won't ever match
        $pagcond['conditions']['CoPetition.co_enrollment_flow_id'] = -1;
      }
    }
    
    return $pagcond;
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.5
   */
  
  function performRedirect() {
    if($this->action == 'add') {
      // After submission on add, we go back to CO People
      
      $this->redirect(array(
        'controller' => 'co_people',
        'action' => 'index',
        'co' => $this->cur_co['Co']['id']
      ));
    } else {
      parent::performRedirect();
    }
  }
  
  /**
   * Resend an invitation associated with a Petition.
   * - precondition: Petition exists in a Pending Confirmation state
   * - postcondition: Invitation sent
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Petition ID
   */
  
  public function resend($id) {
    $recipient = null;
    
    try {
      $recipient = $this->CoPetition->resend($id, $this->Session->read('Auth.User.co_person_id'));
    }
    catch(Exception $e) {
      $this->Session->setFlash($e->getMessage(), '', array(), 'error');
    }
    
    if($recipient) {
      $this->Session->setFlash(_txt('rs.inv.sent', array($recipient)), '', array(), 'success');
    }
    
    // Redirect back to index. We might have gotten here via co_petitions or co_people,
    // so try to figure out the right place to go back to.
    
    if(strstr($this->request->referer(), 'co_petitions')) {
      $this->redirect(array(
        'controller' => 'co_petitions',
        'action' => 'index',
        'co' => $this->cur_co['Co']['id']
      ));
    } else {
      $this->redirect(array(
        'controller' => 'co_people',
        'action' => 'index',
        'co' => $this->cur_co['Co']['id']
      ));
    }
  }
}
