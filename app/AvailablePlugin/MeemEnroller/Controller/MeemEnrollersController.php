<?php
/**
 * COmanage Registry Meem Enrollers Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SEWController", "Controller");

class MeemEnrollersController extends SEWController {
  // Class name, used by Cake
  public $name = "MeemEnrollers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'co_enrollment_flow_wedge_id' => 'asc'
    )
  );
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */

  function beforeRender() {
    parent::beforeRender();
    
    // Pull the list of available enrollment flows. We could filter this down to
    // just those which have Authenticators attached, but some deployer might
    // want to do something custom with a plugin that isn't an authenticator
    // specifically, so we don't.

    $args = array();
    $args['conditions']['MfaCoEnrollmentFlow.co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['MfaCoEnrollmentFlow.status'] = TemplateableStatusEnum::Active;
    $args['order'] = 'MfaCoEnrollmentFlow.name ASC';
    $args['contain'] = false;

    $this->set('vv_available_flows', $this->MeemEnroller->MfaCoEnrollmentFlow->find('list', $args));
    
    // Pull the list of available groups

    $args = array();
    $args['conditions']['MfaExemptCoGroup.co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['MfaExemptCoGroup.status'] = SuspendableStatusEnum::Active;
    $args['order'] = 'MfaExemptCoGroup.name ASC';
    $args['contain'] = false;

    $this->set('vv_available_groups', $this->MeemEnroller->MfaExemptCoGroup->find('list', $args));
    
    // Pull the list of available API Users
    $this->set('vv_api_users', $this->Co->ApiUser->availableApiUsers($this->cur_co['Co']['id']));
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Delete an existing Meem Enroller?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Meem Enroller?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Meem Enroller?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Meem Enroller?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
