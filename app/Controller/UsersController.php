<?php
/**
 * COmanage Registry Users Controller
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class UsersController extends AppController {
  public $name = 'Users';
  
  public $uses = array("CoGroup", "CoGroupMember", "OrgIdentity");

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
          
          $args['joins'][0]['table'] = 'identifiers';
          $args['joins'][0]['alias'] = 'Identifier';
          $args['joins'][0]['type'] = 'INNER';
          $args['joins'][0]['conditions'][0] = 'OrgIdentity.id=Identifier.org_identity_id';
          $args['conditions']['Identifier.identifier'] = $u;
          $args['conditions']['Identifier.login'] = true;
          // Join on identifiers that aren't deleted (including if they have no status)
          $args['conditions']['OR'][] = 'Identifier.status IS NULL';
          $args['conditions']['OR'][]['Identifier.status <>'] = StatusEnum::Deleted;
          // Through the magic of containable behaviors, we can get all the associated
          // data we need in one clever find
          $args['contain'][] = 'PrimaryName';
          $args['contain']['CoOrgIdentityLink']['CoPerson'][0] = 'Co';
          $args['contain']['CoOrgIdentityLink']['CoPerson']['CoGroupMember'] = 'CoGroup';
          
          $orgIdentities = $this->OrgIdentity->find('all', $args);
          
          // Grab the org IDs and CO information
          $orgs = array();
          $cos = array();
          
          foreach($orgIdentities as $o)
          {
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
                'co_person_id' => $l['co_person_id']
              );
              
              // And assemble the Group Memberships
              
              $params = array(
                'conditions' => array(
                  'CoGroupMember.co_person_id' => $l['co_person_id']
                  )
                );
              $memberships = $this->CoGroupMember->find('all', $params);

              foreach($memberships as $m){
                $params = array(
                  'conditions' => array(
                    'CoGroup.id' => $m['CoGroupMember']['co_group_id']
                    )
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
          
          $this->redirect($this->Auth->redirectUrl());
        } else {
          // This is an API user. We don't do anything special at the moment.
        }
      } else {
        throw new RuntimeException("Found empty username at login");
      }
    } else {
      // We're probably here because the session timed out
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
    $this->Session->delete('Auth.User');
    // XXX should redirect to /auth/logout/index.php to trip external logout
    $this->redirect("/");
  }
}
