<?php
/**
 * COmanage Registry SLIP Binding Controller
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

class SlipBindingsController extends StandardController {
  // Class name, used by Cake
  public $name = "SlipBindings";
  
  public $uses = array('SlipPlugin.SlipBinding',
                       'SlipPlugin.SlipPlugin');
                       /*,
                       'AttributeEnumeration',
                       'IdentityDocument');*/
  /*
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'modified' => 'desc'
    )
  );*/

  // This controller needs a CO to be set
  public $requires_co = true;

  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v4.7.0
   */

  function beforeRender() {
    parent::beforeRender();

    // Pull the Proofing Request info for Breadcrumbs
    if(!empty($this->params['named']['sprid'])) {
      $args = array();
      $args['conditions']['SlipProofing.id'] = $this->params['named']['sprid'];
      $args['contain'] = array('ClaimantCoPerson' => 'PrimaryName');

      $this->set('vv_slip_proofing', $this->SlipBinding->SlipProofing->find('first', $args));
    }
  }

  /**
   * Bind an Authenticator Binding request.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  $id   SLIP Binding Request ID
   */

  public function bind($id) {
    // Pull the Binding request and pass it to the view
    $args = array();
    $args['conditions']['SlipBinding.id'] = $id;
    $args['contain'] = false;

    $binding = $this->SlipBinding->find('first', $args);

    // Because we need a landing page anyway, we'll leave the error reporting to the view
    $this->set('slip_binding', $binding);
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
    $sprid = null;

    if(in_array($this->action, array('index', 'request'))) {
      $sprid = $this->params->named['sprid'];
    }

    if($sprid) {
      // Map Slip Binding to Slip Proofing to CO

      $coId = $this->SlipBinding->SlipProofing->findCoForRecord($sprid);

      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.slip_proofings.1'), $coef)));
      }
    }

    return parent::calculateImpliedCoId();
  }

  /**
   * Confirm an Authenticator Binding request.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  $id   SLIP Binding Request ID
   */

  public function confirm($id) {
    if($this->request->is('post')) {
      if(!empty($this->request->data['SlipBinding']['code'])) {
        try {
          $this->SlipBinding->confirm($id, $this->request->data['SlipBinding']['code']);

          $this->redirect(array(
            'plugin'      => 'slip_plugin',
            'controller'  => 'slip_bindings',
            'action'      => 'index',
            'sprid'       => $this->request->data['SlipBinding']['slip_proofing_id']
          ));
        }
        catch(Exception $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
        }
      }
    }

    // Pull the Binding request and pass it to the view
    $args = array();
    $args['conditions']['SlipBinding.id'] = $id;
    $args['contain'] = false;

    $binding = $this->SlipBinding->find('first', $args);

    // If $id is invalid, we won't have any usable context to figure out where to redirect to,
    // so like bind.ctp we just let the view render the error.

    $this->set('slip_binding', $binding);
    $this->set('title_for_layout', _txt('ct.slip_bindings.pl'));
  }

  /**
   * Obtain all Standard Objects (of the model's type).
   * - postcondition: $<object>s set on success (REST or HTML), using pagination (HTML only)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v4.7.0
   */

  public function index() {
    // Pull the set of Authenticator Bindings/Org Identities

    if(!empty($this->params->named['sprid'])) {
      $this->set('slip_bindings', $this->SlipBinding->status($this->params->named['sprid']));
    }

    $this->set('title_for_layout', _txt('ct.slip_bindings.pl'));
  }

  /**
   * Send an Authenticator Binding request.
   * 
   * @since  COmanage Registry v4.7.0
   */

  public function request() {
    if(!empty($this->request->params['named']['sprid'])
       && !empty($this->request->params['named']['identifier'])) {
      try {
        $bid = $this->SlipBinding->request(
          $this->request->params['named']['sprid'],
          cmg_urldecode($this->request->params['named']['identifier']),
          $this->Session->read('Auth.User.co_person_id')
        );
        //$this->Flash->set(_txt('pl.slipplugin.scheduled', array($id)), array('key' => 'success'));

        $this->redirect(array(
          'plugin' => 'slip_plugin',
          'controller' => 'slip_bindings',
          'action' => 'confirm',
          $bid
        ));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));

        $this->redirect(array(
          'plugin' => 'slip_plugin',
          'controller' => 'slip_bindings',
          'action' => 'index',
          'sprid' => $this->request->params['named']['sprid']
        ));
      }
    }
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

    $isRegistrar = false;

    // Is the current CO Person a Registar?

    $cfgId = $this->SlipPlugin->getConfigId($this->cur_co['Co']['id']);

    $isRegistrar = $this->SlipPlugin->isRegistrar($cfgId, $this->Session->read('Auth.User.co_person_id'));

    // Add an Authenticator Binding?
    $p['add'] = false;

    // Perform an Authentictor Binding?
    // Any authenticated user can access the URL to bind an Authenticator, if there is no
    // matching authenticated identifier the request will fail
    $p['bind'] = true;

    // Confirm an Authenticator Binding?
    $p['confirm'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Delete an existing Authenticator Binding?
    $p['delete'] = false;

    // Edit an existing Authenticator Binding?
    $p['edit'] = false;
    
    // View all existing Authenticator Binding?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Request an Authenticator Binding?
    $p['request'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Revoke an Authenticator Binding?
    $p['revoke'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);
    
    // View an existing Authenticator Binding?
    $p['view'] = false;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
