<?php
/**
 * COmanage Registry Standard Identifier Validator (SIV) Controller
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class SIVController extends StandardController {
  public $requires_co = true;
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  public function beforeRender() {
    parent::beforeRender();
    
    // Get a pointer to our model names
    $req = $this->modelClass;
    $modelpl = Inflector::tableize($req);
    
    // Find the ID of our parent
    $ivid = -1;
    
    if(!empty($this->params->named['ivid'])) {
      $ivid = filter_var($this->params->named['ivid'],FILTER_SANITIZE_SPECIAL_CHARS);
    } elseif(!empty($this->viewVars[$modelpl][0][$req])) {
      $ivid = $this->viewVars[$modelpl][0][$req]['co_identifier_validator_id'];
    }
    
    $this->set('vv_ivid', $ivid);
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v2.0.0
   */
  
  public function performRedirect() {
    $target = array();
    $target['plugin'] = null;
    $target['controller'] = "co_identifier_validators";
    $target['action'] = 'index';
    $target['co'] = $this->cur_co['Co']['id'];
    
    $this->redirect($target);
  }
}
