<?php
/**
 * COmanage Registry CO Petition Controller
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
      'ApproverCoPerson' => 'Name',
      'EnrolleeCoPerson' => 'Name',
      'PetitionerCoPerson' => 'Name',
      'SponsorCoPerson' => 'Name'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // For rendering views, we need more information than just the various ID numbers
  // stored in a petition.
  public $view_contains = array(
    'ApproverCoPerson' => 'Name',
    'EnrolleeCoPerson' => 'Name',
    'PetitionerCoPerson' => 'Name',
    'SponsorCoPerson' => 'Name',
    'CoPetitionHistoryRecord' => array(
      'ActorCoPerson' => array(
        'Name'
      )
    )
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
    if(!$this->restful) {
      $enrollmentFlowID = $this->enrollmentFlowID();
        
      if($this->request->is('post')) {
        // Set the view var. We need this on both success and failure.
          
        $this->set('co_enrollment_attributes',
                   $this->CoPetition->CoEnrollmentFlow->CoEnrollmentAttribute->enrollmentFlowAttributes($enrollmentFlowID));
        
        try {
          $this->CoPetition->createPetition($enrollmentFlowID,
                                            $this->cur_co['Co']['id'],
                                            $this->request->data,
                                            $this->Session->read('Auth.User.co_person_id'));
          
          $matchPolicy = $this->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                                    array('CoEnrollmentFlow.id' => $enrollmentFlowID));
          
          $authnReq = $this->CoPetition->CoEnrollmentFlow->field('require_authn',
                                                                 array('CoEnrollmentFlow.id' => $enrollmentFlowID));
          
          if($matchPolicy == EnrollmentMatchPolicyEnum::Self) {
            if($authnReq) {
              $this->Session->setFlash(_txt('rs.pt.login'), '', array(), 'success');
              $this->redirect("/auth/logout");
            } else {
              // Not really clear where to send a self-enrollment person...
              $this->Session->setFlash(_txt('rs.pt.create'), '', array(), 'success');
              $this->redirect("/");
            }
          } else {
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
    if(!$this->restful && ($this->action == 'add' || $this->action == 'edit')) {
      // Make sure we were given a valid enrollment flow
      
      $args['conditions']['CoEnrollmentFlow.id'] = $this->enrollmentFlowID();
      $found = $this->CoPetition->CoEnrollmentFlow->find('count', $args);
      
      if($found == 0) {
        $this->Session->setFlash(_txt('er.coef.unk'), '', array(), 'error');
      }
    }
    
    parent::beforeFilter();
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
    if(!$this->restful) {
      // Set the enrollment flow ID to make it easier to carry forward through failed submissions
      $this->set('co_enrollment_flow_id', $this->enrollmentFlowID());
      
      if(($this->action == 'add' || $this->action == 'edit' || $this->action == 'view')
          && $this->request->is('get')) {
        // If we processed a post, this will have already been set.
        
        $defaultValues = array();
        
        $enrollmentFlowID = $this->enrollmentFlowID();
        
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
        
        $this->set('co_enrollment_attributes',
                   $this->CoPetition->CoEnrollmentFlow->CoEnrollmentAttribute->enrollmentFlowAttributes($this->enrollmentFlowID(),
                                                                                                        $defaultValues));
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
  }
  
  /**
   * Deny a petition.
   * - precondition: $id must exist and be in 'Pending Approval' state
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
   * Determine the requested Enrollment Flow ID.
   * - precondition: An enrollment flow ID should be specified as a named query parameter or in form data.
   *
   * @since  COmanage Registry v0.5
   * @return Integer CO Enrollment Flow ID if found, or -1 otherwise
   */
  
  function enrollmentFlowID() {
    if(isset($this->request->params['named']['coef']))
      return($this->request->params['named']['coef']);
    elseif(isset($this->request->data['CoPetition']['co_enrollment_flow_id']))
      return($this->request->data['CoPetition']['co_enrollment_flow_id']);
    
    return(-1);
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
    $cmr = $this->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Some operations are authorized according to the flow configuration.
    $flowAuthorized = false;
    
    // If an enrollment flow was specified, check the authorization for that flow
    
    if($this->enrollmentFlowID() != -1) {
      $flowAuthorized = $this->CoPetition->CoEnrollmentFlow->authorizeById($this->enrollmentFlowID(), $cmr['copersonid']);
    }
    
    // Add a new CO Petition?
    $p['add'] = $flowAuthorized
                // Or we have an index view
                || ($this->enrollmentFlowID() == -1 & ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin'])));
    
    // Approve a CO Petition?
    $p['approve'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    $p['deny'] = $p['approve'];
    
    // Delete an existing CO Petition?
    // For now, this is restricted to CMP and CO Admins, until we have a better policy
    $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Edit an existing CO Petition?
    $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // Match against existing CO People? If the match policy is Advisory or Automatic, we
    // allow matching to take place as long as $flowAuthorized is also true.
    // Note this same permission exists in CO People
    
    $p['match_policy'] = $this->CoPetition->CoEnrollmentFlow->field('match_policy',
                                                                    array('CoEnrollmentFlow.id' => $this->enrollmentFlowID()));
    $p['match'] = ($flowAuthorized &&
                   ($p['match_policy'] == EnrollmentMatchPolicyEnum::Advisory
                    || $p['match_policy'] == EnrollmentMatchPolicyEnum::Automatic));
    
    // View all existing CO Petitions?
    $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // Resend invitations?
    $p['resend'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // View an existing CO Petition? We allow the usual suspects to view a Petition, even
    // if they don't have permission to edit it.
    $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));

    $this->set('permissions', $p);
    return($p[$this->action]);
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
      $recipient = $this->CoPetition->resend($id);
    }
    catch(Exception $e) {
      $this->Session->setFlash($e->getMessage(), '', array(), 'error');
    }
    
    if($recipient) {
      $this->Session->setFlash(_txt('rs.inv.sent', array($recipient)), '', array(), 'success');
    }
    
    // Redirect back to index
    
    $this->redirect(array(
      'controller' => 'co_petitions',
      'action' => 'index',
      'co' => $this->cur_co['Co']['id']
    ));
  }
}
