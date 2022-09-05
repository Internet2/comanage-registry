<?php
/**
 * COmanage Registry Email Address Widget Email Address Controller
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

class EmailAddressWidgetVerificationRequestsController extends StandardController {
  // Class name, used by Cake
  public $name = "EmailAddressWidgetVerificationRequests";

  private static $actions = array(
    'gentoken',
    'verify'
  );

  public $uses = array(
    'EmailAddressWidget.CoEmailAddressWidget',
    'EmailAddressWidget.EmailAddressWidgetVerificationRequest',
    'CoMessageTemplate'
  );

  public function beforeFilter() {
    parent::beforeFilter();

    if(in_array($this->action, EmailAddressWidgetVerificationRequestsController::$actions)) {
      $this->Security->validatePost = false;
      $this->Security->enabled = false;
      $this->Security->csrfCheck = false;
    }

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
    if(!empty($this->request->data['email'])
       && !empty($this->request->data["email_address_widget_id"])) {
      $this->CoEmailAddressWidget->id = $this->request->data["email_address_widget_id"];

      $email = $this->request->data['email'];
      
      $results = $this->EmailAddressWidgetVerificationRequest->generateToken($email,$this->CoEmailAddressWidget->field('default_type'));
      if(!empty($results['id'])) {
        // We have a valid result. Provide the row ID to the verification form,
        // and send the token to the new email address for round-trip verification by the user.
        $this->set('vv_response_type', 'ok');
        $this->set('vv_id', $results['id']);
        $this->EmailAddressWidgetVerificationRequest->send($email,$results['token'], $this->CoEmailAddressWidget->field('co_message_template_id'));
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
    if(!empty($this->request->data['token']) &&
       !empty($this->request->data['id']) &&
       !empty($this->request->data['copersonid'])) {
      $token = $this->request->data['token'];
      $id = $this->request->data['id'];
      $coPersonId = $this->request->data['copersonid'];
      $outcome = $this->EmailAddressWidgetVerificationRequest->verify($token,$id,$coPersonId);
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
   * Find the provided CO ID from the query string for the reconcile action
   * or invoke the parent method.
   * - precondition: A coid should be provided in the query string
   *
   * @since  COmanage Registry v4.1.0
   * @return Integer The CO ID if found, or -1 if not
   */

  public function parseCOID($data = null) {
    if($this->request->method() == "POST"
       && isset($this->request->data["coid"])
       && in_array($this->action, EmailAddressWidgetVerificationRequestsController::$actions)) {
      return $this->request->data["coid"];
    }

    return parent::parseCOID($data);
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

    // Add an email address?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Generate an email address token?
    $p['gentoken'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Verify Email
    $p['verify'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}