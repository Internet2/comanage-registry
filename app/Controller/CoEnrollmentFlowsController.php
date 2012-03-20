<?php
/**
 * COmanage Registry CO Enrollment Flows Controller
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");
  
class CoEnrollmentFlowsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoEnrollmentFlows";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoEnrollmentFlow.name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $cmr = $this->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Enrollment Flow?
    $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Delete an existing CO Enrollment Flow?
    $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Edit an existing CO Enrollment Flow?
    $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // View all existing CO Enrollment Flows?
    $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin']);
    
    // Select a CO Enrollment Flow to create a petition from?
    $p['select'] = ($cmr['cmadmin'] || $cmr['coadmin'] || !empty($cmr['couadmin']));
    
    // View an existing CO Enrollment Flow?
    $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.3
   */
  
  function performRedirect() {
    // On add, redirect to collect attributes
    
    if($this->action == 'add')
      $this->redirect(array('controller' => 'co_enrollment_attributes',
                            'action' => 'add',
                            'coef' => $this->CoEnrollmentFlow->id,
                            'co' => $this->cur_co['Co']['id']));
    else
      parent::performRedirect();
  }
  
  /**
   * Select an enrollment flow to create a petition from.
   * - postcondition: $co_enrollment_flows set
   *
   * @since  COmanage Registry v0.5
   */
  
  function select() {
    // Determine the Enrollment Flows for this CO and pass them to the view.
    // Currently, we don't check for COU-specific flows. 
    
    // Set page title
    $this->set('title_for_layout', _txt('ct.co_enrollment_flows.pl'));
    
    $this->paginate['conditions']['CoEnrollmentFlow.co_id'] = $this->cur_co['Co']['id'];
    $this->set('co_enrollment_flows', $this->paginate('CoEnrollmentFlow'));
  }
}
