<?php
/**
 * COmanage Registry Addresses Controller
 *
 * Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("MVPAController", "Controller");

class AddressesController extends MVPAController {
  // Class name, used by Cake
  public $name = "Addresses";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'line1' => 'asc'
    )
  );
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $cmr = $this->calculateCMRoles();
    $pids = $this->parsePersonID($this->request->data);

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
      
      $cou = $this->Address->CoPersonRole->Cou->find("first",
                                                     array("joins" =>
                                                           array(array('table' => 'co_person_roles',
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
