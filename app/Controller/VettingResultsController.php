<?php
/**
 * COmanage Registry Vetting Results Controller
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class VettingResultsController extends StandardController {
  // Class name, used by Cake
  public $name = "VettingResults";
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $edit_contains = array();
  
  public $view_contains = array(
    'VetterCoPerson' => array('PrimaryName'),
    'VettingStep',
    'VettingRequest' => array('CoPerson' => array('PrimaryName'))
  );
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Is this a record we can manage?
    $managed = false;
    
    if(!empty($roles['copersonid'])
       && !empty($this->request->params['named']['copersonid'])) {
      // XXX for now CO Admins only, but maybe also COU admins?
      $managed = $this->Role->isCoAdminForCoPerson($roles['copersonid'],
                                                   $this->request->params['named']['copersonid']);
    }

    // Is this person a vetter for any Step within this CO?
    $vetterGroups = $this->Role->vetterForGroups($roles['copersonid']);
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // View an existing Vetting Request?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin'] || !empty($vetterGroups));

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
