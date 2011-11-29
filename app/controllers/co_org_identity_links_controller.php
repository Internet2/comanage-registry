<?php
  /*
   * COmanage Gears CO Org Identity Link Controller
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

  class CoOrgIdentityLinksController extends StandardController {
   // Class name, used by Cake
    var $name = "CoOrgIdentityLinks";
    
    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    
    // Establish pagination parameters for HTML views
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'id' => 'asc'
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
      
      // Check that the IDs (CO Person, Org Person) provided point to existing entities.
      
      if(empty($this->data['CoOrgIdentityLink']['co_person_id']))
      {
        $this->restResultHeader(403, "CoPerson Does Not Exist");
        return(false);
      }
      
      $this->CoOrgIdentityLink->CoPerson->contain();
      $coPerson = $this->CoOrgIdentityLink->CoPerson->findById($this->data['CoOrgIdentityLink']['co_person_id']);
      
      if(empty($coPerson))
      {
        $this->restResultHeader(403, "CoPerson Does Not Exist");
        return(false);
      }
      
      if(empty($this->data['CoOrgIdentityLink']['org_identity_id']))
      {
        $this->restResultHeader(403, "OrgIdentity Does Not Exist");
        return(false);
      }
      
      // Can't contain OrgIdentity completely since Name is used for display
      $this->CoOrgIdentityLink->OrgIdentity->contain('Name');
      $orgIdentity = $this->CoOrgIdentityLink->OrgIdentity->findById($this->data['CoOrgIdentityLink']['org_identity_id']);
      
      if(empty($orgIdentity))
      {
        $this->restResultHeader(403, "OrgIdentity Does Not Exist");
        return(false);
      }
      
      // Check that an org identity being added is not already a member of the CO.
      // (A person can't be added to the same CO twice... that's what Person Roles
      // are for.) Note the UI check is in co_people_controller.
      
      if($this->CoOrgIdentityLink->CoPerson->orgIdIsCoPerson($coPerson['CoPerson']['co_id'],
                                                             $orgIdentity['OrgIdentity']['id']))
      {
        $this->restResultHeader(403, "OrgIdentity Already Linked");
        return(false);
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
      
      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
      // Add a new Person Source?
      $p['add'] = $cmr['cmadmin'];
      
      // Delete an existing Person Source?
      $p['delete'] = $cmr['cmadmin'];
      
      // Edit an existing Person Source?
      $p['edit'] = $cmr['cmadmin'];
      
      // View all existing Person Sources?
      $p['index'] = $cmr['cmadmin'];
            
      // View an existing Person Source?
      $p['view'] = $cmr['cmadmin'];

      $this->set('permissions', $p);
      return($p[$this->action]);
    }
  }
?>