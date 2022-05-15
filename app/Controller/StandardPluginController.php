<?php
/**
 * COmanage Registry Standard Plugin Controller
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
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

// In spite of its name, this is not yet a Standard Controller -- it is only
// used by Vetter Plugins. However, the idea is that all S*Controller interfaces
// will move to StandardPluginController as part of the PE migration. Then, it
// might make sense to use StandardPluginController as a switch engine, with
// plugin-type specific backends invoked as needed (but not have the plugin
// author worry about the right magic incantations).

class StandardPluginController extends StandardController {
  // Plugins need a CO to be set
  public $requires_co = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: requires_co possibly set
   *
   * @since  COmanage Registry v4.1.0
   */

  function beforeFilter() {
    parent::beforeFilter();
    
    // Get a pointer to our model name
    $req = $this->modelClass;
    $model = $this->$req;
    
    // Dynamically adjust validation rules to include the current CO ID for dynamic types.
    
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
   * Determine the CO ID based on some attribute of the request.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Array $data Array of data for parsing Person ID
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    $req = $this->modelClass;
    $model = $this->$req;
    
    if($model->isPlugin('vetter')) {
      // We accept a result ID in the review action query parameters
      if($this->action == 'review' && !empty($this->request->params['named']['vettingresult'])) {
        // This might throw InvalidArgumentException
        return $model->VettingStep->VettingResult->findCoForRecord($this->request->params['named']['vettingresult']);
      }
    }
    
    return parent::calculateImpliedCoId($data);
  }
  
  /**
   * Calculate Vetter permissions, for use in isAuthorized().
   *
   * @since  COmanage Registry v4.1.0
   * @return array    Array of permissions suitable for isAuthorized()
   */
  
  protected function calculateVetterPermissions() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    $vetterForRequest = false;
    
    if(!empty($this->request->params['named']['vettingresult'])) {
      // Check the result via the VettingRequest
      $args = array();
      $args['conditions']['VettingResult.id'] = $this->request->params['named']['vettingresult'];
      $args['contain'] = array('VettingStep');
      
      $this->loadModel('VettingResult');
      
      $vettingResult = $this->VettingResult->find('first', $args);
      
      if(!empty($roles['copersonid'])) {
        $vetterGroups = $this->Role->vetterForGroups($roles['copersonid']);
        
        if(!empty($vettingResult['VettingStep']['vetter_co_group_id'])) {
          $vetterForRequest = $this->VettingResult->VetterCoPerson->CoGroupMember->isMember($vettingResult['VettingStep']['vetter_co_group_id'], $roles['copersonid']);
        }
      }
    }
    
    // Delete an existing Visual Compliance Vetter?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Visual Compliance Vetter?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Visual Compliance Vetters?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Review a pending Visual Compliance request?
    $p['review'] = ($roles['cmadmin'] || $roles['coadmin'] || $vetterForRequest);
    
    // View an existing Visual Compliance?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    return $p;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.1.0
   */
  
  public function performRedirect() {
    $req = $this->modelClass;
    $model = $this->$req;
    
    $target = array();
    
    if($model->isPlugin('vetter')) {
      $target['plugin'] = null;
      $target['controller'] = "vetting_steps";
      $target['action'] = 'index';
      $target['co'] = $this->cur_co['Co']['id'];
    }
      
    $this->redirect($target);
  }
  
  /**
   * Review the status of a Vetting Step.
   *
   * @since  COmanage Registry v4.1.0
   */

  public function review() {
    // eg: TestVetter
    $req = $this->modelClass;
    $model = $this->$req;
    // eg: test_vetter
    $umodel = Inflector::underscore($req);
    
    if($model->isPlugin('vetter')) {
      if(!empty($this->request->params['named']['vettingresult'])) {
        // Pull the vetting info for the view
        
        $args = array();
        $args['conditions']['VettingResult.id'] = $this->request->params['named']['vettingresult'];
        $args['contain'] = array(
          'VettingRequest' => array(
            'CoPerson' => array(
              'Name'
            )
          ),
          'VettingStep',
          'VetterCoPerson' => array('PrimaryName')
        );
        
        $result = $model->VettingStep->VettingResult->find('first', $args);
        
        $this->set('vv_result', $result);
        
        if(!empty($result['VettingStep']['id'])) {
          $args = array();
          $args['conditions'][$req.'.vetting_step_id'] = $result['VettingStep']['id'];
          $args['contain'] = false;
          
          $this->set($umodel, $model->find('first', $args));
        }
      }
    }
  }
}
