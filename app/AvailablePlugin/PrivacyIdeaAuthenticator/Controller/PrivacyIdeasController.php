<?php
/**
 * COmanage Registry Privacy IDEA Controller
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

App::uses("SAMController", "Controller");

class PrivacyIdeasController extends SAMController {
  // Class name, used by Cake
  public $name = "PrivacyIdeas";
  
  protected $pi_token_models = array(
    PrivacyIDEATokenTypeEnum::TOTP => 'TotpToken'
  );
  
  /**
   * Add a Standard Object.
   *
   * @since  COmanage Regsitry v4.0.0
   */
  
  public function add() {
    // This function is intended for use with the Establish Authenticators
    // Enrollment Flow step. (The normal interface renders a link to /index,
    // which we redirect below into the correct token controller.)
    
    // On GET, we redirect into the correct token controller here, otherwise
    // we need to handle this in the token specifc backend
    
    if($this->request->is('get')) {
      $controller = Inflector::tableize($this->pi_token_models[ $this->viewVars['vv_authenticator']['PrivacyIdeaAuthenticator']['token_type']]);
      
      if(!empty($controller)) {
        $this->redirect(
          array(
            'plugin'          => 'privacy_idea_authenticator',
            'controller'      => $controller,
            'action'          => 'add',
            'authenticatorid' => $this->request->params['named']['authenticatorid'],
            'copersonid'      => $this->request->params['named']['copersonid'],
            // For reentry into the Enrollment Flow
            'onFinish'        => $this->request->params['named']['onFinish']
          )
        );
      }
    }
  }
  
  /**
   * Obtain all Standard Objecst (of the model's type).
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function index() {
    // The standard Authenticator code generates links here, so we redirect
    // into the appropriate controller
    
    $controller = Inflector::tableize($this->pi_token_models[ $this->viewVars['vv_authenticator']['PrivacyIdeaAuthenticator']['token_type']]);
    
    $this->redirect(
      array(
        'plugin'          => 'privacy_idea_authenticator',
        'controller'      => $controller,
        'action'          => 'index',
        'authenticatorid' => $this->request->params['named']['authenticatorid'],
        'copersonid'      => $this->request->params['named']['copersonid']
      )
    );
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
    
    // Merge in the permissions calculated by our parent
    $p = array_merge($p, $this->calculateParentPermissions(true));
    
    // Unsupported operations (these are handled in the authenticator specific backends)
    $p['delete'] = false;
    $p['edit'] = false;
    $p['view'] = false;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
