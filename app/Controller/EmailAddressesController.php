<?php
/**
 * COmanage Registry Email Addresses Controller
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

class EmailAddressesController extends MVPAController {
  // Class name, used by Cake
  public $name = "EmailAddresses";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'mail' => 'asc'
    )
  );

  /**
   * Delete an Email Address Object
   * - precondition: <id> must exist
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: On success, all related data (any table with an <object>_id column) is deleted
   *
   * @since  COmanage Registry v0.7
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be deleted
   */  
  function delete($id) {
    $this->redirectTab = 'email';

    parent::delete($id);
  }

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
    $self = false;
    
    if($cmr['comember'] &&
       // As of now, a person can only edit their CO data, not their Org data
       (($pids['copersonid'] && $cmr['copersonid'] && ($pids['copersonid'] == $cmr['copersonid']))))
      $self = true;
    
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
    
    // Add a new Email Address?
    $p['add'] = ($cmr['cmadmin'] || $admin || $self);
    
    // Delete an existing Email Address?
    $p['delete'] = ($cmr['cmadmin'] || $admin || $self);
    
    // Edit an existing Email Address?
    $p['edit'] = ($cmr['cmadmin'] || $admin || $self);
    
    // View all existing Email Addresses?
    $p['index'] = ($cmr['cmadmin'] || $admin);
    
    // View an existing Email Address?
    $p['view'] = ($cmr['cmadmin'] || $admin || $self);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.7
   */
  
  function performRedirect() {
    $this->redirectTab = 'email';

    parent::performRedirect();
  }
}
