<?php
/**
 * COmanage Registry SLIP Attachments Controller
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

class SlipAttachmentsController extends StandardController {
  // Class name, used by Cake
  public $name = "SlipAttachments";
  
  public $uses = array('SlipPlugin.SlipAttachment',
                       'SlipPlugin.SlipPlugin');
  
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
    'SlipProofing' => array('ClaimantCoPerson' => 'PrimaryName')
  );

  /**
   * Add an Attachment via an uploadedkey file.
   *
   * @since  COmanage Registry v4.7.0
   */

  public function add() {
    if($this->request->is('post')) {
      try {
        $this->SlipAttachment->addFromUpload(
          $this->request->data['SlipAttachment']['slip_proofing_id'],
          $this->request->data['SlipAttachment']['description'],
          $this->request->data['SlipAttachment']['file_contents']
        );

        $this->Flash->set(_txt('rs.added-a3', array(_txt('ct.slip_attachments.1'))), array('key' => 'success'));

        $this->redirect(array(
          'plugin'      => 'slip_plugin',
          'controller'  => 'slip_attachments',
          'action'      => 'index',
          'sprid'       => $this->request->data['SlipAttachment']['slip_proofing_id']
        ));
      }
      catch(Exception $e) {
        $this->set('title_for_layout', _txt('op.add.new', array(_txt('ct.slip_attachments.1'))));
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    } else {
      parent::add();
    }
  }

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

      $this->set('vv_slip_proofing', $this->SlipAttachment->SlipProofing->find('first', $args));
    }
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

    if(in_array($this->action, array('add', 'index'))) {
      $sprid = $this->params->named['sprid'];
    }

    if($sprid) {
      // Map Slip Attachment to Slip Proofing to CO

      $coId = $this->SlipAttachment->SlipProofing->findCoForRecord($sprid);

      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.slip_proofings.1'), $coef)));
      }
    }

    return parent::calculateImpliedCoId();
  }

  /**
   * Delete an Attachment.
   *
   * @since  COmanage Registry v4.7.0
   */

  public function delete($id) {
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;

    $args = array();
    $args['conditions'][$req.'.id'] = $id;
    $args['contain'] = false;

    // Cache the SLIP Proofing ID so we have a location to redirect to
    $curdata = $model->find('first', $args);

    if(!empty($curdata)) {
      try {
        $ret = $model->delete($id);

        $this->Flash->set(_txt('rs.deleted-a3', array($id)), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }

    }

    $this->redirect(array(
      'plugin'      => 'slip_plugin',
      'controller'  => 'slip_attachments',
      'action'      => 'index',
      'sprid'       => $curdata['SlipAttachment']['slip_proofing_id']
    ));
  }

  /**
   * Deliver a SLIP Attachment.
   * 
   * @since  COmanage Registry v4.7.0
   * @param  int   SLIP Attachment ID
   */

  public function deliver($id) {
    // In PE's Mostly Static Resources we provide caching hints, but we don't bother with that
    // here since these are intended to be delivered once or maybe twice and then deleted.

    $args = array();
    $args['conditions']['SlipAttachment.id'] = $id;
    $args['contain'] = false;

    $attachment = $this->SlipAttachment->find('first', $args);

    if(empty($attachment['SlipAttachment']['file_contents'])) {
      $this->Flash->set(_txt('er.notfound', array(_txt('ct.slip_attachments.1'), $id)), array('key' => 'error'));
      // We don't have a valid record so we don't know where to go
      $this->redirect('/');
    }

    $this->autoRender = false;
    $this->response->type($attachment['SlipAttachment']['mime_type']);
    $this->response->cache('-1 minute', '+7 days');
    $this->response->body($attachment['SlipAttachment']['file_contents']);

    return $this->response;
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.7.0
   * @return Array An array suitable for use in $this->paginate
   */

  function paginationConditions() {
    if(!empty($this->params['named']['sprid'])) {
      return array(
        'conditions' => [
          'SlipAttachment.slip_proofing_id' => $this->params['named']['sprid']
        ]
      );
    }

    return array();
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

    // Add an Attachment?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Delete an Attachment?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Deliver the Attachment?
    $p['deliver'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Edit an existing Attachment?
    $p['edit'] = false;
    
    // View all existing Attachments?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);
    
    // View an existing Attachment?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
