<?php
/**
 * COmanage Registry Standard Organization Source (SORS) Controller
 * (We don't call this SOSController to avoid any confusion that name might cause)
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class SORSController extends StandardController {
  // SOISs always need a CO
  public $requires_co = true;

  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.4.0
   */
  
  public function beforeRender() {
    parent::beforeRender();
    
    // Get a pointer to our model names
    $req = $this->modelClass;
    $modelpl = Inflector::tableize($req);
    
    // Find the ID of our parent
    $osid = -1;
    
    if(!empty($this->params->named['osid'])) {
      $osid = filter_var($this->params->named['osid'],FILTER_SANITIZE_SPECIAL_CHARS);
    } elseif(!empty($this->viewVars[$modelpl][0][$req])) {
      $osid = $this->viewVars[$modelpl][0][$req]['organization_source_id'];
    }
    
    $this->set('vv_osid', $osid);
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.4.0
   */
  
  public function performRedirect() {
    $target = array();
    $target['plugin'] = null;
    $target['controller'] = "organization_sources";
    $target['action'] = 'index';
    $target['co'] = $this->cur_co['Co']['id'];
    
    $this->redirect($target);
  }
}
