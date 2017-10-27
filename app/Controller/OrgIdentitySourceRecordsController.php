<?php
/**
 * COmanage Registry Organizational Identity Source Records Controller
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class OrgIdentitySourceRecordsController extends StandardController {
  // Class name, used by Cake
  public $name = "OrgIdentitySourceRecords";
  
  // When using additional models, we must also specify our own
  public $uses = array('OrgIdentitySourceRecord', 'CmpEnrollmentConfiguration');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'sorid' => 'asc'
    )
  );
  
  public $view_contains = array(
    'CoPetition',
    'OrgIdentity' => array('PrimaryName'),
    'OrgIdentitySource'
  );
  
  // This controller needs a CO to be set
  public $requires_co = false;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v2.0.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    // This controller may or may not require a CO, depending on how
    // the CMP Enrollment Configuration is set up. Check and adjust before
    // beforeFilter is called.
    
    $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
    
    if(!$pool) {
      // We actually just need this to make the menus render correctly.
      // No need to bind the Co model (unlike, say, OrgIdentitySourcesController).
      $this->requires_co = true;
    }
    
    // The views will also need this
    $this->set('pool_org_identities', $pool);
    
    parent::beforeFilter();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v2.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    $coadmin = false;
    
    if($roles['coadmin'] && !$this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      // CO Admins can only manage org identity sources if org identities are NOT pooled
      $coadmin = true;
    }
    
    // View an Org Identity Source Record? (Matches OrgIdentitiesController)
    if($this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      $p['view'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    } else {
      // View a Org Identity Source Record? (Matches OrgIdentitySourceRecordsController)
      $p['view'] = ($roles['cmadmin']
                    || ($roles['coadmin'] || $roles['couadmin']));
    }
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
