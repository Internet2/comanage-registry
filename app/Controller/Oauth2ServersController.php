<?php
/**
 * COmanage Registry OAuth2 Servers Controller
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class Oauth2ServersController extends StandardController {
  // Class name, used by Cake
  public $name = "Oauth2Servers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 50,
    'order' => array(
      'Oauth2Server.serverurl' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $view_contains = array(
    'Server'
  );
  
  /**
   * OAuth callback.
   *
   * @since  COmanage Registry v3.2.0
   * @param  integer $id Oauth2Server ID
   */
  
  public function callback($id) {
    // We have to look in $_GET because what we get back isn't a Cake style named parameter
    // (ie: code=foo, not code:foo)
    
    try {
      if(empty($_GET['code']) || empty($_GET['state'])) {
        throw new RuntimeException(_txt('er.server.oauth2.callback'));
      }
      
      // Verify that state is our hashed session ID, as per RFC6749 §10.12
      // recommendations to prevent CSRF.
      // https://tools.ietf.org/html/rfc6749#section-10.12
      
      if($_GET['state'] != hash('sha256', session_id())) {
        throw new RuntimeException(_txt('er.server.oauth2.state'));
      }

      $response = $this->Oauth2Server->exchangeCode($id, $_GET['code'], $this->Oauth2Server->redirectUri($id));
      
      $this->Flash->set(_txt('rs.server.oauth2.token.ok'), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
  
  /**
   * Update an Oauth2Server.
   *
   * @since  COmanage Registry v3.2.0
   * @param  integer $id Oauth2Server ID
   */

  public function edit($id) {
    parent::edit($id);
    
    $this->set('vv_redirect_uri', $this->Oauth2Server->redirectUri($id));
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.2.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Accept an OAuth callback?
    $p['callback'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit an existing OAuth2  Server?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Obtain an access token for this OAuth2 Server?
    $p['token'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View this OAuth2 Server?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.2.0
   */

  function performRedirect() {
    $target = array();
    $target['plugin'] = null;
    
    if(!empty($this->request->params['pass'][0])) {
      $target['controller'] = 'oauth2_servers';
      $target['action'] = 'edit';
      $target[] = filter_var($this->request->params['pass'][0], FILTER_SANITIZE_SPECIAL_CHARS);
    } else {
      $target['controller'] = "servers";
      $target['action'] = 'index';
      $target['co'] = $this->cur_co['Co']['id'];
    }
    
    $this->redirect($target);
  }
  
  /**
   * Obtain an access token for a Oauth2Server.
   *
   * @since  COmanage Registry v3.2.0
   * @param  integer $id Oauth2Server ID
   */

  public function token($id) {
    // Pull our configuration, initially to find out what type of grant type we need
    $args = array();
    $args['conditions']['Oauth2Server.id'] = $id;
    $args['contain'] = false;
    
    $osrvr = $this->Oauth2Server->find('first', $args);
    
    if(!$osrvr) {
      $this->Flash->set(_txt('er.notfound', array(_txt('ct.oauth2_servers.1'), $id)), array('key' => 'error'));
      $this->performRedirect();
    }
    
    switch($osrvr['Oauth2Server']['access_grant_type']) {
      case Oauth2GrantEnum::AuthorizationCode:
        // Issue a redirect to the server

        $targetUrl = $osrvr['Oauth2Server']['serverurl']
                   . '/authorize?response_type=code'
                   . '&client_id=' . $osrvr['Oauth2Server']['clientid']
                   . '&redirect_uri=' . urlencode($this->Oauth2Server->redirectUri($id))
                   . '&state=' . hash('sha256', session_id());
        
        $this->redirect($targetUrl);
        break;
      case Oauth2GrantEnum::ClientCredentials:
        // Make a direct call to the server
        try {
          $this->Oauth2Server->obtainToken($id, 'client_credentials');
          $this->Flash->set(_txt('rs.server.oauth2.token.ok'), array('key' => 'success'));
        }
        catch(Exception $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
        }
        break;
      default:
        // No other flows currently supported
        throw new LogicException('NOT IMPLEMENTED');
        break;
    }
    
    $this->performRedirect();
  }
}
