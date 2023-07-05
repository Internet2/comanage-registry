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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SDWController", "Controller");

class CoPasswordWidgetsController extends SDWController {
  // Class name, used by Cake
  public $name = "CoPasswordWidgets";
  
  public $uses = array(
    'PasswordWidget.CoPasswordWidget',
    'Authenticator',
    'PasswordAuthenticator.PasswordAuthenticator',
    'CO'
  );

  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v4.3.0
   */

  public function beforeFilter() {
    if(!empty($this->request->data["Password"]["co_password_widget_id"])
       && $this->action == "manage") {
      $args = array();
      $args['conditions']["CoPasswordWidget.id"] = $this->request->data["Password"]["co_password_widget_id"];
      $args['contain'] = false;

      $this->CoPasswordWidget->setConfig($this->CoPasswordWidget->find('first', $args));
    }

    // For ajax i accept only json format
    if( $this->request->is('ajax') ) {
      $this->RequestHandler->addInputType('json', array('json_decode', true));
    }

    parent::beforeFilter();
  }


  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.3.0
   */

  public function beforeRender() {
    // Gather the available PasswordAuthenticator authenticators for the config form
    $args = array();
    $args['conditions']['co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['plugin'] = 'PasswordAuthenticator';
    $this->set('vv_available_authenticators', $this->Authenticator->find('list', $args));
  
    // Pass the config
    $cfg = $this->CoPasswordWidget->getConfig();
    $this->set('vv_config', $cfg);

    parent::beforeRender();
  }
  
  /**
   * Render the widget according to the requested user and current configuration.
   *
   * @since  COmanage Registry v4.3.0
   * @param  Integer $id CO Services Widget ID
   */
  
  public function display($id) {
    // We need the CoPerson ID - with that we can look up the Passwords via 
    // ajax against the API in the web client.
    $this->set('vv_co_person_id', $this->reqCoPersonId);
    $this->set('vv_co_id', $this->cur_co['Co']['id']);

    // Get the password information
    $args = array();
    $args['conditions']['authenticator_id'] = $this->CoPasswordWidget->getConfig()['CoPasswordWidget']['authenticator_id'];
    $pwAuthenticator = $this->PasswordAuthenticator->find('first', $args);
    $this->set('vv_pw_authenticator', $pwAuthenticator);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.3.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();

    // Determine what operations this user can perform
    
    // Construct the permission set for this user, which will also be passed to the view.
    // Ask the parent to calculate the display permission, based on the configuration.
    // Note that the display permission is set at the Dashboard, not Dashboard Widget level.
    $p = $this->calculateParentPermissions($roles);

    // Delete an existing CO Password Widget?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Password Widget?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Password Widget?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Get list of password
    $p['passwords'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // Manage passwords
    $p['manage'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }


  /**
   * Add or reset passwords (manage)
   *
   * @since  COmanage Registry v4.3.0
   */

  public function manage() {
    $this->request->allowMethod('ajax');
    $this->layout = 'ajax';

    if (!$this->request->is('restful')) {
      throw new MethodNotAllowedException();
    }

    $model = $this->name;
    $req   = Inflector::singularize($model);

    $cfg = $this->CoPasswordWidget->getConfig();
    // Get the authenticator
    $args = array();
    $args['conditions']['authenticator_id'] = $cfg['CoPasswordWidget']['authenticator_id'];
    $pwAuthenticator = $this->PasswordAuthenticator->find('first', $args);

    $this->PasswordAuthenticator->setConfig($pwAuthenticator);

    try {
      $data = array(
        'Password' => array(
          'password_authenticator_id' => $this->request->data["Password"]["password_authenticator_id"],
          'co_person_id'              => $this->request->data["Password"]["co_person_id"],
          'password'                  => $this->request->data["Password"]["password"],
          'password2'                 => $this->request->data["Password"]["password2"],
          // We do not care about the password type since this is handled from the authenticator
          // 'password_type'           => $this->request->data["Password"]["PasswordType"]
        )
      );

      // This is a reset
      if(!empty($this->request->data["Current"])) {
        $data['Password']['passwordc'] = $this->request->data["Current"]['password'];
      }

      // Password Authenticators might save more than one records at one pass because they take into consideration
      // all the different types of passwords.
      $r = $this->PasswordAuthenticator->manage($data, $this->request->data["Password"]["co_person_id"]);
      // Trigger provisioning
      $this->PasswordAuthenticator->Authenticator->provision($this->request->data["Password"]["co_person_id"]);
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_CREATED);
      if(!empty($this->request->data["Current"])) {
        $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_OK);
      }
      $resp = array(
        "ObjectType" => $req,
        "comment"    => $r
      );
    } catch (InvalidArgumentException $e) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_BAD_REQUEST);
      $resp = array(
        "ObjectType" => $req,
        "error"      => $e->getMessage()
      );
    } catch (Exception $e) {
      throw new InternalErrorException($e->getMessage());
    }
    $this->set(compact('resp'));
    $this->set('_serialize', 'resp');
  }

  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v4.3.0
   * @return Integer The CO ID if found, or -1 if not
   */

  public function parseCOID($data = null) {
    if($this->action == 'display') {
      return parent::parseCOID($data);
    }
    $cfg = $this->CoPasswordWidget->getConfig();
    $this->CoPasswordWidget->CoDashboardWidget->id = $cfg['CoPasswordWidget']["co_dashboard_widget_id"];
    $this->CoPasswordWidget->CoDashboardWidget->CoDashboard->id = $this->CoPasswordWidget->CoDashboardWidget->field('co_dashboard_id');
    $co_id = $this->CoPasswordWidget->CoDashboardWidget->CoDashboard->field('co_id');

    if(!empty($co_id)) {
      return $co_id;
    }
  }


  /**
   * Add or reset passwords (manage)
   *
   * @since  COmanage Registry v4.3.0
   */

  public function passwords($id) {
    $this->request->allowMethod('ajax');
    $this->layout = 'ajax';

    if (!$this->request->is('restful')) {
      throw new MethodNotAllowedException();
    }

    $coPersonId = $this->Session->read('Auth.User.co_person_id');
    if (empty($coPersonId)) {
      throw new BadRequestException();
    }

    $model = $this->name;
    $req   = Inflector::singularize($model);

    $cfg = $this->CoPasswordWidget->getConfig();
    // Get the authenticator
    $args = array();
    $args['conditions']['authenticator_id'] = $cfg['CoPasswordWidget']['authenticator_id'];
    $pwAuthenticator = $this->PasswordAuthenticator->find('first', $args);

    $this->PasswordAuthenticator->setConfig($pwAuthenticator);

    // Get the person passwords
    $args = array();
    $args['conditions']['Password.co_person_id'] = $coPersonId;
    $args['conditions']['Password.password_authenticator_id'] = $pwAuthenticator['PasswordAuthenticator']["id"];
    $args['contain'] = false;

    try {
      $resp = $this->PasswordAuthenticator->Password->find('all', $args);
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_OK);
    } catch (InvalidArgumentException $e) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_BAD_REQUEST);
      $resp = array(
        "ObjectType" => $req,
        "error"      => $e->getMessage()
      );
    } catch (Exception $e) {
      throw new InternalErrorException($e->getMessage());
    }

    $this->set(compact('resp'));
    $this->set('_serialize', 'resp');
  }

  /**
   * Override the default sanity check performed in AppController
   *
   * @since  COmanage Registry v4.3.0
   * @return Boolean True if sanity check is successful
   */

  public function verifyRequestedId() {
    return true;
  }
}
