<?php
  /*
   * COmanage Gears Users Controller
   * Used by Auth component
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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
   */

  class UsersController extends AppController {
    var $name = 'Users';
    var $components = array('Session');
    
    function isAuthorized()
    {
      // This will be overridden by most Controllers to do method-level authz.
      
      return(true);  
    }
    
    function login()
    {
      // We need to retrieve additional information (roles, basically) about a user
      // prior to authentication completing.  To do that, the app_controller disables
      // auto redirect with the expectation that we'll trigger the redirect here
      // after we're done.
      
      // First, see if we've returned from the external handler with a user set

      $u = $this->Session->read('Auth.external.user');
      
      if(!empty($u))
      {
        // Clear the session var so we don't loop indefinitely
        $this->Session->delete('Auth.external.user');       

        $data = array();
        $data[ $this->Auth->fields['username'] ] = $u; 
        $data[ $this->Auth->fields['password'] ] = "*";
      
        if($this->Auth->login($data))
        {
          // We're logged in. Retrieve some information about the user and stuff it
          // into the session.
          
          if(!$this->Session->check('Auth.User.api_user_id'))
          {
            // This is an Org Identity. Figure out which Org Identities this username
            // (identifier) is associated with. First, pull the identifiers.
            
            $this->loadModel('OrgIdentity');
            $dbo = $this->OrgIdentity->getDataSource();
            
            $args['joins'][0]['table'] = $dbo->fullTableName($this->OrgIdentity->Identifier);
            $args['joins'][0]['alias'] = 'Identifier';
            $args['joins'][0]['type'] = 'INNER';
            $args['joins'][0]['conditions'][0] = 'OrgIdentity.id=Identifier.org_identity_id';
            $args['conditions']['Identifier.identifier'] = $u;
            $args['conditions']['Identifier.login'] = true;
            // Through the magic of containable behaviors, we can get all the associated
            // data we need in one clever find
            $args['contain'][] = 'Name';
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
                
                foreach($l['CoPerson']['CoGroupMember'] as $gm)
                {
                  $cos[ $l['CoPerson']['Co']['name'] ]['groups'][ $gm['CoGroup']['name'] ] = array(
                    'co_group_id' => $gm['co_group_id'],
                    'name' => $gm['CoGroup']['name'],
                    'member' => $gm['member'],
                    'owner' => $gm['owner']
                  );
                }
              }
            }
             
            $this->Session->write('Auth.User.org_identities', $orgs);
            $this->Session->write('Auth.User.cos', $cos);
            
            // Pick a name. We don't really have a good heuristic for this, so for now we'll
            // go with the first one returned, which was probably added first.
            
            $this->Session->write('Auth.User.name', $orgIdentities[0]['Name']);
          }
          else
          {
            // This is an API user. We don't do anything special at the moment.
          }
          
          $this->redirect($this->Auth->redirect());
        }
        // XXX else some error
      }
      else
      {
        // Not logged in, redirect to external Auth engine
        // XXX currently only supporting REMOTE_USER

        $this->Session->write('Auth.external.return', $this->base . "/" . $this->params['url']['url']);
        $this->redirect('/auth/login/index.php');
      }
    }
    
    function logout()
    {
      // Bounce through the external logout and tell it to redirect via the Auth logout

      $this->Session->write('Auth.external.return', $this->Auth->logout());
      $this->redirect('/auth/logout/index.php');
    }
  }
?>