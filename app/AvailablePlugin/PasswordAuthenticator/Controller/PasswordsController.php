<?php
/**
 * COmanage Registry Password Controller
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

App::uses("SAMController", "Controller");

class PasswordsController extends SAMController {
  // Class name, used by Cake
  public $name = "Passwords";
  
  public function generate() {
    $this->Password->PasswordAuthenticator->setConfig($this->viewVars['vv_authenticator']);
    
    try {
      if($this->viewVars['vv_authenticator']['PasswordAuthenticator']['password_source'] != PasswordAuthPasswordSourceEnum::AutoGenerate) {
        throw new InvalidArgumentException(_txt('er.passwordauthenticator.source', PasswordAuthPasswordSourceEnum::AutoGenerate));
      }
      
      $this->set('vv_token', $this->Password->generateToken($this->viewVars['vv_authenticator']['PasswordAuthenticator']['id'],
                                                            $this->viewVars['vv_co_person']['CoPerson']['id'],
                                                            $this->viewVars['vv_authenticator']['PasswordAuthenticator']['max_length'],
                                                            $this->Session->read('Auth.User.co_person_id')));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
      
      $target = array();
      $target['plugin'] = null;
      $target['controller'] = "authenticators";
      $target['action'] = 'status';
      $target['copersonid'] = $this->viewVars['vv_co_person']['CoPerson']['id'];
      
      $this->redirect($target);
    }
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
    
    // Merge in the permissions calculated by our parent
    $p = array_merge($p, $this->calculateParentPermissions($this->Password->PasswordAuthenticator->multiple));
    
    $p['generate'] = isset($p['manage']) ? $p['manage'] : false;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
