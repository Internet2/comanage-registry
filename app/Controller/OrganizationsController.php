<?php
/**
 * COmanage Registry Organization Model
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

App::uses("StandardController", "Controller");

class OrganizationsController extends StandardController {
  var $name = "Organizations";
  
  var $paginate = array(
    'limit' => 25,
    'order' => array(
      'name' => 'asc'
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
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Organization?
    $p['add'] = $cmr['cmadmin'];
    
    // Delete an existing Organization?
    $p['delete'] = $cmr['cmadmin'];
    
    // Edit an existing Organization?
    $p['edit'] = $cmr['cmadmin'];
    
    // View all existing Organizations?
    $p['index'] = $cmr['cmadmin'];
          
    // View an existing Organization?
    $p['view'] = $cmr['cmadmin'];

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
