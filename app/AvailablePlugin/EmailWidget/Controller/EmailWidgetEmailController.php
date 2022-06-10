<?php
/**
 * COmanage Registry Email Widget Email Address Controller
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class EmailWidgetEmailController extends StandardController {
  // Class name, used by Cake
  public $name = "EmailWidgetEmail";
  
  public function beforeFilter() {
    parent::beforeFilter();

    $this->RequestHandler->renderAs($this, 'json');
  }
  
  /**
   * Step 1 in adding an email address: 
   * Generate and mail a token for email verification.
   * Return the row id back for use in step two. 
   *
   * @since  COmanage Registry v4.1.0
   */
  public function gentoken() {
    if(!empty($this->request->params['named']['email']) &&
       !empty($this->request->params['named']['type'])) {
      
      $email = $this->request->params['named']['email'];
      $type = $this->request->params['named']['type'];
      $primary = $this->request->params['named']['primary'];
      $mtid = $this->request->params['named']['mtid'];
      
      $results = $this->EmailWidgetEmail->generateToken($email,$type,$primary);
      if(!empty($results['id'])) { // XXX Ensure this test is adequate.
        // We have a valid result. Provide the row ID to the verification form,
        // and send the token to the new email address for round-trip verification by the user.
        $this->set('vv_response_type', 'ok');
        $this->set('vv_id', $results['id']);
        $this->EmailWidgetEmail->send($email,$results['token'],$mtid);
      } else {
        $this->set('vv_response_type','error');
      }
      
    } else {
      $this->set('vv_response_type','badParams');
    }
  }
  
  /**
   * Step 2 in adding an email address:
   * Verify the token associated with the new address.
   *
   * @since  COmanage Registry v4.1.0
   */
  public function verify() {
    if(!empty($this->request->params['named']['token']) &&
       !empty($this->request->params['named']['id']) &&
       !empty($this->request->params['named']['copersonid'])) {
      $token = $this->request->params['named']['token'];
      $id = $this->request->params['named']['id'];
      // $coPersonId = $this->Session->read('Auth.User.co_person_id'); // XXX this returns the wrong id. Why?
      $coPersonId = $this->request->params['named']['copersonid'];
      $outcome = $this->EmailWidgetEmail->verify($token,$id,$coPersonId);
      $this->set('vv_outcome', $outcome);
      if($outcome == 'success') {
        $this->set('vv_response_type','ok');
      } else {
        $this->set('vv_response_type','error');
      }
    } else {
      $this->set('vv_response_type','badParams');
    }
  }
  
  /**
   * TODO: Refactor this to be correct
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
  
    // Add an email address?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Generate an email address token?
    $p['gentoken'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Verify Email
    $p['verify'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Self-service permission is true for all EmailAddress types
    $p['selfsvc']['EmailAddress']['*'] = true;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}