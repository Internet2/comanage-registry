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
  protected $orcidSources;

  public $uses = array(
    'OrcidSource.OrcidSource',
    'OrcidSource.OrcidToken'
  );

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v4.4.0
   * @throws BadRequestException|NotFoundException
   */

  function beforeFilter() {
    _bootstrap_plugin_txt();

    if (empty($this->request->query['coid'])) {
      $this->Api->restResultHeader(
        HttpStatusCodesEnum::HTTP_BAD_REQUEST,
        _txt('er.orcidsource.param.notfound', array(_txt('ct.cos.1') . ' Id'))
      );
      $this->response->send();
      exit;
    }
    if (empty($this->request->query['orcid'])) {
      $this->Api->restResultHeader(
        HttpStatusCodesEnum::HTTP_BAD_REQUEST,
        _txt('er.orcidsource.param.notfound', array('ORCID Identifier'))
      );
      $this->response->send();
      exit;
    }

    // ΧΧΧ We cannot add the orcid tokens in the query since it does implement the Changelog behavior
    //     and the find method will break.
    $args = array();
    $args['joins'][0]['table'] = 'oauth2_servers';
    $args['joins'][0]['alias'] = 'Oauth2Server';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Oauth2Server.server_id=OrcidSource.server_id';
    $args['conditions']['LOWER(Oauth2Server.serverurl) LIKE'] = '%orcid%';
    $args['conditions']['Server.server_type'] = ServerEnum::Oauth2Server;
    $args['conditions']['OrgIdentitySource.co_id'] = $this->request->query['coid'];
    $args['contain'] = false;

    $this->orcidSources = $this->OrcidSource->find('all', $args);

    if(empty($this->orcidSources)) {
      $this->Api->restResultHeader(
        HttpStatusCodesEnum::HTTP_NOT_FOUND,
        _txt('er.notfound-b', array(_txt('ct.orcid_sources.1')))
      );
      $this->response->send();
      exit;
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

    $orcid_source_ids = Hash::extract($this->orcidSources, '{n}.OrcidSource.id');

    // Get the token record(s) from the database
    $args = array();
    $args['conditions']['OrcidToken.orcid_identifier'] = $this->request->query['orcid'];
    $args['conditions']['OrcidToken.orcid_source_id'] = $orcid_source_ids;
    $args['contain'] = false;

    $tokens = $this->OrcidToken->find('all', $args);

    $columnsToDecrypt = array(
      'access_token',
      'id_token',
      'refresh_token'
    );

    $data = array();
    if(!empty($tokens)) {
      foreach ($tokens as $idx => $token) {
        $data[$idx] = array();
        $data[$idx]['orcid'] = $token['OrcidToken']['orcid_identifier'];
        $orcidSourceIndex = array_search($token['OrcidToken']['orcid_source_id'], $orcid_source_ids);
        $data[$idx]['scopes'] = $this->getOauth2ServerScopes(
          $this->orcidSources[$orcidSourceIndex]['Server'],
          $this->orcidSources[$orcidSourceIndex]['OrcidSource']
        );
        foreach ($token['OrcidToken'] as $columm => $value) {
          if(in_array($columm, $columnsToDecrypt)) {
            $data[$idx][$columm] = !empty($value) ? $this->OrcidToken->getUnencrypted($value) : '';
          }
        }
      }
    }

    // todo: Move to its own render file
    $data = array(
      'RequestType' => 'OrcidTokens',
      'Version'     => '1.0',
      'OrcidTokens' => $data
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
    if(!empty($this->request->query['coid'])) {
      return $this->request->query['coid'];
    }
  }

  /**
   * Get the scopes
   *
   * @param array $server         Server Record
   * @param array $orcidSource    OrcidSource record
   *
   * @return string List of scopes
   * @since  COmanage Registry v4.4.0
   */

  public function getOauth2ServerScopes($server, $orcidSource) {
    if(is_bool($orcidSource['scope_inherit']) && $orcidSource['scope_inherit']) {
      $Oauth2Server = ClassRegistry::init('Oauth2Server');
      $args = array();
      $args['conditions']['Oauth2Server.server_id'] = $server['id'];
      $args['contain'] = false;

      $server = $Oauth2Server->find('first', $args);

      return $server['Oauth2Server']['scope'];
    }

    return OrcidSourceEnum::DEFAULT_SCOPE;
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
