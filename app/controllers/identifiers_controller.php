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
    
    function checkWriteDependencies($curdata = null)
    {
      // Perform any dependency checks required prior to a write (add/edit) operation.
      // This method is intended to be overridden by model-specific controllers.
      //
      // Parameters:
      // - For edit operations, $curdata will hold current data
      //
      // Preconditions:
      // (1) $this->data holds request data
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.
      
      // Get a pointer to our model
      $req = $this->modelClass;
      $model = $this->$req;
      
      // Check that identifier isn't already in use
      
      if(!isset($curdata)
         || ($curdata[$req]['identifier'] != $this->data[$req]['identifier']))
      {
        $x = $model->findByIdentifier($this->data[$req]['identifier']);
      
        if(!empty($x))
        {
          if($this->restful)
            $this->restResultHeader(403, "Identifier In Use");
          else
            $this->Session->setFlash("The identifier '" . $this->data[$req]['identifier'] . "' already exists (ID: " . $x[$req]['id'] . ")", '', array(), 'error');          

          return(false);
        }
      }
        
      return(true);
    }
    
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