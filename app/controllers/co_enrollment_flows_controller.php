<?php
  /*
   * COmanage Registry CO Enrollment Flows Controller
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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
   */

  include APP."controllers/standard_controller.php";
  
  class CoEnrollmentFlowsController extends StandardController {
    // Class name, used by Cake
    var $name = "CoEnrollmentFlows";
    
    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    
    // Establish pagination parameters for HTML views
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'CoEnrollmentFlow.name' => 'asc'
      )
    );
    
    // This controller needs a CO to be set
    var $requires_co = true;
    
    function isAuthorized()
    {
      // Authorization for this Controller, called by Auth component
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) Session.Auth holds data used for authz decisions
      //
      // Postconditions:
      // (1) $permissions set with calculated permissions
      //
      // Returns:
      // - Array of permissions

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
      
      // View an existing CO Enrollment Flow?
      $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin']);

      $this->set('permissions', $p);
      return($p[$this->action]);
    }
    
    function performRedirect()
    {
      // Perform a redirect back to the controller's default view.
      //
      // Parameters:
      //   None
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Redirect generated
      //
      // Returns:
      //   Nothing
      
      // On add, redirect to collect attributes
      
      if($this->action == 'add')
        $this->redirect(array('controller' => 'co_enrollment_attributes',
                              'action' => 'add',
                              'coef' => $this->CoEnrollmentFlow->id,
                              'co' => $this->cur_co['Co']['id']));
      else
        parent::performRedirect();
    }
  }
?>