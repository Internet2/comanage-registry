<?php
/**
 * COmanage Registry Users Controller
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class UsersController extends AppController {
  public $name = 'Users';
  
  public $uses = array("OrgIdentitySource");

  public $components = array(
    'Auth' => array(
      'authenticate' => array(
        'RemoteUser'
      )
    ),
    'RequestHandler',
    'Session',
    'Login'
  );

  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry 3.1.0
   */
  
  function beforeFilter() {
    // Since we're overriding, we need to call the parent to run the authz check.
    parent::beforeFilter();

    // Allow logout to process without a login page.
    $this->Auth->allow('logout');
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.4
   * @return Array Permissions
   */
  
  function isAuthorized() {
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Login?
    $p['login'] = true;
    
    // Logout
    $p['logout'] = true;

    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Login a user
   * - precondition: User has been authenticated
   * - precondition: Session updated with User information
   * - postcondition: User logged in via Auth component
   * - postcondition: Redirect to / issued
   *
   * @since  COmanage Registry v0.1
   * @throws RuntimeException
   * @return void
   * @todo   A lot of the data pulled here should instead be referenced via calls to CoRole
   */
  
  public function login() {
    if($this->Auth->login()) {

      if(!$this->Session->check('Auth.User.api_user_id')) {
        if($this->Login->process()) {
          // Determine last login for the identifier. Do this before we record
          // the current login. We don't currently check identifiers associated with
          // other Org Identities because doing so would be a bit challenging...
          // we're logging in at a platform level, which COs do we query? For now,
          // someone who wants more login details can view them via their canvas.
          $lastlogins = array();

          if(!empty($orgIdentities[0]['Identifier'])) {
            foreach($orgIdentities[0]['Identifier'] as $id) {
              if(!empty($id['identifier']) && isset($id['login']) && $id['login']) {
                $lastlogins[ $id['identifier'] ] = $this->AuthenticationEvent->lastlogin($id['identifier']);
              }
            }
          }

          $this->Session->write('Auth.User.lastlogin', $lastlogins);

          // Record the login
          $this->Login->record();
          
          // Update Org Identities associated with an Enrollment Source, if configured.
          // Note we're performing CO specific work here, even though we're not in a CO context yet.
          
          $this->OrgIdentitySource->syncByIdentifier($u);
          
          $this->redirect($this->Auth->redirectUrl());
        } else {
          throw new RuntimeException(_txt('er.auth.empty'));
        }
      }
      else {
        // This is an API user. We don't do anything special at the moment, other
        // than record the login event
        $this->Login->record();
      }
    } else {
      // We're probably here because the session timed out
      
      // This is a bit of a hack. As of Cake v2.5.2, AuthComponent sets a flash message
      // ($this->Auth->authError) on an unauthenticated request, before handing the
      // request off to the login handler. This generates a "Permission Denied" flash
      // after the user authenticates, which is confusing because it appears on a page
      // that they do have permission to see. As a workaround, we'll just clobber the
      // existing auth flash message.
      // (Test case: new user authenticates against an enrollment flow requiring authn.)
      CakeSession::delete('Message.error');
      
      $this->redirect("/auth/login");
      //throw new RuntimeException("Failed to invoke Auth component login");
    }
  }
  
  /**
   * Logout a user
   * - precondition: User has been logged in
   * - postcondition: Curret session auth information is deleted
   * - postcondition: Redirect to / issued
   *
   * @since  COmanage Registry v0.1
   * @return void
   */
  
  public function logout() {
    $this->Session->delete('Auth');
    
    $redirect = "/";
    
    if($this->Session->check('Logout.redirect')) {
      $redirect = $this->Session->read('Logout.redirect');
      
      // Clear the redirect so we don't use it again
      $this->Session->delete('Logout.redirect');
    }
    
    $this->redirect($redirect);
  }
}
