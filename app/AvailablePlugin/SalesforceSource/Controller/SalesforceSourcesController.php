<?php
/**
 * COmanage Registry Salesforce Source Controller
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SOISController", "Controller");

class SalesforceSourcesController extends SOISController {
  // Class name, used by Cake
  public $name = "SalesforceSources";

  public $uses = array("SalesforceSource.SalesforceSource",
                       "OrgIdentitySource");

  /**
   * Salesforce OAuth callback.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $id SalesforceSource ID
   */
  
  public function callback($id) {
    // We have to look in $_GET because what we get back isn't a Cake style named parameter
    // (ie: code=foo, not code:foo)
    
    try {
      if(empty($_GET['code']) || empty($_GET['state'])) {
        throw new RuntimeException(_txt('er.salesforcesource.callback'));
      }
      
      $oisid = $this->SalesforceSource->field('org_identity_source_id',
                                              array('SalesforceSource.id' => $id));
      
      if(!$oisid) {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.salesforce_sources.1', $id))));
      }
      
      $Backend = $this->OrgIdentitySource->instantiateBackendModel($oisid);
      
      $response = $Backend->exchangeCode($_GET['code'], base64_decode($_GET['state']));
      
      // Store the tokens
      
      $this->SalesforceSource->id = $id;
      $this->SalesforceSource->saveField('access_token', $response->access_token);
      $this->SalesforceSource->saveField('refresh_token', $response->refresh_token);
      // This will be (eg) cs67.salesforce.com
      if(!empty($response->instance_url)) {
        $this->SalesforceSource->saveField('instance_url', $response->instance_url);
      }
      // While we're here, clear the groupable attributes cache
      $this->SalesforceSource->saveField('groupable_attrs', null);
      
      $this->Flash->set(_txt('pl.salesforcesource.token.ok'), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->redirect(array(
      'plugin'     => 'salesforce_source',
      'controller' => 'salesforce_sources',
      'action'     => 'edit',
      $id
    ));
  }
  
  /**
   * Perform any followups following a write operation.  Note that if this
   * method fails, it must return a warning or REST response, but that the
   * overall transaction is still considered a success (add/edit is not
   * rolled back).
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.1
   * @param  Array Request data
   * @param  Array Current data
   * @param  Array Original request data (unmodified by callbacks)
   * @return boolean true if dependency checks succeed, false otherwise.
   */

  function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
    if(!empty($reqdata['SalesforceSource']['serverurl'])
       && (empty($reqdata['SalesforceSource']['refresh_token'])
           || empty($reqdata['SalesforceSource']['access_token']))) {
      // Warn that no oauth token is present
      
      $this->Flash->set(_txt('pl.salesforcesource.token.missing'), array('key' => 'information'));      
    }
    
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Accept an OAuth callback?
    $p['callback'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing SalesforceSource?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing SalesforceSource?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing SalesforceSources?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View API limits?
    $p['limits'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing SalesforceSource?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * View SalesforceSource limits.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $id SalesforceSource ID
   */
  
  public function limits($id) {
    // We need the parent ID to instantiate the backend
    
    try {
      $oisid = $this->SalesforceSource->field('org_identity_source_id', array('SalesforceSource.id' => $id));
    
      if(!$oisid) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.salesforce_sources.1'), $id)));
      }
      
      // Obtain limit information
      $Backend = $this->OrgIdentitySource->instantiateBackendModel($oisid);
      
      $this->set('vv_limits', $Backend->limits());
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
  }
}
