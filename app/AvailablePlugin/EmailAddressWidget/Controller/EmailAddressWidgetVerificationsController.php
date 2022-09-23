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
      $rec = $this->EmailAddressWidgetVerification->getRecordToVerify($this->request->params['pass'][0]);
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
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_BAD_REQUEST, _txt('er.token'));
      return false;
    }

    // Retrieve the Verification record and the configuration
    $rec = $this->EmailAddressWidgetVerification->getRecordToVerify($token);
    // Check if the person owns the records and can proceed with the verification
    $CoPerson = ClassRegistry::init('CoPerson');
    $actorCoPersonId = $CoPerson->idForIdentifier($this->cur_co["Co"]["id"], $this->Session->read('Auth.User.username'));

    if($rec['EmailAddressWidgetVerification']['co_person_id'] != $actorCoPersonId) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_NOT_FOUND,
                                   _txt('er.cop.nf', array($actorCoPersonId)));
      return false;
    }

    try {
      if(!$this->EmailAddressWidgetVerification->checkValidity($token)) {
        $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_NOT_ACCEPTABLE,
                                     _txt('er.emailaddresswidget.timeout'));
        return false;
      }
      $this->set('email_address_id', $this->EmailAddressWidgetVerification->addEmailToPerson($token, $actorCoPersonId));

      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_CREATED, _txt('rs.added-a3', array($rec['EmailAddressWidgetVerification']['email'])));
    } catch (Exception $e) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_BAD_REQUEST, $e->getMessage());
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