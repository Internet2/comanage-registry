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
          // We're logged in
          // We need to know if the user is an admin, or a collabmin for one or more COs.
          
          // Add more retrieval so we can get CO name
          $this->User->OrgPerson->recursive = 2;
          $orgp = $this->User->OrgPerson->findById($this->Session->read('Auth.User.org_person_id'));
          
          $this->Session->write('Auth.User.name', $orgp['Name']);

          $cos = array();
          
          foreach($orgp['CoPersonSource'] as $c)
          {
            // Create an entry in the session information for each CO the user is a member of

            $cos[ $c['Co']['name'] ] = array(
              'co_id' => $c['co_id'],
              'co_name' => $c['Co']['name'],
              'co_person_id' => $c['co_person_id']
            );
            
            // Retrieve group memberships and attach them as well
            $grps = $this->User->OrgPerson->CoPersonSource->CoPerson->CoGroupMember->findAllByCoPersonId($c['co_person_id']);

            foreach($grps as $g)
            {
              $cos[ $c['Co']['name'] ]['groups'][ $g['CoGroup']['name'] ] = array(
                'co_group_id' => $g['CoGroup']['id'],
                'name' => $g['CoGroup']['name'],
                'member' => $g['CoGroupMember']['member'],
                'owner' => $g['CoGroupMember']['owner']
              );
            }
            
            $this->Session->write('Auth.User.cos', $cos);
          }
          
          // Auth.User.org_person_id
          
          // XXX get rid of this hardcoding
          if($u == 'rest')
            $this->Session->write('Auth.User.role', 'admin');
          else
            $this->Session->write('Auth.User.role', 'member');
          
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