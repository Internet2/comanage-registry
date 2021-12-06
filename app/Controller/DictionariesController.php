<?php
/**
 * COmanage Registry Dictionaries Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class DictionariesController extends StandardController {
  public $requires_co = true;

  // Class name, used by Cake
  public $name = "Dictionaries";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'description' => 'asc'
    )
  );

  public $delete_contains = array('AttributeEnumeration');

  public $edit_contains = array();

  public $view_contains = array();

  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v4.0.1
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */

  function checkDeleteDependencies($curdata) {
    // We can't delete a dictionary if it is linked to an attribute enumerator

    if(!empty($curdata["AttributeEnumeration"])) {
      $count = count($curdata["AttributeEnumeration"]);
      // Return the error message
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, _txt('er.dict.with.attr', array($count)));
      } else {
        $this->Flash->set(_txt('er.dict.with.attr', array($count)), array('key' => 'error'));
      }
      return false;
    }

    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Dictionary?
    $p['add'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Delete an existing Dictionary?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing Dictionary?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all existing Dictionary?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View an existing Dictionary?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
