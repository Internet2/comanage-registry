<?php
/**
 * COmanage Registry Navigation Links Controller
 *
 * Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");
  
class NavigationLinksController extends StandardController {
  // Class name, used by Cake
  public $name = "NavigationLinks";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'NavigationLink.type_name' => 'asc'
    )
  );

  /**
   * Get location options for view.
   * - postcondition: vv_link_location_options set
   *
   * @since  COmanage Registry v0.8.2
   */
  
  function beforeRender() {
    //globals
    global $cm_lang, $cm_texts;

    // Pass the location options
    $link_location_options = array(LinkLocationEnum::topBar => $cm_texts[ $cm_lang ]['en.nav.location'][LinkLocationEnum::topBar]);
    $this->set('vv_link_location_options', $link_location_options);

    parent::beforeRender();
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.8.2
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new  link?
    $p['add'] = ($roles['cmadmin'] );
    
    // Delete an existing  Link?
    $p['delete'] = ($roles['cmadmin'] );
    
    // Edit an existing  Link?
    $p['edit'] = ($roles['cmadmin'] );
    
    // View all existing  Links?
    $p['index'] = ($roles['cmadmin'] );
    
    // View an existing  Link?
    $p['view'] = ($roles['cmadmin'] );

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
