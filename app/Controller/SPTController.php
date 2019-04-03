<?php
/**
 * COmanage Registry Standard Provisioner Targets (SPT) Controller
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
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class SPTController extends StandardController {
  // SPTs need a CO to be set
  public $requires_co = true;
  
// We can't use $uses because it clobbers our other models and it's hard to
// dynamically reconstruct that list.
//  public $uses = array('Server');

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: requires_co possibly set
   *
   * @since  COmanage Registry v3.2.0
   */

  function beforeFilter() {
    parent::beforeFilter();
    
    // Get a pointer to our model name
    $req = $this->modelClass;
    $model = $this->$req;
    
    // Dynamically adjust validation rules to include the current CO ID for dynamic types.
    // This is a common case for provisioner configuration, but this could plausibly go
    // in StandardController.
    
    foreach($model->validate as $attr => $acfg) {
      if(isset($acfg['content']['rule'][0])
         && $acfg['content']['rule'][0] == 'validateExtendedType') {
        // Inject the current CO so validateExtendedType() works correctly
        
        $vrule = $acfg['content']['rule'];
        $vrule[1]['coid'] = $this->cur_co['Co']['id'];
        
        $model->validator()->getField($attr)->getRule('content')->rule = $vrule;
      }
    }
  }
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v0.9.1
   */
  
  public function beforeRender() {
    parent::beforeRender();
    
    // Get a pointer to our model names
    $req = $this->modelClass;
    $modelpl = Inflector::tableize($req);
    
    // Find the ID of our parent
    $ptid = -1;
    
    if(!empty($this->params->named['ptid'])) {
      $ptid = filter_var($this->params->named['ptid'],FILTER_SANITIZE_SPECIAL_CHARS);
    } elseif(!empty($this->viewVars[$modelpl][0][$req])) {
      $ptid = $this->viewVars[$modelpl][0][$req]['co_provisioning_target_id'];
    }
    
    $this->set('vv_ptid', $ptid);
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.9.1
   */
  
  public function performRedirect() {
    $target = array();
    $target['plugin'] = null;
    $target['controller'] = "co_provisioning_targets";
    $target['action'] = 'index';
    $target['co'] = $this->cur_co['Co']['id'];
    
    $this->redirect($target);
  }
}
