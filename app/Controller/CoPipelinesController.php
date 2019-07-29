<?php
/**
 * COmanage Registry CO Pipelines Controller
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoPipelinesController extends StandardController {
  // Class name, used by Cake
  public $name = "CoPipelines";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoPipeline.name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $view_contains = array(
    'CoEnrollmentFlow',
    'CoSetting',
    'OrgIdentitySource'
  );
  
  public $edit_contains = array(
    'CoEnrollmentFlow',
    'CoSetting',
    'OrgIdentitySource'
  );

  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   *
   * @since  COmanage Registry v2.0.0
   */
  
  function beforeRender() {
    // We need to pull the available email and identifier types for use with
    // the appropriate match strategies
    
    $emailTypes = $this->CoPipeline->Co->CoPerson->EmailAddress->types($this->cur_co['Co']['id'], 'type');
    $idTypes = $this->CoPipeline->Co->CoPerson->Identifier->types($this->cur_co['Co']['id'], 'type');
    
    $this->set('vv_types', array('EmailAddress' => $emailTypes,
                                 'Identifier'   => $idTypes));
    
    // Provide a list of available COUs
    $this->set('vv_cous', $this->CoPipeline->Co->Cou->allCous($this->cur_co['Co']['id'], 'hash'));
    
    // Provide a list of valid affiliations
    $this->set('vv_copr_affiliation_types', $this->CoPipeline->Co->CoPerson->CoPersonRole->types($this->cur_co['Co']['id'], 'affiliation'));
    
    // Provide a list of valid statuses on delete
    $statuses = array();
    
    foreach($this->CoPipeline->validate['sync_status_on_delete']['rule'][1] as $s) {
      $statuses[$s] = _txt('en.status', null, $s);
    }
    
    $this->set('vv_delete_statuses', $statuses);
    
    // Provide a list of available Match Servers
    $args = array();
    $args['conditions']['Server.co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['Server.server_type'] = ServerEnum::MatchServer;
    $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
    $args['fields'] = array('id', 'description');
    $args['contain'] = false;
    
    $this->set('vv_match_servers', $this->CoPipeline->Co->Server->find('list', $args));
    
    parent::beforeRender();
  }
  
  /**
   * Manually execute a pipeline for a specified Org Identity.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  public function execute($id) {
    if(empty($this->request->query['org_identity_id'])
       || empty($this->request->query['action'])) {
      $this->Flash->set(_txt('er.notprov'), array('key' => 'error'));
    } else {
      try {
        // Wrap the call in a transaction
        $dbc = $this->CoPipeline->getDataSource();
        $dbc->begin($this);
        
        $this->CoPipeline->execute($id,
                                   $this->request->query['org_identity_id'],
                                   $this->request->query['action'],
                                   $this->Session->read('Auth.User.co_person_id'));
        
        $dbc->commit($this);
        $this->Flash->set(_txt('rs.pi.ok'), array('key' => 'success'));
      }
      catch(Exception $e) {
        $dbc->rollback($this);
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    }
    
    // Redirect back to org identities controller
    
    if(!empty($this->request->query['org_identity_id'])) {
      $this->redirect(array(
        'controller' => 'org_identities',
        'action'     => 'view',
        $this->request->query['org_identity_id']
      ));
    } else {
      $this->redirect("/");
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v2.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Pipeline?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Pipeline?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Pipeline?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Manually execute an existing CO Pipeline?
    // This corresponds to OrgIdentitiesController/pipeline
    $p['execute'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Pipeline?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Pipeline?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
