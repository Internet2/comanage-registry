<?php
  /*
   * COmanage Gears Addresses Controller
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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

  class AddressesController extends MVPAController {
    // Class name, used by Cake
    var $name = "Addresses";
    
    // Cake Components used by this Controller
    var $components = array('RequestHandler',  // For REST
                            'Security',
                            'Session');
    
    // Establish pagination parameters for HTML views
    var $paginate = array(
      'limit' => 25,
      'order' => array(
        'line1' => 'asc'
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

      // Is this our own record? ($cmr is what was authenticated, $pids what was requested)
      // We needs to see if the requested person role ID belongs to the authenticated person.
      $self = false;
      
      $copid = $this->Address->CoPersonRole->field('co_person_id', array('id' => $pids['copersonroleid']));
        
      if($copid && $copid == $cmr['copersonid'])
        $self = true;

      // If we're manipulating an Org Person, any CO admin or COU admin can edit,
      // but if we're manipulating a CO Person, only the CO admin or appropriate
      // COU admin (an admin of the COU associated with the current record) can edit
      
      $admin = false;
      
      if(($pids['copersonid'] && $cmr['coadmin'])
         || ($pids['orgidentityid'] && ($cmr['admin'] || $cmr['coadmin'] || $cmr['subadmin'])))
        $admin = true;
        
      if(!$admin && $cmr['couadmin'])
      {
        // Current person is a COU admin, see if it's for this person role. There should
        // be only one match, so we use 'first'.
        
        $dbo = $this->Address->getDataSource();
        
        $cou = $this->Address->CoPersonRole->Cou->find("first",
                                                       array("joins" =>
                                                             array(array('table' => $dbo->fullTableName($this->Address->CoPersonRole),
                                                                         'alias' => 'CoPersonRole',
                                                                         'type' => 'INNER',
                                                                         'conditions' => array('Cou.id=CoPersonRole.cou_id'))),
                                                             "conditions" =>
                                                             array('CoPersonRole.id' => $pids['copersonroleid'])));
        
        if(isset($cou['Cou']['name']))
        {
          foreach($cmr['couadmin'] as $c)
          {
            if($c == $cou['Cou']['name'])
            {
              $admin = true;
              break;
            }
          }
        }
      }
      
      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();
      
      // Determine what operations this user can perform
      
      // Add a new Address?
      $p['add'] = ($cmr['cmadmin'] || $admin || $self);
      
      // Delete an existing Address?
      $p['delete'] = ($cmr['cmadmin'] || $admin || $self);
      
      // Edit an existing Address?
      $p['edit'] = ($cmr['cmadmin'] || $admin || $self);
      
      // View all existing Addresses?
      $p['index'] = ($cmr['cmadmin'] || $admin);
      
      // View an existing Address?
      $p['view'] = ($cmr['cmadmin'] || $admin || $self);

      $this->set('permissions', $p);
      return($p[$this->action]);
    }
  }
?>