<?php
/**
 * COmanage Registry SLIP Plugin Controller
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

class SlipPluginsController extends StandardController {
  // Class name, used by Cake
  public $name = "SlipPlugins";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'id' => 'asc'
    )
  );

  // This controller needs a CO to be set
  public $requires_co = true;

  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v4.7.0
   */

  function beforeRender() {
    parent::beforeRender();
    
    // Pull the list of available groups.

    $args = array();
    $args['conditions']['RegistrarCoGroup.co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['RegistrarCoGroup.status'] = SuspendableStatusEnum::Active;
    $args['order'] = 'RegistrarCoGroup.name ASC';
    $args['contain'] = false;

    $this->set('vv_available_groups', $this->SlipPlugin->RegistrarCoGroup->find('list', $args));

    // And available Message Templates

    $args = array();
    $args['conditions']['InitiationMessageTemplate.co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['InitiationMessageTemplate.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['InitiationMessageTemplate.context'] = MessageTemplateEnum::Plugin;
    $args['order'] = 'InitiationMessageTemplate.description ASC';
    $args['contain'] = false;

    $this->set('vv_available_templates', $this->SlipPlugin->InitiationMessageTemplate->find('list', $args));
  }

  /**
   * Find the SLIP Plugin configuration for the requested CO and redirect to it.
   *
   * @since  COmanage Registry v4.7.0
   */

  public function index() {
    // Do we already have a configuration for this CO?

    try {
      $recordId = $this->SlipPlugin->getConfigId($this->cur_co['Co']['id']);
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
      $this->redirect('/');
    }

    // Redirect to edit

    $this->redirect(array('action' => 'edit', $recordId));
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v4.7.0
   * @return Integer The CO ID if found, or -1 if not
   */

  public function parseCOID($data = null) {
    if($this->action == 'start') {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }

    return parent::parseCOID();
  }

  /**
   * Start an Identity Proofing request.
   * 
   * @since  COmanage Registry v4.7.0
   */

  public function start() {
    if(!empty($this->params['named']['op']) && $this->params['named']['op'] == 'go') {
      // Claimant has clicked the Start button, start a proofing request.
      // For this, we need the Plugin configuration, via the CO ID.

      $this->SlipPlugin->SlipProofing->start(
        $this->SlipPlugin->getConfigId($this->cur_co['Co']['id']),
        $this->Session->read('Auth.User.co_person_id'),
        $this->Session->read('Auth.User.co_person_id')
      );

      // Issue a redirect so the Claimant doesn't try to reload the page
      $this->redirect(array(
        'co' => $this->cur_co['Co']['id'],
        'op' => 'initiated'
      ));
    }
    
    $this->set('title_for_layout', _txt('ct.slip_plugins.1'));
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

    // Because this is a "Simple" Plugin, we only support one configuration per CO,
    // which is managed via the CO Configuration, which links to the index, which
    // works like CO Settings and redirects to the correct entity.
    
    // Delete an existing SLIP Plugin?
    $p['delete'] = false;
    
    // Edit an existing SLIP Plugin?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing SLIP Plugins?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Start a Proofing Request
    $p['start'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // View an existing SLIP Plugin?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
