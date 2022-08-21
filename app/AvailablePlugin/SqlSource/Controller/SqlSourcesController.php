<?php
/**
 * COmanage Registry SQL Source Controller
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

App::uses("SOISController", "Controller");

class SqlSourcesController extends SOISController {
  // Class name, used by Cake
  public $name = "SqlSources";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'source_table' => 'asc'
    )
  );
  
  public $uses = array(
    'SqlSource.SqlSource',
    'Address',
    'EmailAddress',
    'Identifier',
    'Name',
    'TelephoneNumber',
    'Url'
  );
  
  public $edit_contains = array(
    'OrgIdentitySource'
  );

  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  function beforeRender() {
    parent::beforeRender();
    
    // Pull the list of available types
    
    $this->set('vv_addresses_types', $this->Address->types($this->cur_co['Co']['id'], 'type'));
    $this->set('vv_email_addresses_types', $this->EmailAddress->types($this->cur_co['Co']['id'], 'type'));
    $this->set('vv_identifiers_types', $this->Identifier->types($this->cur_co['Co']['id'], 'type'));
    $this->set('vv_names_types', $this->Name->types($this->cur_co['Co']['id'], 'type'));
    $this->set('vv_telephone_numbers_types', $this->TelephoneNumber->types($this->cur_co['Co']['id'], 'type'));
    $this->set('vv_urls_types', $this->Url->types($this->cur_co['Co']['id'], 'type'));
  }
  
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
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    $coadmin = false;
    
    if($roles['coadmin'] && !$this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      // CO Admins can only manage org identity sources if org identities are NOT pooled
      $coadmin = true;
    }
    
    // Delete an existing Source?
    $p['delete'] = $roles['cmadmin'] || $coadmin;
    
    // Edit an existing Source?
    $p['edit'] = $roles['cmadmin'] || $coadmin;
    
    // View all existing Sources?
    $p['index'] = $roles['cmadmin'] || $coadmin;
    
    // View an existing Source?
    $p['view'] = $roles['cmadmin'] || $coadmin;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
