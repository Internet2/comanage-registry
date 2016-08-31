<?php
/**
 * COmanage Registry CO Identifier Validators Controller
 *
 * Copyright (C) 2016 SCG
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
 * @copyright     Copyright (C) 2016 SCG
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");
  
class CoIdentifierValidatorsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoIdentifierValidators";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoIdentifierValidator.description' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   *
   * @since  COmanage Registry v1.1.0
   */

  function beforeRender() {
    // Pull the set of validators
    $this->set('vv_plugins', $this->loadAvailablePlugins('identifiervalidator'));
    
    // Pull the set of Extended Types. We support both EmailAddress and Identifier
    $types = array();
    // Normally we wouldn't use a localized language string as an array key,
    // but in this case it's used to render the select popup
    $types[_txt('ct.email_addresses.1')] = $this->Co->CoExtendedType->active($this->cur_co['Co']['id'], 'EmailAddress.type', 'keyed');
    $types[_txt('ct.identifiers.1')] = $this->Co->CoExtendedType->active($this->cur_co['Co']['id'], 'Identifier.type', 'keyed');
    
    $this->set('vv_available_types', $types);
    
    parent::beforeRender();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v1.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Identifier Validator?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Identifier Validator?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Identifier Validator?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Identifier Validators?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Identifier Validator?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
