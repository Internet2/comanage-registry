<?php
/**
 * COmanage Registry ORCID Tokens Controller
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

class OrcidTokensController extends SOISController {
  // Class name, used by Cake
  public $name = 'OrcidTokens';

  protected $orcid_identifier = null;

  public $uses = array(
    'OrcidSource.OrcidSource',
    'OrcidSource.OrcidToken'
  );

  /**
   * Get a token
   *
   * @since  COmanage Registry v2.0.0
   */

  public function token() {
    $this->request->allowMethod(array('ajax', 'rest'));
    $this->layout = 'ajax';

    if (empty($this->request->params['pass'][0])) {
      throw new BadRequestException(_txt('er.orcidsource.param.notfound', array(_txt('ct.orcid_sources.1') . ' Id')));
    }
    if (empty($this->request->query['orcid'])) {
      throw new BadRequestException(_txt('er.orcidsource.param.notfound', array('ORCID Identifier')));
    }
    // Get the token record from the database
    $args = array();
    $args['conditions']['OrcidToken.orcid_identifier'] = $this->request->query['orcid'];
    $args['conditions']['OrcidToken.orcid_source_id'] = $this->request->params['pass'][0];
    $args['contain'] = false;

    $token = $this->OrcidToken->find('first', $args);

    $this->set('vv_token', $token);
    $data = array(
      'token' => $token
    );
    $this->set(compact('data')); // Pass $data to the view
    $this->set('_serialize', 'data');
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
