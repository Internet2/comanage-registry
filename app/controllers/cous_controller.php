<?php
  /*
   * COmanage Gears COU Controller
   *
   * Version: $Revision: 59 $
   * Date: $Date: 2011-03-13 22:12:07 -0400 (Sun, 13 Mar 2011) $
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

  class CousController extends StandardController {
    // Class name, used by Cake
    var $name = "Cous";
    
    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    
    // Establish pagination parameters for HTML views
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'Cou.name' => 'asc'
      )
    );
    
    // This controller needs a CO to be set
    var $requires_co = true;
    
    function checkDeleteDependencies($curdata)
    {
      // Perform any dependency checks required prior to a delete operation.
      // This method is intended to be overridden by model-specific controllers.
      //
      // Parameters:
      // - curdata: Current data
      //
      // Preconditions:
      //     None
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.
      
      $couppl = $this->Cou->CoPersonRole->findAllByCouId($curdata['Cou']['id']);
      
      if(!empty($couppl))
      {
        // A COU can't be removed if anyone is still a member of it.
        
        if($this->restful)
          $this->restResultHeader(403, "CoPersonRole Exists");
        else
          $this->Session->setFlash(_txt('er.cou.copr', array($curdata['Cou']['name'])), '', array(), 'error');
        
        return(false);
      }
        
      return(true);
    }

    function checkWriteFollowups()
    {
      // Perform any followups following a write operation.  Note that if this
      // method fails, it must return a warning or REST response, but that the
      // overall transaction is still considered a success (add/edit is not
      // rolled back).
      // This method is intended to be overridden by model-specific controllers.
      // 
      // Parameters:
      //   None
      //
      // Preconditions:
      // (1) $this->data holds request data
      //
      // Postconditions:
      // (1) Session flash message updated (HTML) or HTTP status returned (REST) on error
      //
      // Returns:
      // - true if dependency checks succeed, false otherwise.
      
      // Create an admin Group for the new COU. As of now, we don't try to populate
      // it with the current user, since it may not be desirable for the current
      // user to be a member of the new CO.
      
      // Only do this via HTTP.
      
      if(!$this->restful && $this->action == 'add')
      {
        if(isset($this->Cou->id))
        {
          $a['CoGroup'] = array(
            'co_id' => $this->data['Cou']['co_id'],
            'name' => 'admin:' . $this->data['Cou']['name'],
            'description' => _txt('fd.group.desc.adm', array($this->data['Cou']['name'])),
            'open' => false,
            'status' => 'A'
          );
          
          if(!$this->Cou->Co->CoGroup->save($a))
          {
            $this->Session->setFlash(_txt('er.cou.gr.admin'), '', array(), 'info');
            return(false);
          }
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

      $cmr = $this->calculateCMRoles();             // What was authenticated
      
      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
      // Add a new COU?
      $p['add'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // Delete an existing COU?
      $p['delete'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // Edit an existing COU?
      $p['edit'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // View all existing COUs?
      $p['index'] = ($cmr['cmadmin'] || $cmr['coadmin']);
      
      // View an existing COU?
      $p['view'] = ($cmr['cmadmin'] || $cmr['coadmin']);

      $this->set('permissions', $p);
      return($p[$this->action]);
    }
  }
?>