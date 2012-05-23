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
    if(!$this->restful && $this->request->is('post')) {
      $enrollmentFlowID = $this->enrollmentFlowID();
      
      // Set the view var. We need this on both success and failure.
        
      $this->set('co_enrollment_attributes',
                 $this->CoPetition->CoEnrollmentFlow->CoEnrollmentAttribute->enrollmentFlowAttributes($enrollmentFlowID));
      
      try {
        $this->CoPetition->createPetition($enrollmentFlowID,
                                          $this->cur_co['Co']['id'],
                                          $this->request->data,
                                          $this->Session->read('Auth.User.co_person_id'));
        
        $this->Session->setFlash(_txt('rs.pt.create'), '', array(), 'success');
        $this->performRedirect();
      }
      catch(Exception $e) {
        $this->Session->setFlash($e->getMessage(), '', array(), 'error');
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
        $this->set('co_enrollment_attributes',
                   $this->CoPetition->CoEnrollmentFlow->CoEnrollmentAttribute->enrollmentFlowAttributes($this->enrollmentFlowID()));
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
    
    // Add a new CO Petition?
    $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // Approve a CO Petition?
    $p['approve'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    $p['deny'] = $p['approve'];
    
    // Delete an existing CO Petition?
    $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // Edit an existing CO Petition?
    $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // Match against existing CO People?
    // Note this same permission exists in CO People
    $p['match'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // View all existing CO Petitions?
    $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
          
    // View an existing CO Petition?
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
}
