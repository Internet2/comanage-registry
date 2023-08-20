<?php
/**
 * COmanage Registry Core API Petitions Controller
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoreApiController', 'CoreApi.Controller');
class CoreApiPetitionsController extends CoreApiController {
  // Class name, used by Cake
  public $name = "CoreApiPetitions";

  public $mapper = "CoPetitions";

  public $uses = array(
    "Co",
    "CoJob",
    "CoreApi.CoreApi",
    "CoreApi.CoreApiPetition",
  );

  public function pullPetitionData() {
    $modelApiName = $this->modelName;
    $modelMapperName = $this->$modelApiName->mapper;

    // Validate the query parameters
    $this->params->query = $this->CoreApiPetition->validateQueryParams($this->params->query);

    // Load the default ordering and pagination settings
    $this->Paginator->settings = $this->paginate;
    $this->Paginator->settings['conditions']["CoPetition.co_id"] = (int)$this->cur_api['CoreApi']['co_id'];

    // Filter by status
    if(!empty($this->request->query["status"])) {
      $this->Paginator->settings['conditions']["CoPetition.status"] = $this->request->query["status"];
    }

    // Filter by Petition ID
    if(!empty($this->request->query["id"])
        || !empty($this->request->params["id"])) {
      $this->Paginator->settings['conditions']["CoPetition.id"] = (int)($this->request->query["id"] ?? $this->request->params["id"]);
    }

    // Filter by Enrollment Flow
    if(!empty($this->request->query["enrollmentflowid"])) {
      $this->Paginator->settings['conditions']["CoPetition.co_enrollment_flow_id"] = (int)$this->request->query["enrollmentflowid"];
    }

    // Filter by COU
    if(!empty($this->request->query["couid"])) {
      $this->Paginator->settings['conditions']['CoPetition.cou_id'] = (int)$this->request->query["couid"];
    }

    // CO Person mappings
    $coperson_alias_mapping = array(
      "enrollee" => "EnrolleePrimaryName",
      "petitioner" => "PetitionerPrimaryName",
      "sponsor'"=> "SponsorPrimaryName",
      "approver" => "ApproverPrimaryName",
    );

    // Filter by Name
    foreach($coperson_alias_mapping as $search_field => $class) {
      if(!empty($this->request->query[$search_field])) {
        $searchterm = $this->request->query[$search_field];
        $searchterm = strtolower(str_replace(urlencode("/"), "/", $searchterm));
        $this->Paginator->settings['conditions']['AND'][] = array(
          'OR' => array(
            'LOWER('. $class . '.family) LIKE' => '%' . $searchterm . '%',
            'LOWER('. $class . '.given) LIKE' => '%' . $searchterm . '%',
          )
        );
      }
    }

    // We need all the relational data for the full mode
    $this->Paginator->settings['link'] = $this->$modelApiName->index_contains;

    // Query offset
    if(!empty($this->request->query['limit'])) {
      $this->Paginator->settings['limit'] = $this->request->query['limit'];
    }
    // Order Direction
    if(!empty($this->request->query['direction'])) {
      $this->Paginator->settings['order']["CoPetition.id"] = $this->request->query['direction'];
    }
    // Page
    if(!empty($this->request->query['page'])) {
      $this->Paginator->settings['page'] = $this->request->query['page'];
    }
  }

  /**
   * Handle a Core API CO People Index API request.
   * /api/co/:coid/core/v1/petitions
   *
   * @since  COmanage Registry v4.3.0
   */

  public function index() {
    $modelApiName = $this->modelName;
    $modelMapperName = $this->$modelApiName->mapper;

    try {
      $this->pullPetitionData();
      $modelObj = $this->Paginator->paginate($modelMapperName);

      $ret = $this->$modelApiName->readV1Index($this->cur_api['CoreApi']['co_id'], $modelObj);

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

  /**
   * Handle a Core API CO People Read API request.
   * /api/co/:coid/core/v1/petitions/:id
   *
   * @since  COmanage Registry v4.3.0
   */

  public function read() {
    $modelApiName = $this->modelName;
    $modelMapperName = $this->$modelApiName->mapper;

    try {
      $this->pullPetitionData();
      $modelObj = $this->Paginator->paginate($modelMapperName);

      $ret = $this->$modelApiName->readV1Index($this->cur_api['CoreApi']['co_id'], $modelObj);

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
