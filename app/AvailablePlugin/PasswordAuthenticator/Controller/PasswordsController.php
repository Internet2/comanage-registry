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
  
  // Password Authenticator ID, used by ssr()
  protected $passwordAuthenticatorId = null;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $pool_org_identities set
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function beforeFilter() {
    parent::beforeFilter();
    
    if($this->action == 'ssr') {
      // Check that we have a valid token
      $token = null;
      
      if($this->request->is('get')
         && !empty($this->request->params['named']['token'])) {
        $token = $this->request->params['named']['token'];
      } elseif($this->request->is('post')
               && !empty($this->request->data['Password']['token'])) {
        $token = $this->request->data['Password']['token'];
      }
      
      if($token) {
        $AuthenticatorResetToken = ClassRegistry::init('RecoveryWidget.AuthenticatorResetToken');

        try {
          // Test the token without invalidating it
          $AuthenticatorResetToken->validateToken($token, false);
          
          // We need the form to render without authentication
          $this->Auth->allow();
        }
        catch(InvalidArgumentException $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
          $this->redirect('/');
        }
      }
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.0.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    if($this->action == 'ssr') {
      // Map the token (if found) to a CO. Note we don't check for expired tokens
      // yet since if we fail here the user will get a confusing error ("No CO
      // Specified").
      
      $token = null;
      
      if($this->request->is('get')
         && !empty($this->request->params['named']['token'])) {
        $token = $this->request->params['named']['token'];
      } elseif($this->request->is('post')
               && !empty($this->request->data['Password']['token'])) {
        $token = $this->request->data['Password']['token'];
      }
      
      if($token) {
        $AuthenticatorResetToken = ClassRegistry::init('RecoveryWidget.AuthenticatorResetToken');

        try {
          return $AuthenticatorResetToken->getCoIdForToken($token);
        }
        catch(Exception $e) {
          // Force a different error to be slightly less confusing
          $this->Flash->set(_txt('er.passwordauthenticator.token.notfound'), array('key' => 'error'));
          $this->redirect('/');
        }
      }
    }
    
    return parent::calculateImpliedCoId($data);
  }

  /**
   * Generate a new password.
   *
   * @since  COmanage Registry v3.3.0
   */
  
  public function generate() {
    $this->Password->PasswordAuthenticator->setConfig($this->viewVars['vv_authenticator']);
    
    try {
      if($this->viewVars['vv_authenticator']['PasswordAuthenticator']['password_source'] != PasswordAuthPasswordSourceEnum::AutoGenerate) {
        throw new InvalidArgumentException(_txt('er.passwordauthenticator.source', PasswordAuthPasswordSourceEnum::AutoGenerate));
      }

      $actorCoPersonId = $this->request->is('restful') ? null : $this->Session->read('Auth.User.co_person_id');
      $actorApiUserId = $this->request->is('restful') ? $this->Auth->User('id') : null;

      $this->set('vv_token', $this->Password->generateToken($this->viewVars['vv_authenticator']['PasswordAuthenticator']['id'],
                                                            $this->viewVars['vv_co_person']['CoPerson']['id'],
                                                            $this->viewVars['vv_authenticator']['PasswordAuthenticator']['max_length'],
                                                            $actorCoPersonId,
                                                            $actorApiUserId));
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
   * Self service reset a password.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function ssr() {
    $authenticatorId = null;
    
    $AuthenticatorResetToken = ClassRegistry::init('RecoveryWidget.AuthenticatorResetToken');

    if($this->request->is('get')) {
      if(!empty($this->request->named['token'])) {
        // We're back from the email message. Verify that the token is valid.
        // If so, pass the token to the view for embedding in the reset form.
        
        try {
          $tokenInfo = $AuthenticatorResetToken->validateToken($this->request->named['token'], false);
          
          if(!empty($tokenInfo['co_person_id'])) {
            // The form will embed the token for the actual reset request
            $this->set('vv_token', $this->request->named['token']);
            
            // Also pass the CO Person name to the view
            $args = array();
            $args['conditions']['Name.co_person_id'] = $tokenInfo['co_person_id'];
            $args['conditions']['Name.primary_name'] = true;
            $args['contain'] = false;
            
            $name = $this->Password->CoPerson->Name->find('first', $args);
            
            $this->set('vv_name', generateCn($name['Name']));
          }
        } catch(Exception $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
        }
      } else {
        $this->Flash->set('er.passwordauthenticator.token.notfound', array('key' => 'error'));
      }
    } elseif($this->request->is('post')) {
      if(!empty($this->request->data['Password'])) {
        try {
          // Before we try to set the password we need to grab the redirect URL
          // associated with the token (if configured)

          $tokenInfo = $AuthenticatorResetToken->validateToken($this->request->data['Password']['token'], false);
          
          $r = $this->Password->PasswordAuthenticator->manage($this->request->data, null);
          
          // Trigger provisioning
          $this->Password->PasswordAuthenticator->Authenticator->provision($tokenInfo['co_person_id']);

          // And register a notification
          $this->Password->PasswordAuthenticator->notify($tokenInfo['co_person_id']);

          $this->Flash->set($r, array('key' => 'success'));
          
          if(!empty($tokenInfo['redirect_url'])) {
            $this->redirect($tokenInfo['redirect_url']);
          }
        }
        catch(Exception $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
          
          // On error we need to re-include the token so the form can be resubmitted
          if(!empty($this->request->data['Password']['token'])) {
            $this->set('vv_token', $this->request->data['Password']['token']);
            
            // We need to re-populate vv_name here
            
            $tokenInfo = $AuthenticatorResetToken->validateToken($this->request->data['Password']['token'], false);
            
            if(!empty($tokenInfo['co_person_id'])) {
              // The form will embed the token for the actual reset request
              $this->set('vv_token', $this->request->data['Password']['token']);
              
              // Also pass the CO Person name to the view
              $args = array();
              $args['conditions']['Name.co_person_id'] = $tokenInfo['co_person_id'];
              $args['conditions']['Name.primary_name'] = true;
              $args['contain'] = false;
              
              $name = $this->Password->CoPerson->Name->find('first', $args);
              
              $this->set('vv_name', generateCn($name['Name']));
            }
          }
        }
      }
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
    
    // beforeFilter() will determine if ssr() is permitted
    $p['ssr'] = false;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
