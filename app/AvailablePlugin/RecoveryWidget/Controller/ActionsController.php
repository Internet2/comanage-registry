<?php
/**
 * COmanage Registry CO Recovery Widget Actions Controller
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

App::uses("SDWController", "Controller");

class ActionsController extends SDWController {
  // Class name, used by Cake
  public $name = "Actions";
  
  public $uses = array(
    'RecoveryWidget.CoRecoveryWidget',
    'RecoveryWidget.RecoveryWidget'
  );

  // Our current configuration
  protected $config = null;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v4.1.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Many of our actions require anonymous access
    if(!empty($this->config['CoDashboardWidget']['CoDashboard']['visibility'])
              && $this->config['CoDashboardWidget']['CoDashboard']['visibility'] == VisibilityEnum::Unauthenticated) {
      if($this->action == 'lookup') {
        $this->Auth->allow('lookup');
      }
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.1.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    // ActionsController doesn't have any actual models, so we need lookup the CO ID based on
    // the request.

    if(!empty($this->request->params['named']['recoverywidgetid'])) {
      // Pull the CO from the configuration

      $args = array();
      $args['conditions']['CoRecoveryWidget.id'] = $this->request->params['named']['recoverywidgetid'];
      $args['contain'] = array('CoDashboardWidget' => 'CoDashboard');

      // We lookup the configuration first, but we'll store it for other uses within this controller
      $this->config = $this->CoRecoveryWidget->find('first', $args);

      if(!empty($this->config['CoDashboardWidget']['CoDashboard']['co_id'])) {
        return $this->config['CoDashboardWidget']['CoDashboard']['co_id'];
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_recovery_widgets.1'),
                                                      filter_var($this->request->params['named']['recoverywidgetid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }

    return parent::calculateImpliedCoId();
  }
  
  /**
   * Lookup a CO Person based on a search key and a requested task.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Integer $id  Widget ID
   */
  
  public function lookup() {
    // Confirm the requested action is enabled. We do this outside the try/catch block
    // because we don't want to render the form if the action is not enabled. While we're
    // here, also set the page title.

    $enabled = false;

    if(!empty($this->request->params['named']['task'])) {
      switch($this->request->params['named']['task']) {
        case 'authenticator_reset':
          $enabled = !empty($this->config['CoRecoveryWidget']['authenticator_id'])
                     && !empty($this->config['CoRecoveryWidget']['authenticator_reset_template_id']);
          $this->set('vv_title', _txt('pl.recoverywidget.task.authenticator_reset'));

          // If Authenticator Reset is enabled, pass the URL to the direct credential change URL.
          // This is similar to CoRecoveryWidgetsController.
          if(!empty($this->config['CoRecoveryWidget']['authenticator_id'])) {
            $resetUrl = array(
              'plugin'          => 'password_authenticator',
              'controller'      => 'passwords',
              'action'          => 'manage',
              'authenticatorid' => $this->config['CoRecoveryWidget']['authenticator_id']
            );

            $this->set('vv_authenticator_change_url', $resetUrl);
          }
          break;
        case 'confirmation_resend':
          $enabled = $this->config['CoRecoveryWidget']['enable_confirmation_resend'];
          $this->set('vv_title', _txt('pl.recoverywidget.task.confirmation_resend'));
          break;
        case 'identifier_lookup':
          $enabled = //!empty($this->config['CoRecoveryWidget']['identifier_type'])
                     !empty($this->config['CoRecoveryWidget']['identifier_template_id']);
          $this->set('vv_title', _txt('pl.recoverywidget.task.identifier_lookup'));
          break;
        default:
          break;
      }
    }

    if(!$enabled) {
      $this->Flash->set(_txt('er.recoverywidget.disabled'), array('key' => 'error'));
      $this->redirect('/');
      return;
    }

    $this->set('vv_task', $this->request->params['named']['task']);

    try {
      if($this->request->is('post') && !empty($this->request->data['q'])) {
        switch($this->request->params['named']['task']) {
          case 'authenticator_reset':
            $this->RecoveryWidget->sendAuthenticatorReset($this->cur_co['Co']['id'],
                                                          $this->config['CoRecoveryWidget'],
                                                          $this->request->data['q'],
                                                          // Since this is intended to be a self
                                                          // service reset tool, the co_person_id
                                                          // will usually be empty
                                                          $this->Session->read('Auth.User.co_person_id'));
            break;
          case 'confirmation_resend':
            $this->RecoveryWidget->resendConfirmation($this->cur_co['Co']['id'], 
                                                      $this->request->data['q'],
                                                      // Since this is intended to be a self
                                                      // service reset tool, the co_person_id
                                                      // will usually be empty
                                                      $this->Session->read('Auth.User.co_person_id'));
            break;
          case 'identifier_lookup':
            $this->RecoveryWidget->sendIdentifier($this->cur_co['Co']['id'], 
                                                  $this->request->data['q'],
                                                  $this->config['CoRecoveryWidget']['identifier_template_id'],
                                                  // Since this is intended to be a self
                                                  // service reset tool, the co_person_id
                                                  // will usually be empty
                                                  $this->Session->read('Auth.User.co_person_id'));
            break;
          default;
            throw new RuntimeException('NOT IMPLEMENTED');
            break;
        }

        // If we made it here, we successfully processed the lookup request (which may or may
        // not have actually matched a record). We render success but let the form render again
        // anyway, in case the user wants to try again.
        $this->Flash->set(_txt('pl.recoverywidget.sent'), array('key' => 'success'));
      }
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
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

    // Delete an existing CO Recovery Widget?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Recovery Widget?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Recovery Widget?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
