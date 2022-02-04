<?php
/**
 * COmanage Registry Service Eligibility Enroller Controller
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoPetitionsController', 'Controller');

class ServiceEligibilityEnrollerCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "ServiceEligibilityEnrollerCoPetitions";

  public $uses = array('CoPetition',
                       'ServiceEligibilityEnroller.ServiceEligibility',
                       'ServiceEligibilityEnroller.ServiceEligibilitySetting');
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   * - postcondition: Set $sponsors
   *
   * @since  COmanage Registry v4.1.0
   */

  public function beforeRender() {
    parent::beforeRender();
    
    // Pull the set of available services
    $this->set('vv_available_services', $this->ServiceEligibility->availableServices($this->cur_co['Co']['id']));
    
    // Pull the Settings for this CO
    $args = array();
    $args['conditions']['ServiceEligibilitySetting.co_id'] = $this->cur_co['Co']['id'];
    $args['contain'] = false;
    
    $this->set('vv_settings', $this->ServiceEligibilitySetting->find('first', $args));
  }
  
  /**
   * Collect Service Eligibility after initial attributes.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Integer $id       CO Petition ID
   * @param  Array   $onFinish Redirect target on completion
   * @throws InvalidArgumentException
   */
  
  protected function execute_plugin_petitionerAttributes($id, $onFinish) {
    if($this->request->is('post')) {
      // We need a CO Person Role
      $coPersonRoleId = $this->CoPetition->field('enrollee_co_person_role_id', array('CoPetition.id' => $id));
      
      if(!$coPersonRoleId) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_person_roles.1'), $id)));
      }
      
      // Look for any requested eligibilities.
      
      if(!empty($this->request->data['CoPetition']['eligibilities'])) {
        // $this->request->data['CoPetition']['eligibilities'] will be a singleton
        // if !allow_multiple or an array if allow_multiple.
        
        if(is_array($this->request->data['CoPetition']['eligibilities'])) {
          foreach($this->request->data['CoPetition']['eligibilities'] as $e) {
            $this->ServiceEligibility->add(
              $coPersonRoleId,
              $e,
              $this->Session->read('Auth.User.co_person_id'),
              $id,
              false
            );
          }
        } else {
          $this->ServiceEligibility->add(
            $coPersonRoleId,
            $this->request->data['CoPetition']['eligibilities'],
            $this->Session->read('Auth.User.co_person_id'),
            $id,
            false
          );
        }
      }
      
      $this->redirect($onFinish);
    }
    // else just let the form render
  }
}
