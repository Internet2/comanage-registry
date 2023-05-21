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

  /**
   * Handle a Core API CO People Read API request.
   * /api/co/:coid/core/v1/petitions
   *
   * @since  COmanage Registry v4.3.0
   */

  public function index() {
    $modelApiName = $this->modelName;
    $modelMapperName = $this->$modelApiName->mapper;

    try {
      $query_filters = array();
      // Load the default ordering and pagination settings
      $this->Paginator->settings = $this->paginate;
      $this->Paginator->settings['conditions']["{$modelMapperName}.co_id"] = $this->cur_api['CoreApi']['co_id'];

      // Filter by status
      if(!empty($this->request->query["{$modelMapperName}.status"])) {
        $query_filters[] = 'status';
        $this->Paginator->settings['conditions']["{$modelMapperName}.status"] = $this->request->query["{$modelMapperName}.status"];
      }

      // Filter by Enrollment Flow
      if(!empty($this->request->query["{$modelMapperName}.enrollmentFlow"])) {
        $query_filters[] = 'enrollmentFlow';

        $this->Paginator->settings['conditions']["{$modelMapperName}.co_enrollmentFlow_id"] = $this->request->query["{$modelMapperName}.enrollmentFlow"];
      }

      // Filter by COU
      if(!empty($this->request->query["{$modelMapperName}.cou"])) {
        $query_filters[] = 'cou';
        $cou_name =$this->request->query["{$modelMapperName}.cou"];
        if($cou_name == _txt('op.select.opt.any')) {
          $this->Paginator->settings['conditions'][] = 'CoPetition.cou_id IS NOT NULL';
        } elseif($cou_name == _txt('op.select.opt.none')) {
          $this->Paginator->settings['conditions'][] = 'CoPetition.cou_id IS NULL';
        } else {
          $this->Paginator->settings['conditions']['CoPetition.cou_id'] = $cou_name;
        }
      }

      // Filter by CO Person ID
      if(!empty($this->request->query["{$modelMapperName}.copersonid"])) {
        $query_filters[] = 'copersonid';

        $this->Paginator->settings['conditions']["{$modelMapperName}.enrollee_co_person_id"] = $this->request->query["{$modelMapperName}.copersonid"];
      }

      // CO Person mappings
      $coperson_alias_mapping = array(
        "{$modelMapperName}.enrollee" => "EnrolleePrimaryName",
        "{$modelMapperName}.petitioner" => "PetitionerPrimaryName",
        "{$modelMapperName}.sponsor'"=> "SponsorPrimaryName",
        "{$modelMapperName}.approver" => "ApproverPrimaryName",
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
        $this->Paginator->settings['order']["{$modelMapperName}.id"] = $this->request->query['direction'];
      }
      // Page
      if(!empty($this->request->query['page'])) {
        $this->Paginator->settings['page'] = $this->request->query['page'];
      }

      $modelObj = $this->Paginator->paginate($modelMapperName);

      if(empty($modelObj)) {
        $modelObj = ClassRegistry::init($modelMapperName);
        // The model has a status enum type hint. I use the existing type hint and append the postfix
        $attr_human_readable = array();
        foreach ($query_filters as $filter) {
          $attr_human_readable[] = _txt($modelObj->cm_enum_txt[$filter], null, $this->request->query["{$modelMapperName}." . $filter]);
        }
        throw new InvalidArgumentException(
          _txt('er.notfound', array($modelApiName, implode(',', $attr_human_readable)))
        );
      }

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

}
