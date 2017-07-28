<?php
/**
 * COmanage Registry CO Email Lists Controller
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoEmailListsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoEmailLists";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoEmailList.name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $delete_contains = array(
  );
  
  public $edit_contains = array(
  );
  
  public $view_contains = array(
  );
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   * - postcondition: $cous may be set.
   * - postcondition: $co_groups may be set.
   *
   * @since  COmanage Registry v3.1.0
   */

  function beforeRender() {
    if(!$this->request->is('restful')) {
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['CoGroup.status'] = SuspendableStatusEnum::Active;
      $args['order'] = array('CoGroup.name ASC');

      $this->set('vv_co_groups', $this->Co->CoGroup->find('list', $args));
    }
    
    parent::beforeRender();
  }

  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    if(!isset($curdata) || ($curdata['CoEmailList']['name'] != $reqdata['CoEmailList']['name'])) {
      // Make sure name doesn't exist within this CO.
      
      $x = $this->CoEmailList->find('count',
                                    array('conditions' =>
                                          array('CoEmailList.name' => $reqdata['CoEmailList']['name'],
                                                'CoEmailList.co_id' => $this->cur_co['Co']['id'])));
      
      if($x > 0) {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(403, "Name In Use");
        } else {
          $this->Flash->set(_txt('er.el.exists', array($reqdata['CoEmailList']['name'])), array('key' => 'error'));
        }
        
        return false;
      }
    }
    
    return true;
  }
  
  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry 3.1.0
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */
  
  public function generateHistory($action, $newdata, $olddata) {
    switch($action) {
      case 'add':
        $this->CoEmailList->HistoryRecord->record(null,
                                                  null,
                                                  null,
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  ActionEnum::CoEmailListAdded,
                                                  _txt('rs.added-a2', array(_txt('ct.co_email_lists.1'),
                                                                            $newdata['CoEmailList']['name'])),
                                                  null,
                                                  $this->CoEmailList->id);
        break;
      case 'delete':
        $this->CoEmailList->HistoryRecord->record(null,
                                                  null,
                                                  null,
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  ActionEnum::CoEmailListDeleted,
                                                  _txt('rs.deleted-a2', array($olddata['CoEmailList']['name'])),
                                                  null,
                                                  $this->CoEmailList->id);
        break;
      case 'edit':
        $this->CoEmailList->HistoryRecord->record(null,
                                                  null,
                                                  null,
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  ActionEnum::CoEmailListEdited,
                                                  _txt('en.action', null, ActionEnum::CoEmailListEdited) . ": " .
                                                  $this->CoEmailList->changesToString($newdata, $olddata, $this->cur_co['Co']['id']),
                                                  null,
                                                  $this->CoEmailList->id);
        break;
    }
    
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Email List?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Email List?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Email List?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View history? This correlates with HistoryRecordsController
    $p['history'] = ($roles['cmadmin']
                     || $roles['coadmin']);
    
    // View all existing Email Lists?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // (Re)provision an existing Email List?
    $p['provision'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Email List?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Obtain provisioning status for an Email List
   *
   * @param  integer CO Group ID
   * @since  COmanage Registry v3.1.0
   */
  
  function provision($id) {
    if(!$this->request->is('restful')) {
      // Pull some data for the view to be able to render
      $this->set('co_provisioning_status', $this->CoEmailList->provisioningStatus($id));
      
      $args = array();
      $args['conditions']['CoEmailList.id'] = $id;
      $args['contain'] = false;
      
      $elist = $this->CoEmailList->find('first', $args);
      
      $this->set('co_email_list', $elist);
      
      if(!empty($elist['CoEmailList']['name'])) {
        $this->set('title_for_layout', _txt('fd.prov.status.for', array($elist['CoEmailList']['name'])));
      }
    }
  }
}
