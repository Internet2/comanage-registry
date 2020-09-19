<?php
/**
 * COmanage Registry Meem Enroller Reminders Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

//App::uses("SEWController", "Controller");

class MeemRemindersController extends AppController {
  // Class name, used by Cake
  public $name = "MeemReminders";
  
  public $require_co = false;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  function beforeFilter() {
    $this->require_co = false;
    
    if($this->action == 'remind') {
      $this->Auth->allow();
    }
    
    parent::beforeFilter();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Render a reminder?
    $p['remind'] = true;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Render an MFA Enrollment Reminder.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $id MeemEnroller ID
   */
  
  public function remind($id) {
    // Strictly speaking we're using $id wrong, since we're going to treat it as
    // a MeemEnroller ID rather than a MeemReminder ID (which isn't a thing)
    
    $this->loadModel('MeemEnroller.MeemEnroller');
    
    $args = array();
    $args['conditions']['MeemEnroller.id'] = $id;
    $args['contain'] = false;
    
    $cfg = $this->MeemEnroller->find('first', $args);
    
    if(!$cfg || !$cfg['MeemEnroller']['enable_reminder_page']) {
      throw new RuntimeException(_txt('er.meemenroller.disabled'));
    }
    
    $this->set('vv_efid', $cfg['MeemEnroller']['mfa_co_enrollment_flow_id']);
    
    // Check the requested redirect target
    if(!empty($this->request->query('return'))) {
      // Insert the registry URL as always permitted
      $allowList = "/^" . addcslashes(Router::url("/", true), "/") . "/\n";
      $allowList .= $cfg['MeemEnroller']['return_url_allowlist'];
      
      $found = false;
      
      foreach(preg_split('/\R/', $allowList) as $u) {
        if(preg_match($u, $this->request->query('return'))) {
          $found = true;
          break;
        }
      }
      
      if($found) {
        $this->set('vv_return_url', $this->request->query('return'));
      }
    }
    
    $this->set('noLoginLogout', true);
  }
}
