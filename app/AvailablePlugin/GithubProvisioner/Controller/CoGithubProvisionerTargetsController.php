<?php
/**
 * COmanage Registry CO GitHub Provisioner Targets Controller
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
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SPTController", "Controller");

// This file is generated by Composer
require_once APP . "AvailablePlugin" . DS . "GithubProvisioner" . DS . "Vendor" . DS . "autoload.php";

class CoGithubProvisionerTargetsController extends SPTController {
  // Class name, used by Cake
  public $name = "CoGithubProvisionerTargets";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'github_user' => 'asc'
    )
  );
  
  /**
   * Accept a callback from GitHub.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer CoGithubProvisioner ID
   */
  
  public function callback($id) {
    if(!empty($this->request->query['code'])
       && !empty($this->request->query['state'])) {
      // Cross check the code against 
      
      $state = $this->Session->read('Plugin.GithubProvisioner.state');
      
      if($state == $this->request->query['state']) {
        // Need to pull the current values
        
        $args = array();
        $args['conditions']['CoGithubProvisionerTarget.id'] = $id;
        $args['contain'] = false;
        
        $curdata = $this->CoGithubProvisionerTarget->find('first', $args);
        
        if(!empty($curdata)) {
          // No need to use the cached client here
          $client = new GuzzleHttp\Client(['base_url' => 'https://github.com']);
          
          $response = $client->request('POST', '/login/oauth/access_token',
            [
              'form_params' => [
                'client_id'     => $curdata['CoGithubProvisionerTarget']['client_id'],
                'client_secret' => $curdata['CoGithubProvisionerTarget']['client_secret'],
                'code'          => $this->request->query['code']
              ],
              'headers' => [
                'Accept'        => 'application/json'
              ]
            ]
          );
          
          $json = $response->json();
          
          if(!empty($json['access_token'])) {
            $this->CoGithubProvisionerTarget->id = $id;
            
            if($this->CoGithubProvisionerTarget->saveField('access_token', $json['access_token'])) {
              // Redirect to select org to manage if one is not already set.
              if(empty($curdata['CoGithubProvisionerTarget']['github_org'])) {
                $target = array();
                $target['plugin'] = 'github_provisioner';
                $target['controller'] = 'co_github_provisioner_targets';
                $target['action'] = 'select';
                $target[] = $id;
                
                $this->redirect($target);
              }
            } else {
              $this->Flash->set(_txt('er.db.save'), array('key' => 'error'));
            }
          } else {
            $this->Flash->set(_txt('er.githubprovisioner.access_token'), array('key' => 'error'));
          }
        } else {
          $this->Flash->set(_txt('er.notfound', array(_txt('ct.co_github_provisioner_targets.1'), filter_var($id,FILTER_SANITIZE_SPECIAL_CHARS))),
                            array('key' => 'error'));
        }
      } else {
        $this->Flash->set(_txt('er.githubprovisioner.state'), array('key' => 'error'));
      }
    }
    
    $this->performRedirect();
  }
  
  /**
   * Update a CO GithubProvisioner Target.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - precondition: <id> must exist
   * - postcondition: On GET, $<object>s set
   * - postcondition: On POST success, object updated
   * - postcondition: On POST, session flash message updated
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer CoGithubProvisioner ID
   */
  
  public function edit($id) {
    parent::edit($id);
    
    // Set the callback URL
    $this->set('vv_github_callback_url', array('plugin'     => 'github_provisioner',
                                               'controller' => 'co_github_provisioner_targets',
                                               'action'     => 'callback',
                                               $id));
    
    // Determine if the 'GitHub' type has been configured
    
    $types = $this->CoGithubProvisionerTarget->CoProvisioningTarget->Co->CoPerson->Identifier->types($this->cur_co['Co']['id'], 'type');
    
    // Pass a hint to the view regarding the github type
    $this->set('vv_github_type', in_array('GitHub', array_keys($types)));
    
    $this->set('vv_extended_type_url', array('plugin'     => null,
                                             'controller' => 'co_extended_types',
                                             'action'     => 'index',
                                             'co'         => $this->cur_co['Co']['id'],
                                             'attr'       => 'Identifier'));
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.9.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Accept a callback from GitHub?
    $p['callback'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Provisioning Target?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Provisioning Target?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Provisioning Targets?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Select a GitHub Organization to manage?
    $p['select'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Provisioning Target?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.9.1
   */
  
  public function performRedirect() {
    if($this->action == 'edit'
       && $this->request->is(array('post', 'put'))
       && !empty($this->viewVars['co_github_provisioner_targets'][0]['CoGithubProvisionerTarget']['client_id'])) {
      // This is a save operation, so get a (new) access token
      
      $scope = $this->CoGithubProvisionerTarget->calculateScope($this->viewVars['co_github_provisioner_targets'][0]);
      $state = Security::generateAuthKey();
      
      // Stuff the state key into the session so we can compare it on callback
      $this->Session->write('Plugin.GithubProvisioner.state', $state);
      
      $querystr = 'client_id=' . urlencode($this->viewVars['co_github_provisioner_targets'][0]['CoGithubProvisionerTarget']['client_id'])
                  // Configured callback URL is used if redirect is not explicitly provided
                  //. '&redirect=' . urlencode($this->viewVars['vv_github_callback_url'])
                  . '&scope=' . urlencode($scope)
                  . '&state=' . urlencode($state);
      
      $this->redirect('https://github.com/login/oauth/authorize?' . htmlentities($querystr));
    }
    
    parent::performRedirect();
  }
  
  /**
   * Select a GitHub Organization to manage.
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer CoGithubProvisioner ID
   */
  
  function select($id=null) {
    if($this->request->is('get')) {
      $args = array();
      $args['conditions']['CoGithubProvisionerTarget.id'] = $id;
      $args['contain'] = false;
      
      $curdata = $this->CoGithubProvisionerTarget->find('first', $args);
      
      if(!empty($curdata)
         && !empty($curdata['CoGithubProvisionerTarget']['access_token'])) {
        try {
          // Determine which organizations we could potentially manage
          
          $ownedOrgs = $this->CoGithubProvisionerTarget->ownedOrganizations($curdata['CoGithubProvisionerTarget']['access_token'],
                                                                            $curdata['CoGithubProvisionerTarget']['github_user']);
          
          if(!empty($ownedOrgs)) {
            $this->set('vv_co_github_provisioner_target', $curdata);
            $this->set('vv_owned_github_orgs', $ownedOrgs);
            $this->set('title_for_layout', _txt('pl.githubprovisioner.org.select'));
          } else {
            $this->Flash->set(_txt('er.githubprovisioner.orgs.none'), array('key' => 'error'));
          }
        }
        catch(Exception $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
        }
      } else {
        $this->Flash->set(_txt('er.notfound', array(_txt('ct.co_github_provisioner_targets.1'), filter_var($id,FILTER_SANITIZE_SPECIAL_CHARS))),
                          array('key' => 'error'));
      }
    } else {
      // Save the field and redirect
      
      $this->CoGithubProvisionerTarget->id = $this->request->data['CoGithubProvisionerTarget']['id'];
      
      if($this->CoGithubProvisionerTarget->saveField('github_org', $this->request->data['CoGithubProvisionerTarget']['github_org'])) {
        $this->Flash->set(_txt('pl.githubprovisioner.token.ok'), array('key' => 'success'));
      } else {
        $this->Flash->set(_txt('er.db.save'), array('key' => 'error'));
      }
      
      $this->performRedirect();
    }
  }
}
