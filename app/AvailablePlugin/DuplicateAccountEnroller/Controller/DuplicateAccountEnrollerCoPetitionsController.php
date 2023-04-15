<?php
/**
 * COmanage Registry Duplicate Account Enroller CoPetitions Controller
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

class DuplicateAccountEnrollerCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "DuplicateAccountEnrollerCoPetitionsController";

  public $uses = array(
    "CoPetition",
    "DuplicateAccountEnroller.DuplicateAccountEnroller"
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
    $args                                                                      = array();
    $args['conditions']['DuplicateAccountEnroller.co_enrollment_flow_wedge_id'] = $this->viewVars['vv_efwid'];

    $duplicate_account = $this->DuplicateAccountEnroller->find('first', $args);
    $this->set('vv_duplicate_account', $duplicate_account);
    $this->set('vv_petition_id', $id);


    $this->redirect($onFinish);
  }
}