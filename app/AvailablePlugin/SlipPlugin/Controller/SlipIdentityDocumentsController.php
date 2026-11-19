<?php
/**
 * COmanage Registry SLIP Identity Documents Controller
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

class SlipIdentityDocumentsController extends StandardController {
  // Class name, used by Cake
  public $name = "SlipIdentityDocuments";
  
  public $uses = array('SlipPlugin.SlipIdentityDocument',
                       'SlipPlugin.SlipPlugin',
                       'AttributeEnumeration',
                       'IdentityDocument');
  
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
    'IdentityDocument' => array('CoPerson' => 'PrimaryName'),
    'SlipProofing'
  );

  /**
   * Add a Standard Object.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - postcondition: On success, new Object created
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: $<object>_id or $invalid_fields set (REST)
   *
   * @since  COmanage Registry v4.7.0
   */
  
  function add() {
    // Because we need to save an Identity Document as well as a SLIP Identity Document,
    // our behavior on post is a bit non-standard.

    if($this->request->is('post')) {
      try {
        $this->SlipIdentityDocument->SlipProofing->attachDocument(
          $this->request->data['SlipIdentityDocument']['slip_proofing_id'],
          $this->request->data['SlipIdentityDocument']
        );
        $this->Flash->set(_txt('pl.slipplugin.attached'), array('key' => 'success'));

        $this->redirect(array(
          'plugin' => 'slip_plugin',
          'controller' => 'slip_identity_documents',
          'action' => 'index',
          'sprid' => $this->request->data['SlipIdentityDocument']['slip_proofing_id']
        ));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }

      // debug($this->request->data);
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

    // Pull the list(s) of enumerations (based on type), if so configured
    // This is based on IdentityDocumentsController
    $enums = array();

    $reflectionClass = new ReflectionClass('IdentityDocumentEnum');

    foreach($reflectionClass->getConstants() as $label => $key) {
      $enums['SlipIdentityDocument.issuing_authority.'.$key] = $this->AttributeEnumeration->enumerations($this->cur_co['Co']['id'], "IdentityDocument.issuing_authority." . $key);
    }

    $this->set('vv_enums', $enums);

    // Pull the Proofing Request info for Breadcrumbs
    if(!empty($this->params['named']['sprid'])) {
      $args = array();
      $args['conditions']['SlipProofing.id'] = $this->params['named']['sprid'];
      $args['contain'] = array('ClaimantCoPerson' => 'PrimaryName');

      $this->set('vv_slip_proofing', $this->SlipIdentityDocument->SlipProofing->find('first', $args));
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
      // Map Slip Identity Document to Slip Proofing to CO

      $coId = $this->SlipIdentityDocument->SlipProofing->findCoForRecord($sprid);

      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.slip_proofings.1'), $coef)));
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
    if(!empty($this->params['named']['sprid'])) {
      return array(
        'conditions' => [
          'SlipIdentityDocument.slip_proofing_id' => $this->params['named']['sprid']
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

    // Add an Identity Document?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Delete an existing Identity Document?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);

    // Edit an existing Identity Document?
    $p['edit'] = false; //($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);
    
    // View all existing Identity Documents?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);
    
    // View an existing Identity Document?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || $isRegistrar);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
