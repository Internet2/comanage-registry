<?php
/**
 * COmanage Registry SLIP Proofing Controller
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class SlipProofingsController extends StandardController {
  // Class name, used by Cake
  public $name = "SlipProofings";

  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'modified' => 'desc'
    )
  );

  // This controller needs a CO to be set
  public $requires_co = true;

  // We need various related models for index and search
  public $view_contains = array(
    'ClaimantCoPerson' => array('PrimaryName'),
    'InitiatorCoPerson' => array('PrimaryName'),
    'RegistrarCoPerson' => array('PrimaryName'),
    'VoucherCoPerson' => array('PrimaryName')
  );

  /**
   * Accept (assign to self) an Identity Proofing request.
   *
   * @since  COmanage Registry v4.7.0
   * @param  $id   SLIP Proofing ID
   */

  public function accept($id) {
    try {
      $this->SlipProofing->accept($id, $this->Session->read('Auth.User.co_person_id'));
      $this->Flash->set(_txt('pl.slipplugin.assigned'), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }

    $this->redirect(array(
      'plugin' => 'slip_plugin',
      'controller' => 'slip_proofings',
      'action' => 'view',
      $id
    ));
  }

  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v4.7.0
   */

  function beforeRender() {
    parent::beforeRender();

    // Pull the Plugin info for Breadcrumbs (for index when there are no Identity Proofings)

    if(!empty($this->params['named']['coperson'])) {
      $args = array();
      $args['conditions']['ClaimantCoPerson.id'] = $this->params['named']['coperson'];
      $args['contain'] = array('PrimaryName');

      $this->set('vv_co_person', $this->SlipProofing->ClaimantCoPerson->find('first', $args));
    }
  }

  /**
   * Calculate Identity Proofing status.
   *
   * @since  COmanage Registry v4.7.0
   */

  public function calculate() {
    try {
      if(empty($this->params['named']['coperson'])) {
        throw new InvalidArgumentException(_txt('er.notprov.id', _txt('ct.co_people.1')));
      }

      // Map the CO to its plugin configuration ID
      $id = $this->SlipProofing->SlipPlugin->getConfigId($this->cur_co['Co']['id']);

      $status = $this->SlipProofing->calculate(
        $id,
        $this->params['named']['coperson'],
        $this->Session->read('Auth.User.co_person_id')
      );

      $this->set('vv_status', $status);

      if(isset($status['result']) && $status['result']) {
        $this->Flash->set(_txt('pl.slipplugin.calculate.ok', array($id)), array('key' => 'success'));
      } else {
        throw new RuntimeException(_txt('er.slipplugin.calculate'));
      }
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }

    // Pass info to the view

    $args = array();
    $args['conditions']['ClaimantCoPerson.id'] = $this->params['named']['coperson'];
    $args['contain'] = array('PrimaryName');

    $this->set('vv_claimant_co_person', $this->SlipProofing->ClaimantCoPerson->find('first', $args));

    $args = array();
    $args['conditions']['SlipPlugin.id'] = $id;
    $args['contain'] = false;

    $this->set('vv_slip_plugin', $this->SlipProofing->SlipPlugin->find('first', $args));

    $this->set('title_for_layout', _txt('pl.slipplugin.calculate'));

    // We want the landing page to render to show detailed status
  }

  /**
   * Cancel an Identity Proofing request.
   *
   * @since  COmanage Registry v4.7.0
   * @param  $id   SLIP Proofing ID
   */

  public function cancel($id) {
    try {
      $this->SlipProofing->cancel($id, $this->Session->read('Auth.User.co_person_id'));
      $this->Flash->set(_txt('pl.slipplugin.canceled', array($id)), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }

    $this->redirect(array(
      'plugin' => 'slip_plugin',
      'controller' => 'slip_proofings',
      'action' => 'index',
      'co' => $this->cur_co['Co']['id']
    ));
  }

  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.7.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    $spid = null;

    if($this->action == 'index' && !empty($this->params->named['co'])) {
      // The menuing will inject a CO ID, but we need a Slip Plugin ID

      $cfgId = $this->SlipProofing->SlipPlugin->getConfigId($this->params->named['co']);

      $this->redirect(array(
        'plugin' => 'slip_plugin',
        'controller' => 'slip_proofings',
        'action' => 'index',
        'spid' => $cfgId
      ));
    } elseif($this->action == 'index'
             && empty($this->params->named['spid'])
             && !empty($this->params->named['copersonid'])) {
      // The (canvas) menuing will inject a CO Person ID, but we need a Slip Plugin ID

      $coId = $this->SlipProofing->ClaimantCoPerson->findCoForRecord($this->params->named['copersonid']);

      if($coId) {
        $cfgId = $this->SlipProofing->SlipPlugin->getConfigId($coId);

        $this->redirect(array(
          'plugin' => 'slip_plugin',
          'controller' => 'slip_proofings',
          'action' => 'index',
          'spid' => $cfgId,
          'coperson' => $this->params->named['copersonid']
        ));
      }
    } elseif(in_array($this->action, array('add', 'index'))) {
      $spid = $this->params->named['spid'];
    } elseif($this->action == 'calculate') {
      // For validate we'll only have a CO Person ID

      if(!empty($this->params['named']['coperson'])) {
        return $this->SlipProofing->ClaimantCoPerson->findCoForRecord($this->params['named']['coperson']);
      }
    } elseif(!empty($this->request->data['SlipProofing']['slip_plugin_id'])) {
      $spid = $this->request->data['SlipProofing']['slip_plugin_id'];
    }

    if($spid) {
      // Map Slip Plugin to CO

      $coId = $this->SlipProofing->SlipPlugin->field('co_id', array('id' => $spid));

      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.slip_plugins.1'), $coef)));
      }
    }

    return parent::calculateImpliedCoId();
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.7.0
   * @return Array An array suitable for use in $this->paginate
   */

  function paginationConditions() {
    $ret = array();

    if(!empty($this->params['named']['spid'])) {
      $ret['conditions']['SlipProofing.slip_plugin_id'] = $this->params['named']['spid'];
    }

    if(!empty($this->params['named']['coperson'])) {
      $ret['conditions']['SlipProofing.co_person_id'] = $this->params['named']['coperson'];
    }

    return $ret;
  }

  /**
   * Request an Identity Vouching.
   *
   * @since  COmanage Registry v4.7.0
   * @param  $id   SLIP Proofing ID
   */

  public function request($id) {
    if($this->request->is('post')) {
      // debug($this->request->data);

      if(!empty($this->request->data['SlipProofing']['voucher_co_person_id'])) {
        try {
          $this->SlipProofing->assignVoucher(
            $id,
            $this->request->data['SlipProofing']['voucher_co_person_id'],
            $this->Session->read('Auth.User.co_person_id')
          );
          $this->Flash->set(_txt('pl.slipplugin.assigned'), array('key' => 'success'));

          $this->redirect(array(
            'plugin'      => 'slip_plugin',
            'controller'  => 'slip_proofings',
            'action'      => 'view',
            $id
          ));
        }
        catch(Exception $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
        }
      }
    }

    // Pass request info to the view

    $args = array();
    $args['conditions']['SlipProofing.id'] = $id;
    $args['contain'] = $this->view_contains;

    // We use 'all' for consistency with StandardController
    $this->set('slip_proofings', $this->SlipProofing->find('all', $args));
    $this->set('title_for_layout', _txt('pl.slipplugin.vouch'));
  }

  /**
   * Schedule an Identity Proofing request.
   *
   * @since  COmanage Registry v4.7.0
   * @param  $id   SLIP Proofing ID
   */

  public function schedule($id) {
    try {
      $this->SlipProofing->schedule($id, $this->Session->read('Auth.User.co_person_id'));
      $this->Flash->set(_txt('pl.slipplugin.scheduled', array($id)), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }

    $this->redirect(array(
      'plugin' => 'slip_plugin',
      'controller' => 'slip_proofings',
      'action' => 'view',
      $id
    ));
  }

  /**
   * Vouch for the Claimant of an Identity Proofing request.
   *
   * @since  COmanage Registry v4.7.0
   * @param  int   SLIP Proofing ID
   */

  public function vouch($id) {
    if(!empty($this->params->named['op'])) {
      try {
        $this->SlipProofing->vouch(
          $id,
          $this->Session->read('Auth.User.co_person_id'),
          $this->params->named['op'] == 'confirm'
        );

        $key = ($this->params->named['op'] == 'confirm') ? 'pl.slipplugin.completed' : 'pl.slipplugin.canceled';

        $this->Flash->set(_txt($key), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }

      // We don't really have a place to send someone, so we just let the view render
      // so the flash renders, but with a hint to not render anything else.
      $this->set('vv_done', $this->params->named['op']);
    }

    // Pass request info to the view

    $args = array();
    $args['conditions']['SlipProofing.id'] = $id;
    $args['contain'] = $this->view_contains;
    $args['contain']['SlipIdentityDocument'] = 'IdentityDocument';
    // Include names of the participants
    $args['contain']['ClaimantCoPerson'] = 'PrimaryName';
    $args['contain']['VoucherCoPerson'] = 'PrimaryName';
    $args['contain']['RegistrarCoPerson'] = 'PrimaryName';
    // Note adding SlipAttachment will cause debug() to fail, since it seems to trip
    // over having binary data in the array
    $args['contain'][] = 'SlipAttachment';

    // We use 'all' for consistency with StandardController
    $this->set('slip_proofings', $this->SlipProofing->find('all', $args));
    $this->set('title_for_layout', _txt('pl.slipplugin.vouch'));
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.7.0
   * @return Array Permissions
   */

  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();

    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();

    // Determine what operations this user can perform

    // The terminology is a bit inconsistent here, eg 'attach' is to manage Identity Documents,
    // 'upload' is to manage Attachments.

    $isRegistrar = false;

    // Is the current CO Person a Registar?

    $cfgId = $this->SlipProofing->SlipPlugin->getConfigId($this->cur_co['Co']['id']);

    // The Registrar coordinates the Proofing Request
    $isRegistrar = $this->SlipProofing->SlipPlugin->isRegistrar($cfgId, $this->Session->read('Auth.User.co_person_id'));

    // The Voucher is the Trusted Party for Validation
    $isVoucher = false;

    if($this->action == 'vouch' && !empty($this->request->params['pass'][0])) {
      $isVoucher = $this->SlipProofing->isVoucher($this->request->params['pass'][0], $this->Session->read('Auth.User.co_person_id'));
    }

    // Accept a Proofing Request
    $p['accept'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Manage Identity Documents?
    $p['attach'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // SLIP Proofings are initiated via SlipPlugin::start
    $p['add'] = false;

    // Manage Authenticator Bindings?
    $p['bind'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Validate Identity Proofing status
    // We use calculate instead of validate, since the latter is a cake function
    $p['calculate'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Cancel an in-progress Proofing?
    $p['cancel'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Delete an existing SLIP Proofing?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Edit an existing SLIP Proofing?
    $p['edit'] = false;

    // View all existing SLIP Proofing?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Request Vouching
    $p['request'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Schedule a Proofing Request
    $p['schedule'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Manage Attachments?
    $p['upload'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // View an existing SLIP Proofing?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Perform Vouching
    // We allow the Registrar to view this URL as a convenience, though they should not perform
    // the vouching unless they are assigned as the Voucher
    $p['vouch'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar || $isVoucher);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
