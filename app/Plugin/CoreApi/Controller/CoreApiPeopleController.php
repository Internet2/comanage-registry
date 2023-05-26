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

App::uses('CoreApiController', 'CoreApi.Controller');
class CoreApiPeopleController extends CoreApiController {
  // Class name, used by Cake
  public $name = "CoreApiPeople";

  public $mapper = "CoPeople";

  public $uses = array(
    "Co",
    "CoJob",
    "CoreApi.CoreApi",
    "CoreApi.CoreApiPerson",
    "OrgIdentitySource"
  );

  /**
   * Handle a Core API CO Record delete request
   * /api/co/:coid/core/v1/<model>/:identifier e.g.
   * /api/co/:coid/core/v1/organizations/:identifier
   * /api/co/:coid/core/v1/departments/:identifier
   *
   * @since  COmanage Registry v4.2.0
   */

  public function delete() {
    $modelApiName = $this->modelName;
    $modelMapperName = $this->$modelApiName->mapper;

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


      $expunge_on_delete = isset($this->cur_api['CoreApi']['expunge_on_delete'])
                           && $this->cur_api['CoreApi']['expunge_on_delete'];
      if($expunge_on_delete) {
        $ret = $this->Person->expungeV1($this->cur_api['CoreApi']['co_id'],
                                        $req_identifier,
                                        $this->cur_api['CoreApi']['identifier_type'],
                                        $this->cur_api['CoreApi']['api_user_id']);
      } else {
        $ret = $this->Person->deleteV1($this->cur_api['CoreApi']['co_id'],
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
}