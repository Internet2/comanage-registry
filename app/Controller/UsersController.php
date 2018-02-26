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
  
  public $uses = array("AuthenticationEvent",
                       "CmpEnrollmentConfiguration",
                       "CoGroup",
                       "CoGroupMember",
                       "CoSetting",
                       "CoTermsAndConditions",
                       "OrgIdentity",
                       "OrgIdentitySource");

  public $components = array(
    'Auth' => array(
      'authenticate' => array(
        'RemoteUser'
      )
    ),
    'RequestHandler',
    'Session'
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
      // At this point, Auth.User.username has been established by the Auth
      // Controller, but nothing else. We now populate the rest of the user's
      // session auth information.
      
      $u = $this->Session->read('Auth.User.username');
      
      if(!empty($u)) {
        if(!$this->Session->check('Auth.User.api_user_id')) {
          // This is an Org Identity. Figure out which Org Identities this username
          // (identifier) is associated with. First, pull the identifiers.
          
          // We use $oargs here instead of $args because we may reuse this below
          $oargs = array();
          $oargs['joins'][0]['table'] = 'identifiers';
          $oargs['joins'][0]['alias'] = 'Identifier';
          $oargs['joins'][0]['type'] = 'INNER';
          $oargs['joins'][0]['conditions'][0] = 'OrgIdentity.id=Identifier.org_identity_id';
          $oargs['conditions']['Identifier.identifier'] = $u;
          $oargs['conditions']['Identifier.login'] = true;
          // Join on identifiers that aren't deleted (including if they have no status)
          $oargs['conditions']['OR'][] = 'Identifier.status IS NULL';
          $oargs['conditions']['OR'][]['Identifier.status <>'] = SuspendableStatusEnum::Suspended;
          // As of v2.0.0, OrgIdentities have validity dates, so only accept valid dates (if specified)
          // Through the magic of containable behaviors, we can get all the associated
          $oargs['conditions']['AND'][] = array(
            'OR' => array(
              'OrgIdentity.valid_from IS NULL',
              'OrgIdentity.valid_from < ' => date('Y-m-d H:i:s', time())
            )
          );
          $oargs['conditions']['AND'][] = array(
            'OR' => array(
              'OrgIdentity.valid_through IS NULL',
              'OrgIdentity.valid_through > ' => date('Y-m-d H:i:s', time())
            )
          );
          // data we need in one clever find
          $oargs['contain'][] = 'PrimaryName';
          $oargs['contain'][] = 'Identifier';
          $oargs['contain']['CoOrgIdentityLink']['CoPerson'][0] = 'Co';
          $oargs['contain']['CoOrgIdentityLink']['CoPerson'][1] = 'CoPersonRole';
          $oargs['contain']['CoOrgIdentityLink']['CoPerson']['CoGroupMember'] = 'CoGroup';
          
          $orgIdentities = $this->OrgIdentity->find('all', $oargs);
          
          // Grab the org IDs and CO information
          $orgs = array();
          $cos = array();
          
          // XXX deprecated as of 3.1.0, remove in 4.0.0
          // Determine if we are collecting authoritative attributes from $ENV
          // (the only support mechanism at the moment). If so, this will be an array
          // of those value. If not, false.
          $envValues = $this->CmpEnrollmentConfiguration->enrollmentAttributesFromEnv();
          
          if(!empty($envValues)) {
            // Walk through the Org Identities and update any configured/collected attributes.
            // Track if we made any changes.
            
            $orgIdentityChanged = false;
            
            foreach($orgIdentities as $o) {
              if(!empty($o['Identifier'])) {
                // Does this org identity's identifier match the authenticated identifier?
                
                foreach($o['Identifier'] as $i) {
                  if(isset($i['login']) && $i['login']
                     && !empty($i['status']) && $i['status'] == StatusEnum::Active
                     && !empty($i['identifier'])
                     && $i['identifier'] == $u) {
                    // We have a match, possibly update associated attributes
                    
                    $newOrgIdentity = $this->OrgIdentity->updateFromEnv($o['OrgIdentity']['id'], $envValues);
                    
                    if(!empty($newOrgIdentity)) {
                      // Update our session store with the new values
                      
                      $orgIdentityChanged = true;
                    }
                    
                    // No need to walk through any other identifiers attached to this org identity
                    break;
                  }
                }
              }
            }
            
            if($orgIdentityChanged) {
              // Simply reread the org identities... this is easier than trying to
              // collate the new identity into the old one. (We don't track all potentially
              // updated attributes in the session.)
              
              $orgIdentities = $this->OrgIdentity->find('all', $oargs);
            }
          }
          
          foreach($orgIdentities as $o) {
            $orgs[] = array(
              'org_id' => $o['OrgIdentity']['id'],
              'co_id' => $o['OrgIdentity']['co_id']
            );
            
            foreach($o['CoOrgIdentityLink'] as $l)
            {
              // If org identities are pooled, OrgIdentity:co_id will be null, so look at
              // the identity links to get the COs (via CO Person).
              
              $cos[ $l['CoPerson']['Co']['name'] ] = array(
                'co_id' => $l['CoPerson']['Co']['id'],
                'co_name' => $l['CoPerson']['Co']['name'],
                'co_person_id' => $l['co_person_id'],
                'co_person' => $l['CoPerson']
              );
              
              // And assemble the Group Memberships
              
              $params = array(
                'conditions' => array(
                  'CoGroupMember.co_person_id' => $l['co_person_id']
                ),
                'contain' => false
              );
              $memberships = $this->CoGroupMember->find('all', $params);
              
              foreach($memberships as $m){
                $params = array(
                  'conditions' => array(
                    'CoGroup.id' => $m['CoGroupMember']['co_group_id']
                  ),
                  'contain' => false
                );
                $result = $this->CoGroup->find('first', $params);
                
                if(!empty($result)) {
                  $group = $result['CoGroup'];
                  
                  $cos[ $l['CoPerson']['Co']['name'] ]['groups'][ $group['name'] ] = array(
                    'co_group_id' => $m['CoGroupMember']['co_group_id'],
                    'name' => $group['name'],
                    'member' => $m['CoGroupMember']['member'],
                    'owner' => $m['CoGroupMember']['owner']
                  );
                }
              }
            }
          }
          
          $this->Session->write('Auth.User.org_identities', $orgs);
          $this->Session->write('Auth.User.cos', $cos);
          
          // Use the primary organizational name as the session name.
          
          if(isset($orgIdentities[0]['PrimaryName'])) {
            $this->Session->write('Auth.User.name', $orgIdentities[0]['PrimaryName']);
          }
          
          // Determine if there are any pending T&Cs
          
          foreach($cos as $co) {
            // First see if T&Cs are enforced at login for this CO
            
            if($this->CoSetting->getTAndCLoginMode($co['co_id']) == TAndCLoginModeEnum::RegistryLogin) {
              $pending = $this->CoTermsAndConditions->pending($co['co_person_id']);
              
              if(!empty($pending)) {
                // Store the pending T&C in the session so that beforeFilter() can check it.
                // This isn't ideal, but should be preferable to beforeFilter performing the
                // check before every action. It also means T&C are enforced once per login
                // rather than if the T&C change in the middle of a user's session.
                
                $this->Session->write('Auth.User.tandc.pending.' . $co['co_id'], $pending);
              }
            }
          }
          
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
          $this->AuthenticationEvent->record($u, AuthenticationEventEnum::RegistryLogin, $_SERVER['REMOTE_ADDR']);
          
          // Update Org Identities associated with an Enrollment Source, if configured.
          // Note we're performing CO specific work here, even though we're not in a CO context yet.
          
          $this->OrgIdentitySource->syncByIdentifier($u);
          
          $this->redirect($this->Auth->redirectUrl());
        } else {
          // This is an API user. We don't do anything special at the moment, other
          // than record the login event
          
          $this->AuthenticationEvent->record($u, AuthenticationEventEnum::ApiLogin, $_SERVER['REMOTE_ADDR']);
        }
      } else {
        throw new RuntimeException(_txt('er.auth.empty'));
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
