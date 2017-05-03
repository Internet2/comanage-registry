<?php
/**
 * COmanage Registry Test Confirmer Controller
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

 App::uses("StandardController", "Controller");

class TestConfirmersController extends StandardController {
  // Class name, used by Cake
  public $name = "TestConfirmers";
  
  public $uses = array('TestConfirmer.TestConfirmer', 'CoEnrollmentFlow', 'CoInvite');

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Auth component is configured 
   *
   * @since  COmanage Registry v3.1.0
   * @throws UnauthorizedException (REST)
   */
  
  function beforeFilter() {
    // Since we're overriding, we need to call the parent to run the authz check
    parent::beforeFilter();
    
    // Allow invite handling to process without a login page
    $this->Auth->allow('reply');
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Reply to an invitation?
    $p['reply'] = true;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Handle an invitation reply request. Note: This is intended to render a confirmation
   * page, NOT to actually process the reply.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $inviteid CO Invitation ID
   */
  
  function reply($inviteid) {
    // Pull the data here that your view will need. eg:
    // (If you want to use the default confirmation buttons, you need to set $invite
    // and $co_enrollment_flow.)
    
    $args = array();
    $args['conditions']['CoInvite.invitation'] = $inviteid;
    $args['contain'] = array('CoPetition', 'EmailAddress');
    
    $invite = $this->CoInvite->find('first', $args);
    
    $this->set('invite', $invite);
    
    $args = array();
    $args['conditions']['CoPerson.id'] = $invite['CoInvite']['co_person_id'];
    $args['contain'] = array('Co', 'PrimaryName');
    
    $invitee = $this->CoInvite->CoPerson->find('first', $args);
    
    $this->set('invitee', $invitee);
    
    if(!empty($invite['CoPetition']['co_enrollment_flow_id'])) {
      $args = array();
      $args['conditions']['CoEnrollmentFlow.id'] = $invite['CoPetition']['co_enrollment_flow_id'];
      $args['contain'][] = 'CoEnrollmentAttribute';
      
      $enrollmentFlow = $this->CoEnrollmentFlow->find('first', $args);
      
      $this->set('co_enrollment_flow', $enrollmentFlow);
    }
  }
}
