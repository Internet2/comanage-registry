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

class EmailAddressWidgetVerificationsController extends StandardController {
  // Class name, used by Cake
  public $name = "EmailAddressWidgetVerifications";

  private static $actions = array(
    'gentoken',
    'verify'
  );

  public $uses = array(
    'EmailAddressWidget.CoEmailAddressWidget',
    'EmailAddressWidget.EmailAddressWidgetVerification',
    'CoMessageTemplate'
  );

  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.1.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    if(!empty($this->request->params['pass'][0])) {
      $args = array();
      $args['conditions']['token'] = $this->request->params['pass'][0];
      $args['contain'] = array('CoEmailAddressWidget' => array('CoDashboardWidget' => array('CoDashboard')));
      $rec = $this->EmailAddressWidgetVerification->find('first',$args);
      if(isset($rec['CoEmailAddressWidget']["CoDashboardWidget"]["CoDashboard"]["co_id"])) {
        return $rec['CoEmailAddressWidget']["CoDashboardWidget"]["CoDashboard"]["co_id"];
      }
    }

    // Or try the default behavior
    return parent::calculateImpliedCoId();
  }

  /**
   * Step 2 in adding an email address:
   * Verify the token associated with the new address.
   *
   * @since  COmanage Registry v4.1.0
   */
  public function verify($token) {
    if(empty($token)) {
      $this->set('vv_response_type','badParams');
      return;
    }

    // Get the identifier of the logged in user
    $identifier = $this->Session->read('Auth.User.username');

    $outcome = $this->EmailAddressWidgetVerification->verify($token, $identifier, $this->cur_co["Co"]["id"]);
    $this->set('vv_outcome', $outcome);
    $this->set('vv_response_type','ok');
    if($outcome != 'success') {
      $this->set('vv_response_type','error');
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

    // Add a verification Request?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Delete a verification Request
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Verify Email
    $p['verify'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }

  /**
   * Override the default sanity check performed in AppController
   *
   * @since  COmanage Registry v4.1.0
   * @return Boolean True if sanity check is successful
   */

  public function verifyRequestedId() {
    return true;
  }
}