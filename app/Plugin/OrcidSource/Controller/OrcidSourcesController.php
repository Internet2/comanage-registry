<?php
/**
 * COmanage Registry ORCID Source Controller
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SOISController", "Controller");

class OrcidSourcesController extends SOISController {
  // Class name, used by Cake
  public $name = "OrcidSources";

  public $uses = array(
    'OrcidSource.OrcidSource',
    'OrcidSource.OrcidSourceBackend',
    'Oauth2Server'
  );

  function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
    $this->Flash->set(_txt('rs.updated-a3', array('Orcid Data')), array('key' => 'success'));
    return true;
  }

  /**
   * Update a OrcidSource.
   *
   * @since  COmanage Registry v2.0.0
   * @param  integer $id OrcidSource ID
   */
  
  public function edit($id) {
    parent::edit($id);
    
    // Set the (second) callback URL, used for authenticated ORCID linking.
    // We can't scope this down past the plugin URL since our callback will be
    // based on the CO Petition ID, not the plugin instantiation ID.
    $this->set('vv_orcid_redirect_url', $this->OrcidSourceBackend->callbackUrl());

    // First pull our Oauth2Server configuration

    $args = array();
    $args['joins'][0]['table'] = 'servers';
    $args['joins'][0]['alias'] = 'Server';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Oauth2Server.server_id=Server.id';
    $args['joins'][1]['table'] = 'orcid_sources';
    $args['joins'][1]['alias'] = 'OrcidSource';
    $args['joins'][1]['type'] = 'INNER';
    $args['joins'][1]['conditions'][0] = 'OrcidSource.server_id=Server.id';
    $args['conditions']['OrcidSource.id'] = $id;
    $args['contain'] = false;

    $oauth_server = $this->Oauth2Server->find('first', $args);
    $this->set('vv_oauth_server', $oauth_server);

  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v2.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Delete an existing OrcidSource?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing OrcidSource?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing OrcidSources?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing OrcidSource?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
