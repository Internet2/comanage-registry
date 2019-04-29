<?php
/**
 * COmanage Registry CO Jobs Controller
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

class CoJobsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoJobs";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 50,
    'order' => array(
      'CoJob.queue_time' => 'desc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $view_contains = array(
  );
  
  /**
   * Perform filtering of COU parent options for dropdown.
   * - postcondition: parent_options set
   *
   * @since  COmanage Registry v0.3
   */

  function beforeRender() {
    parent::beforeRender();
    
    if($this->action == 'view') {
      if(in_array($this->viewVars['co_jobs'][0]['CoJob']['status'], array(JobStatusEnum::InProgress, JobStatusEnum::Queued))) {
        // Request the page auto-refresh
        
        $this->set('vv_refresh_interval', 15);
      }
    }
  }
  
  /**
   * Cancel the specified Job. Note that while the Job status is updated to canceled, it is
   * up to the running process to detect the status change and actually stop.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $id CO Job ID
   */
  
  public function cancel($id) {
    try {
      $this->CoJob->cancel($id, $this->Session->read('Auth.User.username'));
      $this->Flash->set(_txt('rs.jb.cxld'), array('key' => 'success'));    
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->redirect(array(
      'controller' => 'co_jobs',
      'action'     => 'view',
      $id)
    );
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
    
    // Cancel a CO Job?
    $p['cancel'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all CO Jobs?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View this CO Job?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
