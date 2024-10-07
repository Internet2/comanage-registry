<?php
/**
 * COmanage Registry Contacts Controller
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("MVPAController", "Controller");

class ContactsController extends MVPAController {
  // Class name, used by Cake
  public $name = "Contacts";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'given' => 'asc'
    )
  );

  public $edit_contains = array(
    'CoDepartment',
    'Organization'
  );

  public $view_contains = array(
    'CoDepartment',
    'Organization'
  );
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.4.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    $pids = $this->parsePersonID($this->request->data);
    
    // For add action, we accept the identifier passed in the URL, otherwise we
    // lookup based on the record ID.
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new Contact?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Contact?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Contact?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Contact?
    // Currently only supported via REST since there's no use case for viewing all
// XXX enable for REST API?
    $p['index'] = false; //$this->request->is('restful') && ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Contact?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
