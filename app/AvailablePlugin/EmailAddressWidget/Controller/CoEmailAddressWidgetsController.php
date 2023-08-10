<?php
/**
 * COmanage Registry CO Email Address Widgets Controller
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

App::uses("SDWController", "Controller");

class CoEmailAddressWidgetsController extends SDWController {
  // Class name, used by Cake
  public $name = "CoEmailAddressWidgets";
  
  public $uses = array(
    'EmailAddressWidget.CoEmailAddressWidget',
    'EmailAddress',
    'CO',
    'CoMessageTemplate'
  );

  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.1.0
   */

  public function beforeRender() {
    // Gather the available email address types for the config form
    $this->set('vv_available_types', $this->EmailAddress->types($this->cur_co['Co']['id'], 'type'));

    // Gather message templates for the config form
    $args = array();
    $args['conditions']['status'] = SuspendableStatusEnum::Active;
    $args['conditions']['context'] = array(
      MessageTemplateEnum::EnrollmentVerification,
      MessageTemplateEnum::Plugin
    );
    $args['contain'] = false;
    $this->set('vv_message_templates', $this->CoMessageTemplate->find('list',$args));
  
    // Pass the config
    $cfg = $this->CoEmailAddressWidget->getConfig();
    $this->set('vv_config', $cfg);

    parent::beforeRender();
  }
  
  /**
   * Render the widget according to the requested user and current configuration.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Integer $id CO Services Widget ID
   */
  
  public function display($id) {
    // We need only the CoPerson ID - with that we can look up the Email Addresses via 
    // ajax against the API in the web client.
    $this->set('vv_co_person_id', $this->reqCoPersonId);
    $this->set('vv_co_id', $this->cur_co['Co']['id']);

    // Gather the available email address types
    $availableTypes = $this->EmailAddress->types($this->cur_co['Co']['id'], 'type');
    $this->set('vv_available_types', $availableTypes);
  }

  /**
   * Step 1 in adding an email address:
   * Generate and mail a token for email verification.
   * Return the row id back for use in step two.
   *
   * @since  COmanage Registry v4.1.0
   */
  public function gentoken($id) {
    $this->CoEmailAddressWidget->id = $id;
    $this->layout = 'ajax';

    if (empty($this->request->query['email'])
       || empty($this->request->query['type'])
       || empty($this->request->query['copersonid'])
       || empty($this->request->query['isprimary'])) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_BAD_REQUEST, _txt('er.emailaddresswidget.req.params'));
      return;
    }

    // I need to verify that the CO Person is part of the CO
    $copersonid = $this->request->query['copersonid'];
    if(!$this->Role->isCoPerson($copersonid, $this->cur_co["Co"]["id"])) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_NOT_FOUND, _txt('er.cop.nf', array($copersonid)));
      return;
    }

    $results = $this->CoEmailAddressWidget->generateToken(
      $this->request->query['email'],
      $this->request->query['type'],
      $this->request->query['copersonid'],
      $this->request->query['isprimary']
    );

    // Return if we fail to create and save the token
    if(empty($results['token'])) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_BAD_REQUEST, _txt('er.token'));
      return;
    }

    // We have a valid result. Provide the row ID to the verification form,
    // and send the token to the new email address for round-trip verification by the user.
    $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_CREATED,  _txt('fd.created'));
    $this->set('vv_token', $results['token']);
    $this->CoEmailAddressWidget->send($this->request->query['email'],
                                      $results['token'],
                                      $this->CoEmailAddressWidget->field('co_message_template_id'));
  }
  
  /**
   * Set an email address type to the configured "primary" type.
   * Change any existing addresses of the same type to the default type.
   *
   * @param  Integer $id                 CO Services Widget ID
   * @param  Integer $_GET['emailid']    ID of email address to be changed (from query string)
   * @param  String  $_GET['dtype']      Default email address type (from query string)
   * @param  String  $_GET['ptype']      Primary email address type (from query string)
   * @since  COmanage Registry v4.3.0
   */
  public function makePrimary($id) {
    $this->CoEmailAddressWidget->id = $id;
    $this->layout = 'ajax';
    
    // Ensure we have all the query string params that we require
    // Passing in the default and primary email address types (rather than looking them up
    // in the plugin configuration) ensures that they are set to defaults if not yet configured.
    // These fall back to "official" and "preferred" in View/display.ctp if not set in configuration.
    if (empty($this->request->query['emailid']) ||
        empty($this->request->query['dtype']) ||
        empty($this->request->query['ptype'])) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_BAD_REQUEST, _txt('er.emailaddresswidget.req.params'));
      return;
    }
    
    // Verify that the CO Person is part of the CO
    $copersonid = $this->reqCoPersonId;
    if(!$this->Role->isCoPerson($copersonid, $this->cur_co["Co"]["id"])) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_NOT_FOUND, _txt('er.cop.nf', array($copersonid)));
      return;
    }
    
    // Give the email address the primary email address type
    $results = $this->CoEmailAddressWidget->setPrimary(
      $this->request->query['emailid'],
      $this->request->query['dtype'],
      $this->request->query['ptype']
    );
  
    // Return if we fail
    if(empty($results)) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_BAD_REQUEST, _txt('er.emailaddresswidget.make.primary'));
      return;
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

    // Determine what operations this user can perform
    
    // Construct the permission set for this user, which will also be passed to the view.
    // Ask the parent to calculate the display permission, based on the configuration.
    // Note that the display permission is set at the Dashboard, not Dashboard Widget level.
    $p = $this->calculateParentPermissions($roles);

    // Delete an existing CO Email Widget?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Email Widget?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Email Widget?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Generate an email verification token
    $p['gentoken'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    
    // Make an email address's type the "primary" email address type as configured in the plugin
    $p['makeprimary'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
