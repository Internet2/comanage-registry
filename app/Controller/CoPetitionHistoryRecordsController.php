<?php
/**
 * COmanage Registry CO Petition History Record Controller
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
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoPetitionHistoryRecordsController extends StandardController {
  public $name = "CoPetitionHistoryRecords";
  
  public $helpers = array('Time');
  
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'modified' => 'asc'
    ),
    'contain' => array(
      'ActorCoPerson' => 'PrimaryName'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // For rendering views, we need more information than just the various ID numbers
  // stored in a petition.
  public $view_contains = array(
    'ActorCoPerson' => 'PrimaryName'
  );
  
  /**
   * Determine the CO ID based on some attribute of the request.
   *
   * @since  COmanage Registry v0.9.1
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    if($this->action == 'add') {
      // Map petition to CO ID
      
      $coptid = $this->petitionID();
      
      if($coptid) {
        $coId = $this->CoPetitionHistoryRecord->CoPetition->field('co_id',
                                                                  array('id' => $coptid));
      }
      
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
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.9.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Some operations are authorized according to the flow configuration of the associated
    // CO Petition.
    $flowAuthorized = false;
    
    $coptid = $this->petitionID();
    
    if($coptid) {
      // Figure out the associated flow
      
      $coef = $this->CoPetitionHistoryRecord->CoPetition->field('co_enrollment_flow_id',
                                                                array('id' => $coptid));
      
      if($coef) {
        $flowAuthorized = $this->CoPetitionHistoryRecord
                               ->CoPetition
                               ->CoEnrollmentFlow
                               ->authorizeById($coef, 
                                               $roles['copersonid'],
                                               $this->Session->read('Auth.User.username'),
                                               $this->Role);
      }
    }
    
    // If an enrollment flow was specified, check the authorization for that flow
    
    // Add a new CO Petition History Record (comment)? This will correlate to editing
    // a CO Petition.
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']
                 || ($flowAuthorized && $roles['couadmin']));
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.9.1
   */
  
  function performRedirect() {
    if($this->action == 'add' && $this->petitionID()) {
      // We want to go back to http://valkyrie.local/registry/co_petitions/view/89/co:2/coef:12
      
      // After submission on add, we go back to CO People
      
      $this->redirect(array(
        'controller' => 'co_petitions',
        'action' => 'view',
        filter_var($this->petitionID(),FILTER_SANITIZE_SPECIAL_CHARS)
      ));
    } else {
      parent::performRedirect();
    }
  }
  
  /**
   * Determine the requested Petition ID.
   * - precondition: A petition ID should be specified as a named query parameter or in form data.
   *
   * @since  COmanage Registry v0.9.1
   * @return Integer CO Petition ID if found, or null otherwise
   */
  
  protected function petitionID() {
    if(!empty($this->params['named']['copetitionid'])) {
      return $this->params['named']['copetitionid'];
    } elseif(!empty($this->request->data['CoPetitionHistoryRecord']['co_petition_id'])) {
      return $this->request->data['CoPetitionHistoryRecord']['co_petition_id'];
    }
    
    return null;
  }
}
