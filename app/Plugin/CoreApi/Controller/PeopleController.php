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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// This is based heavily on ApiSource::ApiController... maybe merge them,
// possibly with the hypothetical API plugin type

App::uses('ApiController', 'CoreApi.Controller');
class PeopleController extends ApiController {
  // Class name, used by Cake
  public $name = "People";

  /**
   * Handle a Core API CO Person record create
   *
   * @since  COmanage Registry v4.1.0
   */

  public function create() {
    try {
      $ret = $this->CoreApi->createV1($this->cur_api['CoreApi']['co_id'],
                                      $this->request->data,
                                      $this->cur_api['CoreApi']['api_user_id']);

      $this->set('results', array_values($ret));
      $this->Api->restResultHeader(201);
    }
    catch(InvalidArgumentException $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(404);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      if(isset(_txt('en.http.status.codes')[$e->getCode()])) {
        $this->Api->restResultHeader($e->getCode());
      } else {
        $this->Api->restResultHeader(500);
      }
    }
  }

  /**
   * Handle a Core API CO People delete request
   * /api/co/:coid/core/v1/people/:identifier
   * The action has two possible outcomes. Either transition the user to status delete or expunge the user
   * 1. Transition to status delete implies the transition of CoPerson Roles to status deleted
   *
   * @since  COmanage Registry v4.1.0
   */

  public function delete() {
    try {
      if(empty($this->request->params['identifier'])
        && empty($this->request->query["identifier"])) {
        // We shouldn't really get here since routes.php shouldn't allow it
        throw new InvalidArgumentException(_txt('er.notprov'));
      }

      $req_identifier = !empty($this->request->params["identifier"])
        ? $this->request->params["identifier"]
        : (!empty($this->request->query["identifier"])
          ? $this->request->query["identifier"]
          : null);


      $expunge_on_delete = !isset($this->cur_api['CoreApi']['expunge_on_delete'])
        ? false : $this->cur_api['CoreApi']['expunge_on_delete'];
      if($expunge_on_delete) {
        $ret = $this->CoreApi->expungeV1($this->cur_api['CoreApi']['co_id'],
                                         $req_identifier,
                                         $this->cur_api['CoreApi']['identifier_type'],
                                         $this->cur_api['CoreApi']['api_user_id']);
      } else {
        $ret = $this->CoreApi->deleteV1($this->cur_api['CoreApi']['co_id'],
                                        $req_identifier,
                                        $this->cur_api['CoreApi']['identifier_type']);
      }

      if(!empty($ret) && !is_bool($ret)) {
        $this->set('results', $ret);
      }
      $this->Api->restResultHeader(200);
    }
    catch(InvalidArgumentException $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(404);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      if(isset(_txt('en.http.status.codes')[$e->getCode()])) {
        $this->Api->restResultHeader($e->getCode());
      } else {
        $this->Api->restResultHeader(500);
      }
    }
  }

  /**
   * Handle a Core API CO People Read API request.
   * /api/co/:coid/core/v1/people
   *
   * @since  COmanage Registry v4.1.0
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
      $this->Paginator->settings['conditions']['CoPerson.co_id'] = $this->cur_api['CoreApi']['co_id'];
      if(!empty($this->request->query['CoPerson.status'])) {
        $query_filters[] = 'status';
        $this->Paginator->settings['conditions']['CoPerson.status'] = $this->request->query['CoPerson.status'];
      }
      $this->Paginator->settings['joins'][0]['table'] = 'identifiers';
      $this->Paginator->settings['joins'][0]['alias'] = 'Identifier';
      $this->Paginator->settings['joins'][0]['type'] = 'INNER';
      $this->Paginator->settings['joins'][0]['conditions'][0] = 'Identifier.co_person_id=CoPerson.id';

      // We need all the relational data for the full mode
      if($this->cur_api['CoreApi']['index_response_type'] == ResponseTypeEnum::Full) {
        // While we're here pull the data we need
        $this->Paginator->settings['contain'] = array(
          'CoPersonRole' => array(
            'Address',
            'AdHocAttribute',
            'TelephoneNumber'
          ),
          'CoGroupMember',
          'CoOrgIdentityLink' => array(
            'OrgIdentity' => array(
              'Address',
              'AdHocAttribute',
              'EmailAddress',
              'Identifier',
              'Name',
              'TelephoneNumber',
              'Url'
            ),
          ),
          'EmailAddress',
          'Identifier',
          'Name',
          'Url'
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
        $this->Paginator->settings['order']['CoPerson.id'] = $this->request->query['direction'];
      }
      // Page
      if(!empty($this->request->query['page'])) {
        $this->Paginator->settings['page'] = $this->request->query['page'];
      }

      $coPeople = $this->Paginator->paginate('CoPerson');

      if(empty($coPeople)) {
        $CoPerson = ClassRegistry::init('CoPerson');
        // The model has a status enum type hint. I use the existing type hint and append the postfix
        $attr_human_readable = array();
        foreach ($query_filters as $filter) {
          $attr_human_readable[] = _txt($CoPerson->cm_enum_txt[$filter], null, $this->request->query['CoPerson.' . $filter]);
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

  /**
   * Handle a Core API CO Person Read API request.
   * /api/co/:coid/core/v1/people#show
   *
   * @since  COmanage Registry v4.0.0
   */

  public function read() {
    // We basically just pull the current record and return it.
    // We could inject some metadata (modified time, etc) but currently we don't.

    try {
      if(empty($this->request->params['identifier'])) {
        // We shouldn't really get here since routes.php shouldn't allow it
        throw new InvalidArgumentException(_txt('er.notprov'));
      }


      $ret = $this->CoreApi->readV1($this->cur_api['CoreApi']['co_id'],
                                    $this->request->params['identifier'],
                                    $this->cur_api['CoreApi']['identifier_type']);

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
   * Handle a Match Resolution Callback Notification
   *
   * @since  COmanage Registry v4.1.0
   */

  public function resolveMatch() {
    if(empty($this->request->data['sor'])
      || empty($this->request->data['sorid'])
      || empty($this->request->data['referenceId'])) {
      $this->set('results', array('error' => _txt('er.coreapi.json.invalid')));
      $this->Api->restResultHeader(400);
      return;
    }

    // Find the OIS associated with this sor label. There should be exactly one.

    $args = array();
    $args['conditions']['OrgIdentitySource.co_id'] = $this->request->params['coid'];
    $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['OrgIdentitySource.sor_label'] = $this->request->data['sor'];
    $args['contain'] = false;

    $ois = $this->OrgIdentitySource->find('first', $args);

    if(empty($ois)) {
      $this->set('results', array('error' => _txt('er.coreapi.sor.notfound', array($this->request->data['sor']))));
      $this->Api->restResultHeader(400);
      return;
    }

    // If an OrgIdentitySource Create Org Identity operations fails because of
    // multiple choices returned by the Match server, there is no record maintained
    // (except that ApiSource will maintain its own record), so we basically
    // need to queue a new job to start the process over. This implies we don't
    // have a way to validate SORID, but the Job can do that. We also don't
    // try to map the Reference ID here, since that could theoretically change
    // before the Job actually runs (but probably it won't).

    try {
      $params = array(
        'ois_id'        => $ois['OrgIdentitySource']['id'],
        'source_key'    => $this->request->data['sorid'],
        'reference_id'  => $this->request->data['referenceId']
      );

      $this->CoJob->register($this->request->params['coid'],
                             'CoreJob.Sync',
                             null,
                             null,
                             _txt('pl.coreapi.match.resolved'),
                             true,
                             true,
                             $params);
      $this->Api->restResultHeader(202);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(500);
    }

    // Note there's nothing to attach a history record to yet, so we don't
  }

  /**
   * Handle a Core API CO Person Write API Update request.
   *
   * @since  COmanage Registry v4.0.0
   */

  public function update() {
    try {
      if(empty($this->request->params['identifier'])
        && empty($this->request->query["identifier"])) {
        // We shouldn't really get here since routes.php shouldn't allow it
        throw new InvalidArgumentException(_txt('er.notprov'));
      }

      $req_identifier = !empty($this->request->params["identifier"])
        ? $this->request->params["identifier"]
        : (!empty($this->request->query["identifier"])
          ? $this->request->query["identifier"]
          : null);

      $ret = $this->CoreApi->upsertV1($this->cur_api['CoreApi']['co_id'],
                                      $req_identifier,
                                      $this->cur_api['CoreApi']['identifier_type'],
                                      $this->request->data,
                                      $this->cur_api['CoreApi']['api_user_id']);

      $this->set('results', $ret);
      $this->Api->restResultHeader(200);
    }
    catch(InvalidArgumentException $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(404);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      if(isset(_txt('en.http.status.codes')[$e->getCode()])) {
        $this->Api->restResultHeader($e->getCode());
      } else {
        $this->Api->restResultHeader(500);
      }
    }
  }
}