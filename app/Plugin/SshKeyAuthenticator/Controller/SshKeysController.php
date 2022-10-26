<?php
/**
 * COmanage Registry SSH Keys Controller
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SAMController", "Controller");

class SshKeysController extends SAMController {
  // Class name, used by Cake
  public $name = "SshKeys";
  
  public $edit_contains = array();
  
  public $view_contains = array();
  
  /**
   * Add an SSH Key via an uploaded key file.
   * - precondition: SSH Key uploaded
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */

  public function add() {
    if($this->request->is('get')
       || $this->request->is('restful')) {
      parent::add();
      return;
    }

    // We need a CO Person ID
    $p = $this->parsePersonID();

    // No person ID exists
    if(empty($p['copersonid'])) {
      $this->Flash->set(_txt('er.cop.unk'), array('key' => 'error'));
      $this->redirect("/");
    }

    // File error
    if(empty($this->request->data['SshKey']['keyFile']['tmp_name'])
       || !empty($this->request->data['SshKey']['keyFile']['error'])) {
      $this->Flash->set(_txt('er.file.none'), array('key' => 'error'));
      $this->redirect($this->calculateRedirectOnFailure($p));
    }

    // Access the uploaded file as processed by PHP and presented by Cake
    try {
      $sk = $this->SshKey->addFromKeyFile($this->request->data['SshKey']['keyFile']['tmp_name'],
                                          $p['copersonid'],
                                          $this->request->data['SshKey']['ssh_key_authenticator_id']);
      $this->generateHistory('upload',
                             array('SshKey' => $sk),
                             null);

      $this->Flash->set(_txt('rs.added-a3', array(_txt('ct.ssh_keys.1'))), array('key' => 'success'));
      $this->redirect($this->calculateRedirectOnSuccess($p));
    } catch(InvalidArgumentException $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
      $this->redirect($this->calculateRedirectOnFailure($p));
    }
  }

  /**
   * Calculate where to redirect on Failure
   *
   * @since  COmanage Registry v4.1.0
   * @param  Array    $person CO Person ID
   * @return Array            Redirect route
   */

  protected function calculateRedirectOnFailure($person) {
    // Redirect on Failure
    if(empty($this->request->params['named']['onFinish'])) {
      // CO Person Canvas/Authenticator
      return array(
        'plugin'          => 'ssh_key_authenticator',
        'controller'      => 'ssh_keys',
        'action'          => 'index',
        'authenticatorid' => $this->request->data['SshKey']['authenticator_id'],
        'copersonid'      => $person['copersonid']);
    }
    // Enrollment Flow
    return array(
      'plugin'          => 'ssh_key_authenticator',
      'controller'      => 'ssh_keys',
      'action'          => 'add',
      'authenticatorid' => $this->request->data['SshKey']['authenticator_id'],
      'copetitionid'    => $this->request->params["named"]["copetitionid"],
      'token'           => $this->request->params["named"]["token"],
      'onFinish'        => $this->request->params["named"]["onFinish"]
    );
  }

  /**
   * Calculate where to redirect on Success
   *
   * @since  COmanage Registry v4.1.0
   * @param  Array    $person CO Person ID
   * @return Array            Redirect route
   */

  protected function calculateRedirectOnSuccess($person) {
    // Redirect on success
    if(empty($this->request->params['named']['onFinish'])) {
      // CO Person Canvas/Authenticator
      return array(
        'plugin'          => 'ssh_key_authenticator',
        'controller'      => 'ssh_keys',
        'action'          => 'index',
        'authenticatorid' => $this->request->data['SshKey']['authenticator_id'],
        'copersonid'      => $person['copersonid']);
    }
    // Enrollment Flow
    return urldecode($this->request->params['named']['onFinish']);
  }

  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v3.3.0
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */

  public function generateHistory($action, $newdata, $olddata) {
    // Build a change string
    $cstr = "";
    $cop = null;
    $act = null;

    $actorCoPersonId = $this->request->is('restful') ? null : $this->Session->read('Auth.User.co_person_id');
    $actorApiUserId = $this->request->is('restful') ? $this->Auth->User('id') : null;

    switch($action) {
      case 'add':
        $cstr = _txt('rs.added-a2', array(_txt('ct.ssh_keys.1'), $newdata['SshKey']['comment']));
        $cop = $newdata['SshKey']['co_person_id'];
        $act = SshKeyActionEnum::SshKeyAdded;
        break;
      case 'delete':
        $cstr = _txt('rs.deleted-a2', array(_txt('ct.ssh_keys.1'), $olddata['SshKey']['comment']));
        $cop = $olddata['SshKey']['co_person_id'];
        $act = SshKeyActionEnum::SshKeyDeleted;
        break;
      case 'edit':
        $cstr = _txt('rs.edited-a2', array(_txt('ct.ssh_keys.1'), $newdata['SshKey']['comment']));
        $cop = $newdata['SshKey']['co_person_id'];
        $act = SshKeyActionEnum::SshKeyEdited;
        break;
      case 'upload':
        $cstr = _txt('rs.uploaded-a2', array(_txt('ct.ssh_keys.1'), $newdata['SshKey']['comment']));
        $cop = $newdata['SshKey']['co_person_id'];
        $act = SshKeyActionEnum::SshKeyUploaded;
        break;
    }

    $this->SshKey->CoPerson->HistoryRecord->record($cop,
                                                   null,
                                                   null,
                                                   $actorCoPersonId,
                                                   $act,
                                                   $cstr,
                                                   null, null, null,
                                                   $actorApiUserId);

    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Merge in the permissions calculated by our parent
    $p = array_merge($p, $this->calculateParentPermissions($this->SshKey->SshKeyAuthenticator->multiple));
    
    $p['addKeyFile'] = $p['add'];
    
    if(!$this->request->is('restful')) {
      // We don't allow editing of SSH Keys via the UI, though the REST API
      // does permit edits
      $p['edit'] = false;
    }
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
