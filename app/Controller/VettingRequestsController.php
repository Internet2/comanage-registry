<?php
/**
 * COmanage Registry Vetting Requests Controller
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class VettingRequestsController extends StandardController {
  // Class name, used by Cake
  public $name = "VettingRequests";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'link' => array(
      'VetterCoPerson' => array(
        'class' => 'CoPerson',
        'VetterPrimaryName' => array(
          'class' => 'Name',
          'conditions' => array(
            // Linkable behavior doesn't seem to be able to handle multiple joins
            // against the same table, so we manually specify the join condition for
            // each name. We then have to explicitly filter on primary name so as
            // not to produce multiple rows in the join for alternate names the
            // CO Person might have.
            'exactly' => 'VetterPrimaryName.co_person_id = VetterCoPerson.id AND VetterPrimaryName.primary_name = true'
          )
        )
      )
    ),
    'order' => array(
      'id' => 'desc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $edit_contains = array();
  
  public $view_contains = array(
    'CoPerson' => array('PrimaryName'),
    'CoJob',
    'VettingResult' => array(
      // This is the Vetting Step for this Vetting Result, of which there can
      // be more that one
      'VettingStep',
      'order' => array('VettingResult.created' => 'desc')
    ),
    // This is the "current" Vetting Step for this Vetting Request
    'VettingStep'
  );
  
  /**
   * Cancel a Vetting Request
   *
   * @since  COmanage Registry v4.1.0
   * @param  int $id Vetting Request ID
   */
  
  function cancel($id) {
    // Cancel an existing Vetting Request, which will also cancel the job scheduled to process it
    try {
      $this->VettingRequest->cancel($id,
                                    $this->Session->read('Auth.User.co_person_id'));
      $this->Flash->set(_txt('rs.vetting.canceled', array($id)), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.1.0
   * @return Array An array suitable for use in $this->paginate
   * @throws InvalidArgumentException
   */

  function paginationConditions() {
    // Only retrieve entries for the requested Person

    $ret = array();
    
    // XXX If the current user is not a CO admin, filter by groups that the user
    // is a member of -- this should override copersonid
    if(!empty($this->request->params['named']['copersonid'])) {
      // Filter by CO Person ID (authz checked in isAuthorized)
      $ret['conditions']['VettingRequest.co_person_id'] = $this->request->params['named']['copersonid'];
    } else {
      // Constrain to the current CO
      $ret['conditions']['CoPerson.co_id'] = $this->cur_co['Co']['id'];
    }
    
    // Filter by status
    if(!empty($this->params['named']['search.status'])) {
      $ret['conditions']['VettingRequest.status'] = $this->params['named']['search.status'];
    }
    
    // Filter by Name
    if(!empty($this->params['named']['search.co_person']) ) {
      $ret['conditions']['AND'][] = array(
        'OR' => array(
          'LOWER(VetterPrimaryName.family) LIKE' => '%' . strtolower($this->params['named']['search.co_person']) . '%',
          'LOWER(VetterPrimaryName.given) LIKE' => '%' . strtolower($this->params['named']['search.co_person']) . '%',
        )
      );
    }
    
    return $ret;
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v4.1.0
   * @return Integer The CO ID if found, or -1 if not
   */
  
  public function parseCOID($data = NULL) {
    if($this->action == 'register') {
      if(!empty($this->request->params['named']['copersonid'])) {
        // Map the CO Person to a CO
        
        $coId = $this->VettingRequest->CoPerson->field('co_id', array('CoPerson.id' => $this->request->params['named']['copersonid']));
        
        if($coId) {
          return $coId;
        }
      }
    }
    
    return parent::parseCOID();
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.1.0
   */
  
  function performRedirect() {
    if($this->action == 'register' && !empty($this->request->params['named']['copersonid'])) {
      // Redirect to the index view, filtered for the current CO Person
      
      $redirectUrl = array(
        'controller' => 'vetting_requests',
        'action' => 'index',
        'copersonid' => filter_var($this->request->params['named']['copersonid'], FILTER_SANITIZE_SPECIAL_CHARS)
      );
      
      $this->redirect($redirectUrl);
    } elseif(!empty($this->request->pass[0])) {
      // Map the record ID to a CO Person
      $coPersonId = $this->VettingRequest->field('co_person_id', array('VettingRequest.id' => $this->request->pass[0]));
      
      if($coPersonId) {
        $redirectUrl = array(
          'controller' => 'vetting_requests',
          'action' => 'index',
          'copersonid' => $coPersonId
        );
        
        $this->redirect($redirectUrl);
      } else {
        parent::performRedirect();
      }
    } else {
      parent::performRedirect();
    }
  }
  
  /**
   * Register a Vetting Request.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  function register() {
    // Register a new Vetting Request, which will also queue a job to process it
    try {
      if(empty($this->request->params['named']['copersonid'])) {
        throw new InvalidArgumentException(_txt('er.notprov.id', array('ct.co_people.1')));
      }
      
      $reqId = $this->VettingRequest->register($this->request->params['named']['copersonid'],
                                               $this->Session->read('Auth.User.co_person_id'));
      $this->Flash->set(_txt('rs.vetting.registered', array($reqId)), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
  
  /**
   * Resolve a Vetting Step (not an entire Request).
   *
   * @since  COmanage Registry v4.1.0
   * @param  int $id Vetting Request ID
   */
  
  function resolve($id) {
    $result = array(
      'result'  => VettingStatusEnum::Failed,
      'comment' => ""
    );
    
    if(!empty($this->request->data['action']) 
       && $this->request->data['action'] == _txt('op.approve')) {
      $result['result'] = VettingStatusEnum::Passed;
    }
    
    if(!empty($this->request->data['VettingResult']['comment'])) {
      $result['comment'] = $this->request->data['VettingResult']['comment'];
    }
    
    try {
      $this->VettingRequest
           ->VettingResult
           ->VettingStep->resolve($this->request->data['VettingResult']['vetting_step_id'],
                                  $id,
                                  $result,
                                  $this->Session->read('Auth.User.co_person_id'));
      
      if($result['result'] == VettingStatusEnum::Passed) {
        // Requeue the job to finish processing (we just resolved a step, not
        // the whole request).
        $jobId = $this->VettingRequest->queue($id, true, $this->Session->read('Auth.User.co_person_id'));
        
        $this->Flash->set(_txt('rs.vetting.requeued', array($id)), array('key' => 'success'));
      } else {
        // Terminate the vetting request. If we're in an Enrollment Flow, this
        // will also terminate the Enrollment.
        $this->VettingRequest->cancel($id,
                                      $this->Session->read('Auth.User.co_person_id'));
        
        $this->Flash->set(_txt('rs.vetting.canceled', array($id)), array('key' => 'success'));
      }
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
  
  /**
   * Search Block fields configuration
   *
   * @since  COmanage Registry v4.1.0
   */

  public function searchConfig($action) {
    if($action == 'index') {                   // Index
      return array(
        'search.co_person' => array(
          'label' => _txt('fd.enrollee'),
          'type' => 'text'
        ),
        'search.status' => array(
          'label' => _txt('fd.status'),
          'type' => 'select',
          'empty'   => _txt('op.select.all'),
          'options' => _txt('en.status.vet'),
        )
      );
    }
  }
  
  /**
   * Retrieve a Standard Object.
   * - precondition: <id> must exist
   * - postcondition: $<object>s set (with one member)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML)
   *
   * @since  COmanage Registry v4.1.0
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */

  function view($id) {
    parent::view($id);
    
    if(!empty($this->viewVars['vetting_requests'][0]['VettingRequest'])
       && $this->viewVars['vetting_requests'][0]['VettingRequest']['status'] == VettingStatusEnum::PendingManual) {
      // The current request is pending manual resolution. Construct a URL into
      // the appropriate plugin.
      
      // Find the result corresponding to the current step. We should only have
      // one entry for the current step if it is PendingManual, though after
      // the manual review is done there would be two entries for the same step.
      $stepId = $this->viewVars['vetting_requests'][0]['VettingRequest']['vetting_step_id'];
      
      $currentResult = Hash::extract($this->viewVars['vetting_requests'][0]['VettingResult'], 
                                     '{n}[vetting_step_id='.$stepId.']');
      
      $pluginLink = array(
        'plugin'        => Inflector::underscore($this->viewVars['vetting_requests'][0]['VettingStep']['plugin']),
        'controller'    => Inflector::tableize($this->viewVars['vetting_requests'][0]['VettingStep']['plugin']),
        'action'        => 'review',
        'vettingresult' => $currentResult[0]['id']
      );
      
      $this->set('vv_current_result', $currentResult);
      $this->set('vv_plugin_link', $pluginLink);
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Is this person a vetter for any Step within this CO?
    $vetterGroups = array();
    $vetterForRequest = false;
    
    if(!empty($this->request->params['named']['copersonid']) && !empty($roles['copersonid'])) {
      $vetterGroups = $this->Role->vetterForGroups($roles['copersonid']);
    } elseif(!empty($this->request->params['pass'][0])) {
      // Check the current step via the VettingRequest
      $args = array();
      $args['conditions']['VettingRequest.id'] = $this->request->params['pass'][0];
      $args['contain'] = array('VettingStep');
      
      $vettingRequest = $this->VettingRequest->find('first', $args);
      
      if(!empty($roles['copersonid'])) {
        $vetterGroups = $this->Role->vetterForGroups($roles['copersonid']);
        
        if(!empty($vettingRequest['VettingStep']['vetter_co_group_id'])) {
          $vetterForRequest = $this->VettingRequest->CoPerson->CoGroupMember->isMember($vettingRequest['VettingStep']['vetter_co_group_id'], $roles['copersonid']);
        }
      }
    }

    // Is this person a vetter for the _current_ step?
    $isVetter = false;
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Cancel an existing Vetting Request?
    $p['cancel'] = ($roles['cmadmin'] || $roles['coadmin'] || $vetterForRequest);
    
    // Register a new Vetting Request?
    $p['register'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Resolve a Vetting Request (Step)?
    $p['resolve'] = ($roles['cmadmin'] || $roles['coadmin'] || $vetterForRequest);
    
    // View all existing Vetting Requests?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || !empty($vetterGroups));
    $p['search'] = $p['index'];
    
    // View an existing Vetting Request?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || !empty($vetterGroups));

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
