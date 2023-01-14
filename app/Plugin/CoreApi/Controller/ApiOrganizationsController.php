<?php
/**
 * COmanage Registry Core API People Controller
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('ApiController', 'CoreApi.Controller');
class ApiOrganizationsController extends ApiController {
  // Class name, used by Cake
  public $name = "ApiOrganizations";

  public $uses = array(
    "Co",
    "CoJob",
    "CoreApi.CoreApi",
    "CoreApi.ApiOrganization",
  );

  /**
   * Handle a Core API CO Organizations Read API request.
   * /api/co/:coid/core/v1/organizations
   *
   * @since  COmanage Registry v4.2.0
   */

  public function index() {
    try {
      $query_filters = array();
      // Load the default ordering and pagination settings
      $this->Paginator->settings = $this->paginate;
      $this->Paginator->settings['conditions']['Identifier.type'] = $this->cur_api['CoreApi']['identifier_type'];
      $this->Paginator->settings['conditions']['Identifier.deleted'] = false;
      $this->Paginator->settings['conditions']['Identifier.identifier_id'] = null;
      if(!empty($this->request->query['identifier'])) {
        $this->Paginator->settings['conditions']['Identifier.identifier'] = $this->request->query['identifier'];
      }
      $this->Paginator->settings['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
      $this->Paginator->settings['conditions']['Organization.co_id'] = $this->cur_api['CoreApi']['co_id'];
      if(!empty($this->request->query['Organization.status'])) {
        $query_filters[] = 'status';
        $this->Paginator->settings['conditions']['Organization.status'] = $this->request->query['Organization.status'];
      }
      $this->Paginator->settings['joins'][0]['table'] = 'identifiers';
      $this->Paginator->settings['joins'][0]['alias'] = 'Identifier';
      $this->Paginator->settings['joins'][0]['type'] = 'INNER';
      $this->Paginator->settings['joins'][0]['conditions'][0] = 'Identifier.co_person_id=Organization.id';

      // We need all the relational data for the full mode
      if($this->cur_api['CoreApi']['index_response_type'] == ResponseTypeEnum::Full) {
        // While we're here pull the data we need
        $this->Paginator->settings['contain'] = array(
          'EmailAddress',
          'Identifier',
          'Name',
          'Url',
          'Addresses',
          'AdHocAttributes'
        );
      } elseif($this->cur_api['CoreApi']['index_response_type'] == ResponseTypeEnum::IdentifierList) {
        $this->Paginator->settings['contain'] = array(
          'Identifier' => array(
            'conditions' => array(
              'type ' => $this->cur_api['CoreApi']['identifier_type'],
              'deleted' => false,
              'identifier_id' => null
            )));
      }

      // Query offset
      if(!empty($this->request->query['limit'])) {
        $this->Paginator->settings['limit'] = $this->request->query['limit'];
      }
      // Order Direction
      if(!empty($this->request->query['direction'])) {
        $this->Paginator->settings['order']['Organization.id'] = $this->request->query['direction'];
      }
      // Page
      if(!empty($this->request->query['page'])) {
        $this->Paginator->settings['page'] = $this->request->query['page'];
      }

      $coPeople = $this->Paginator->paginate('Organization');

      if(empty($coPeople)) {
        $Organization = ClassRegistry::init('Organization');
        // The model has a status enum type hint. I use the existing type hint and append the postfix
        $attr_human_readable = array();
        foreach ($query_filters as $filter) {
          $attr_human_readable[] = _txt($Organization->cm_enum_txt[$filter], null, $this->request->query['Organization.' . $filter]);
        }
        throw new InvalidArgumentException(
          _txt('er.notfound', array('Person', implode(',', $attr_human_readable)))
        );
      }

      $ret = array();
      if($this->cur_api['CoreApi']['index_response_type'] == ResponseTypeEnum::Full) {
        $ret = $this->CoreApi->readV1Index($this->cur_api['CoreApi']['co_id'], $coPeople);
      } elseif($this->cur_api['CoreApi']['index_response_type'] == ResponseTypeEnum::IdentifierList) {
        // Would it make sense to return the person id as well?
        $ret = Hash::extract($coPeople, '{n}.Identifier.{n}.identifier');
      }

      // Set the results
      $this->set('results', $ret);
      $this->Api->restResultHeader(200);
    }
    catch(InvalidArgumentException $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(404);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(500);
    }
  }
}