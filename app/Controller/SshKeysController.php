<?php
/**
 * COmanage Registry SSH Key Controller
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
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class SshKeysController extends StandardController {
  // Class name, used by Cake
  public $name = "SshKeys";
  
  // We require CO Person and don't (currently) allow Org Identity, but we
  // flag requires_person to simplify redirecting to /co_person. (We half behave
  // like an MVPAController.)
  public $requires_person = true;
  
  // We'll also require CO, though if we ever allow SSH keys to attach to the Org
  // Identity we'll want to operate like (or just extend) MVPAController.
  public $requires_co = true;
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'SshKey.comment' => 'asc'
    )
  );
  
  /**
   * Add an SSH Key via an uploaded key file.
   * - precondition: SSH Key uploaded
   *
   * @since  COmanage Registry v0.9
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */
  
  public function addKeyFile() {
    // We need a CO Person ID
    
    $p = $this->parsePersonID();
    
    if(!empty($p['copersonid'])) {
      // Access the uploaded file as processed by PHP and presented by Cake
      if(!empty($this->request->data['SshKey']['keyFile']['tmp_name'])
         && (!isset($this->request->data['SshKey']['keyFile']['error'])
             || !$this->request->data['SshKey']['keyFile']['error'])) {
        try {
          $sk = $this->SshKey->addFromKeyFile($this->request->data['SshKey']['keyFile']['tmp_name'],
                                              $p['copersonid']);
          $this->generateHistory('upload',
                                 array('SshKey' => $sk),
                                 null);
          
          $this->Flash->set(_txt('rs.added-a3', array(_txt('ct.ssh_keys.1'))), array('key' => 'success'));
          $this->performRedirect();
        }
        catch(InvalidArgumentException $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
        }
      } else {
        $this->Flash->set(_txt('er.file.none'), array('key' => 'error'));
      }
    } else {
      $this->Flash->set(_txt('er.cop.unk'), array('key' => 'error'));
    }
    
    // Redirect to add so we can try again
    $this->redirect(array(
                      'action'     => 'add',
                      'copersonid' => $p['copersonid']
                    ));
  }
  
  /**
   * Callback to set relevant tab to open when redirecting to another page
   *
   * @since  COmanage Registry v0.9
   */

  function beforeFilter() {
    $this->redirectTab = 'sshkey';
    
    parent::beforeFilter();
  }

  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v0.9
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
    
    switch($action) {
      case 'add':
        $cstr = _txt('rs.added-a2', array(_txt('ct.ssh_keys.1'), $newdata['SshKey']['comment']));
        $cop = $newdata['SshKey']['co_person_id'];
        $act = ActionEnum::SshKeyAdded;
        break;
      case 'delete':
        $cstr = _txt('rs.deleted-a2', array(_txt('ct.ssh_keys.1'), $olddata['SshKey']['comment']));
        $cop = $olddata['SshKey']['co_person_id'];
        $act = ActionEnum::SshKeyDeleted;
        break;
      case 'edit':
        $cstr = _txt('rs.edited-a2', array(_txt('ct.ssh_keys.1'), $newdata['SshKey']['comment']));
        $cop = $newdata['SshKey']['co_person_id'];
        $act = ActionEnum::SshKeyEdited;
        break;
      case 'upload':
        $cstr = _txt('rs.uploaded-a2', array(_txt('ct.ssh_keys.1'), $newdata['SshKey']['comment']));
        $cop = $newdata['SshKey']['co_person_id'];
        $act = ActionEnum::SshKeyUploaded;
        break;
    }
    
    $this->SshKey->CoPerson->HistoryRecord->record($cop,
                                                   null,
                                                   null,
                                                   $this->Session->read('Auth.User.co_person_id'),
                                                   $act,
                                                   $cstr);
    
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.9
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    $pids = $this->parsePersonID($this->request->data);
    
    // In order to manipulate an SSH Key, the authenticated user must have permission
    // over the associated CO Person. For add action, we accept the identifier passed
    // in the URL, otherwise we lookup based on the record ID.
    
    $managed = false;
    $self = false;
    
    if(!empty($roles['copersonid'])) {
      switch($this->action) {
      case 'add':
        if(!empty($pids['copersonid'])) {
          $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                            $pids['copersonid']);
        }
        break;
      case 'delete':
      case 'edit':
      case 'view':
        if(!empty($this->request->params['pass'][0])) {
          // look up $this->request->params['pass'][0] and find the appropriate co person id or org identity id
          // then pass that to $this->Role->isXXX
          $args = array();
          $args['conditions']['SshKey.id'] = $this->request->params['pass'][0];
          $args['contain'] = false;
          
          $sshkey = $this->SshKey->find('first', $args);
          
          if(!empty($sshkey['SshKey']['co_person_id'])) {
            $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                              $sshkey['SshKey']['co_person_id']);
          }
        }
        break;
      }
      
      if(!empty($pids['copersonid'])
         && $roles['copersonid'] == $pids['copersonid']) {
        $self = true;
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new SSH Key (via form or upload?
    $p['add'] = ($roles['cmadmin'] || $managed || $self);
    $p['addKeyFile'] = $p['add'];
    
    // Delete an existing SSH Key?
    $p['delete'] = ($roles['cmadmin'] || $managed || $self);
    
    // Edit an existing SSH Key?
    // As of v3.2.0 (CO-1616), editing a key is no longer permitted
    $p['edit'] = false;
    
    // View all SSH Keys?
    $p['index'] = $roles['cmadmin'];
    
    // View an existing SSH Key?
    $p['view'] = ($roles['cmadmin'] || $managed || $self);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
