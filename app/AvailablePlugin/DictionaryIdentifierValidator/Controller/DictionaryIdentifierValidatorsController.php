<?php
/**
 * COmanage Registry Dictionary Identifier Validator Controller
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SIVController", "Controller");

class DictionaryIdentifierValidatorsController extends SIVController {
  // Class name, used by Cake
  public $name = "DictionaryIdentifierValidators";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'mode' => 'asc'
    )
  );
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  function beforeRender() {
    parent::beforeRender();
    
    $args = array();
    $args['conditions']['Dictionary.co_id'] = $this->cur_co['Co']['id'];
    // We only support Standard Dictionaries because it's not clear what it would
    // mean to validate against on Organization or Department. We also directly
    // use DictionaryEntry in DictionaryIdentifierValidator.php, though that
    // presumably could be rewritten.
    $args['conditions']['Dictionary.mode'] = DictionaryModeEnum::Standard;
    $args['fields'] = array('id', 'description');
    
    $this->set('vv_available_dictionaries', $this->DictionaryIdentifierValidator->CoIdentifierValidator->Co->Dictionary->find('list', $args));
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Edit an existing Dictionary Identifier Validator?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all existing Dictionary Identifier Validator?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View an existing Dictionary Identifier Validator?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
