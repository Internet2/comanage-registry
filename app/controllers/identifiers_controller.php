<?php
  /*
   * COmanage Gears Identifiers Controller
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

  include APP."controllers/mvpa_controller.php";

  class IdentifiersController extends MVPAController {
    // Class name, used by Cake
    var $name = "Identifiers";
    
    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    
    // Establish pagination parameters for HTML views
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'identifier' => 'asc'
      )
    );
    
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
      $pids = $this->parsePersonID($this->data);

      // If we're manipulating an Org Person, any CO admin or COU admin can edit,
      // but if we're manipulating a CO Person, only the CO admin or appropriate
      // COU admin (an admin of a COU in the current CO) can edit
      
      $admin = false;
      
      if(($pids['copersonid'] && ($cmr['coadmin'] || $cmr['couadmin']))
         || ($pids['orgidentityid'] && ($cmr['admin'] || $cmr['coadmin'] || $cmr['subadmin'])))
        $admin = true;
      
      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
      // Add a new Identifier?
      $p['add'] = ($cmr['cmadmin'] || $admin);
      
      // Delete an existing Identifier?
      $p['delete'] = ($cmr['cmadmin'] || $admin);
      
      // Edit an existing Identifier?
      $p['edit'] = ($cmr['cmadmin'] || $admin);
      
      // View all existing Identifier?
      $p['index'] = ($cmr['cmadmin'] || $admin);
      
      // View an existing Identifier?
      $p['view'] = ($cmr['cmadmin'] || $admin);

      $this->set('permissions', $p);
      return($p[$this->action]);
    }
  }
?>