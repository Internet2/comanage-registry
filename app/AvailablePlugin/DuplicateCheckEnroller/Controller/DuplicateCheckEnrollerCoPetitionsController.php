<?php
/**
 * COmanage RegistryDuplicate Check Enroller Plugin CoPetitions Controller
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

App::uses('CoPetitionsController', 'Controller');

class DuplicateCheckEnrollerCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "DuplicateCheckEnrollerCoPetitionsController";

  public $uses = array(
    "CoPetition",
    "DuplicateCheckEnroller.DuplicateCheckEnroller"
  );


  /**
   * Plugin functionality following finalize step
   *
   * @param   Integer  $id        CO Petition ID
   * @param   Array    $onFinish  URL, in Cake format
   */

  protected function execute_plugin_finalize($id, $onFinish) {
    // Finished the updates. Return to the petition
    $this->redirect($onFinish);
  }

  /**
   * Plugin functionality following Start step
   *
   * @param   Integer  $id        CO Petition ID
   * @param   Array    $onFinish  URL, in Cake format
   */

  protected function execute_plugin_start($id, $onFinish) {
    $args = array();
    $args['conditions']['DuplicateCheckEnroller.co_enrollment_flow_wedge_id'] = $this->viewVars['vv_efwid'];
    $args['contain'] = false;

    $duplicate_account = $this->DuplicateCheckEnroller->find('first', $args);
    if(empty($duplicate_account)) {
      throw new RuntimeException(_txt('er.duplicate_check_enrollers.cfg.notfound'));
    }


    $this->set('vv_duplicate_account', $duplicate_account);
    $this->set('vv_petition_id', $id);

    $remote_user = getenv($duplicate_account['DuplicateCheckEnroller']['env_remote_user'] ?? IdentifierEnum::ePPN);

    if(empty($remote_user)) {
      throw new RuntimeException(_txt('er.duplicate_check_enrollers.remote_user.notfound'));
    }

    $ident_type = $duplicate_account['DuplicateCheckEnroller']['identifier_type'];

    // CO Person linked to identifier through CO Person
    $co_person = $this->DuplicateCheckEnroller->searchCoPersonDuplicate($this->cur_co["Co"]["id"], $remote_user, $ident_type);
    // CO Person linked to identifier through CO Person
    $co_person_via_org = $this->DuplicateCheckEnroller->findOrgIdentityDuplicate($this->cur_co["Co"]["id"], $remote_user, $ident_type);

    if(!empty($co_person)
       || !empty($co_person_via_org)) {
      // Redirect according to the configuration
      if(!empty($duplicate_account['DuplicateCheckEnroller']['redirect_url'])) {
        $this->redirect($duplicate_account['DuplicateCheckEnroller']['redirect_url']);
      }

      $this->Flash->set(_txt('er.ia.exists', array($remote_user)), array('key' => 'error'));

      $co_person_id = $co_person["CoPerson"]["id"] ?? $co_person_via_org["CoPerson"]["id"] ?? null;
      if(is_null($co_person_id) && !empty($co_person_via_org["OrgIdentity"]["id"])) {
        // The identifier exists but is not linked to a CO Person
        // We will redirect to the root
        $this->redirect("/");
      }

      $this->redirect(array('plugin' => null,
                            'controller' => 'co_people',
                            'action'     => 'canvas',
                             $co_person_id));
    }

    $this->redirect($onFinish);
  }
}