<?php
/**
 * COmanage Registry Fiddle Enroller Controller
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoPetitionsController', 'Controller');

class FiddleEnrollerCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "FiddleEnrollerCoPetitions";

  public $uses = array("CoPetition",
                       "FiddleEnroller.FiddleEnroller");
  
  /**
   * Modify CO Person Role values after approval.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Integer $id       CO Petition ID
   * @param  Array   $onFinish Redirect target on completion
   */
  
  protected function execute_plugin_approve($id, $onFinish) {
    // Pull our config
    
    $efwid = $this->viewVars['vv_efwid'];
    
    $args = array();
    $args['conditions']['FiddleEnroller.co_enrollment_flow_wedge_id'] = $efwid;
    $args['contain'] = false;

    $cfg = $this->FiddleEnroller->find('first', $args);

    if(empty($cfg)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.fiddle_enrollers.1'), $efwid)));
    }

    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain'] = array('EnrolleeCoPersonRole');

    $petition = $this->CoPetition->find('first', $args);

    if(empty($petition)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_petitions.1'), $id)));
    }

    foreach(array(
      'copy_approver_to_manager' => 'manager_co_person_id',
      'copy_approver_to_sponsor' => 'sponsor_co_person_id'
    ) as $action => $targetField) {
      if(isset($cfg['FiddleEnroller'][$action])
        && $cfg['FiddleEnroller'][$action]) {
        // This action is enabled

        if(empty($petition['CoPetition']['approver_co_person_id'])) {
          // If we can't find an approver, it's a major error since this configuration
          // requires approval
          throw new RuntimeException(_txt('er.fiddleenroller.approver', array($id)));
        }

        if(empty($petition['CoPetition']['enrollee_co_person_role_id'])) {
          // If there is no CO Person Role, it's a major error since this configuration
          // requires copying the value to the Role
          throw new RuntimeException(_txt('er.fiddleenroller.role'), array($id));
        }

        if(!empty($petition['EnrolleeCoPersonRole'][$targetField])) {
          // If there is already a value, it's not clear what we should do, so for now
          // we throw an error
          throw new RuntimeException(_txt('er.fiddleenroller.target', array($targetField)));
        }

        // Checks complete, copy the value. For now, we let any errors throw an exception.

        $this->CoPetition->EnrolleeCoPersonRole->id = $petition['CoPetition']['enrollee_co_person_role_id'];
        $this->CoPetition->EnrolleeCoPersonRole->saveField($targetField, $petition['CoPetition']['approver_co_person_id']);

        // Create some history
        $actorCoPersonId = $this->Session->read('Auth.User.co_person_id');

        $txt = _txt('pl.fiddleenroller.copied', array($targetField));

        $this->CoPetition->EnrolleeCoPerson->HistoryRecord->record($petition['CoPetition']['enrollee_co_person_id'],
                                                                    null,
                                                                    null,
                                                                    $actorCoPersonId,
                                                                    ActionEnum::CoPersonEditedManual,
                                                                    $txt);

        $this->CoPetition->CoPetitionHistoryRecord->record($id,
                                                            $actorCoPersonId,
                                                            PetitionActionEnum::AttributesUpdated,
                                                            $txt);
      }
    }

    $this->redirect($onFinish);
  }
}
