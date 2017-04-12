<?php
/**
 * COmanage Registry CO Message Templates Controller
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
  
class CoMessageTemplatesController extends StandardController {
  // Class name, used by Cake
  public $name = "CoMessageTemplates";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoMessageTemplate.description' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Duplicate an existing Message Template
   * - postcondition: Redirect issued
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id CO Message Template ID
   */
  
  public function duplicate($id) {
    try {
      $this->CoMessageTemplate->duplicate($id);
      $this->Flash->set(_txt('rs.copy-a1', array(_txt('ct.co_message_templates.1'))), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->performRedirect();
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
    
    // Add a new Message Template?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Message Template?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Duplicate an existing Message Template?
    $p['duplicate'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Message Template?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Message Templates?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Message Template?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}