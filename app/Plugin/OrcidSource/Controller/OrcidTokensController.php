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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('StandardController', 'Controller');

class OrcidTokensController extends StandardController {
  // Class name, used by Cake
  public $name = 'OrcidTokens';

  /** @var OrcidSource */
  protected $orcidSource;

  public $uses = array(
    'OrcidSource.OrcidSource',
    'OrcidSource.OrcidToken'
  );

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v4.4.0
   * @throws InvalidArgumentException
   */

  function beforeFilter() {
    if (empty($this->request->query['orcidsid'])) {
      throw new BadRequestException(_txt('er.orcidsource.param.notfound', array(_txt('ct.orcid_sources.1') . ' Id')));
    }
    if (empty($this->request->query['orcid'])) {
      throw new BadRequestException(_txt('er.orcidsource.param.notfound', array('ORCID Identifier')));
    }

    $args = array();
    $args['conditions']['OrcidSource.id'] = $this->request->query['orcidsid'];
    $args['contain'] = false;

    $this->orcidSource = $this->OrcidSource->find('first', $args);

    if(empty($this->orcidSource)) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array(_txt('ct.orcid_sources.1'),
                                                $this->request->query['orcidsid'])));
    }

    return parent::beforeFilter();
  }

  /**
   * Get a token
   *
   * @since  COmanage Registry v4.4.0
   */

  public function token() {
    $this->request->allowMethod(array('ajax', 'restful'));
    $this->layout = 'ajax';

    // Get the token record from the database
    $args = array();
    $args['conditions']['OrcidToken.orcid_identifier'] = $this->request->query['orcid'];
    $args['conditions']['OrcidToken.orcid_source_id'] = $this->orcidSource['OrcidSource']['id'];
    $args['contain'] = false;

    $token = $this->OrcidToken->find('first', $args);

    $columnsToDecrypt = array(
      'access_token',
      'id_token',
      'refresh_token'
    );

    $data = array();
    if(!empty($token)) {
      $data['orcid'] = $token['OrcidToken']['orcid_identifier'];
      foreach ($token['OrcidToken'] as $columm => $value) {
        if(in_array($columm, $columnsToDecrypt)) {
          $data[$columm] = !empty($value) ? $this->OrcidToken->getUnencrypted($value) : '';
        }
      }
    }

    $data = array(
      'token' => $data
    );
    $this->set(compact('data')); // Pass $data to the view
    $this->set('_serialize', 'data');
  }

  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v4.4.0
   * @return Integer The CO ID if found, or -1 if not
   */

  public function parseCOID($data = null) {
    if(!empty($this->orcidSource['OrgIdentitySource']['co_id'])) {
      return $this->orcidSource['OrgIdentitySource']['co_id'];
    }
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

    // Retrieve a token record
    $p['token'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
