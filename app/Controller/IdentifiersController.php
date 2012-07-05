<?php
/**
 * COmanage Registry Identifiers Controller
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

class IdentifiersController extends MVPAController {
  // Class name, used by Cake
  public $name = "Identifiers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'identifier' => 'asc'
    )
  );
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Redirect may be issued
   *
   * @since  COmanage Registry v0.6
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Identifier supports Extended Types. Figure out what types are defined
    // in order for the views to render properly.
    
    $this->set('identifier_types', $this->Identifier->types($this->cur_co['Co']['id']));
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.3
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    // We currently don't perform any sanity checks (ie: dupe checks) on identifiers
    // since it's not clear when we should reject an identifier for already existing.
    // For example, if foo@univ.edu is a uid for an org identity, why can't it be a
    // uid for that person's CO identity as well?
    
    // The minimal case that could be checked is the same identifier for the same
    // org identity or co person.
    
    return true;
  }
   */

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
