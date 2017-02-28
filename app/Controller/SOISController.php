<?php
/**
 * COmanage Registry Standard Org Identity Source (SOIS) Controller
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class SOISController extends StandardController {
  // SOISs only need a CO to be set if org identities are NOT pooled
  public $requires_co = false;

  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v1.1.0
   */
  
  function beforeFilter() {
    // This controller may or may not require a CO, depending on how
    // the CMP Enrollment Configuration is set up. Check and adjust before
    // beforeFilter is called.
    
    $this->loadModel('CmpEnrollmentConfiguration');
    $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
    
    if(!$pool) {
      $this->requires_co = true;
      
      // Associate the CO model
//      $this->OrgIdentitySource->bindModel(array('belongsTo' => array('Co')));
    }
    
    // The views will also need this
    $this->set('pool_org_identities', $pool);
    
    parent::beforeFilter();
  }
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v1.1.0
   */
  
  public function beforeRender() {
    parent::beforeRender();
    
    // Get a pointer to our model names
    $req = $this->modelClass;
    $modelpl = Inflector::tableize($req);
    
    // Find the ID of our parent
    $oisid = -1;
    
    if(!empty($this->params->named['oisid'])) {
      $oisid = filter_var($this->params->named['oisid'],FILTER_SANITIZE_SPECIAL_CHARS);
    } elseif(!empty($this->viewVars[$modelpl][0][$req])) {
      $oisid = $this->viewVars[$modelpl][0][$req]['org_identity_source_id'];
    }
    
    $this->set('vv_oisid', $oisid);
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v1.1.0
   */
  
  public function performRedirect() {
    $target = array();
    $target['plugin'] = null;
    $target['controller'] = "org_identity_sources";
    $target['action'] = 'index';
    if(!$this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      $target['co'] = $this->cur_co['Co']['id'];
    }
    
    $this->redirect($target);
  }
}
