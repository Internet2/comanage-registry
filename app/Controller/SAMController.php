<?php
/**
 * COmanage Registry Standard Authenticator Model (SAM) Controller
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class SAMController extends StandardController {
  public $requires_co = true;
  
  public $uses = array('Authenticator');
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v3.1.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    if(!empty($this->request->params['named']['authenticatorid'])) {
      // Pull the Plugin from the Authenticator.
      
      $plugin = $this->Authenticator->field('plugin',
                                            array('id' => $this->request->params['named']['authenticatorid']));
      
      if(empty($plugin)) {
        throw new InvalidArgumentException(_txt('er.notfound', array('ct.authenticators.1',
                                                                     filter_var($this->request->params['named']['authenticatorid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
      
      $this->Authenticator->bindModel(array('hasOne' => array($plugin.".".$plugin => array('dependent' => true))));
      
      // While we're here, load the plugin information and set in the view.
      
      $args = array();
      $args['conditions']['Authenticator.id'] = $this->request->params['named']['authenticatorid'];
      $args['contain'][] = $plugin;
      
      $pluginCfg = $this->Authenticator->find('first', $args);
      
      if(!isset($pluginCfg['Authenticator']['status'])
         || $pluginCfg['Authenticator']['status'] != SuspendableStatusEnum::Active) {
        throw new InvalidArgumentException(_txt('er.perm.status',
                                                array(_txt('en.status.susp', null, $pluginCfg['Authenticator']['status']))));
      }
        
      $this->set('vv_authenticator', $pluginCfg);
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v3.1.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    if(($this->action == "info"
        || $this->action == "manage"
        || $this->action == "reset")
       && !empty($this->request->params['named']['authenticatorid'])) {
      // Pull the CO from the Authenticator

      $coId = $this->Authenticator->field('co_id',
                                          array('id' => $this->request->params['named']['authenticatorid']));

      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.authenticators.1'),
                                                      filter_var($this->request->params['named']['authenticatorid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }

    return parent::calculateImpliedCoId();
  }
  
  /**
   * Calculate permissions for actions provided by SAMController on behalf of "simple"
   * Authenticator plugins (ie: "info", "manage", "reset").
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Permissions array
   */
  
  protected function calculateParentPermissions() {
    $roles = $this->Role->calculateCMRoles();           // Who we authenticated as
    $pids = $this->parsePersonID($this->request->data); // Who we're asking for
    
    // Is this a record we can manage?
    $managed = false;
    $self = false;
    $locked = false;
    
    if(!empty($roles['copersonid'])
       && !empty($this->request->params['named']['copersonid'])) {
      if($roles['copersonid'] == $this->request->params['named']['copersonid']) {
        $self = true;
        
        if(!empty($this->request->params['named']['authenticatorid'])) {
          // See if the authenticator is locked
          
          $status = $this->Authenticator->status($this->request->params['named']['authenticatorid'],
                                                 $roles['copersonid']);
          
          if($status['status'] == AuthenticatorStatusEnum::Locked) {
            $locked = true;
          }
        }
      }
      
      $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                        $this->request->params['named']['copersonid']);
      
      // Set for use in the view
      if($self || $managed) {
        $this->set('vv_co_person_id', $this->request->params['named']['copersonid']);
      }
    }
    
    $p = array();
    
    // View a given CO Person's Authenticator?
    // Corresponds to AuthenticatorsController
    $p['info'] = ($roles['cmadmin']
                  || $roles['coadmin']
                  || $managed
                  || ($self && !$locked));
    
    // Manage a given CO Person's Authenticators?
    // Corresponds to AuthenticatorsController
    $p['manage'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || $managed
                    || ($self && !$locked));
    
    // Reset a given CO Person's Authenticators?
    // Corresponds to AuthenticatorsController
    // Unclear if this should be self service, so for now it isn't
    $p['reset'] = ($roles['cmadmin']
                   || $roles['coadmin']
                   || $managed);
        
    return $p;
  }
  
  /**
   * Obtain the current status information for an Authenticator.
   *
   * @since  COmanage Registry v3.1.0
   */
  
  public function info() {
    $args = array();
    $args['conditions']['CoPerson.id'] = $this->request->params['named']['copersonid'];
    $args['contain'][] = 'PrimaryName';
    
    $this->set('vv_co_person', $this->Authenticator->Co->CoPerson->find('first', $args));
    
    $plugin = $this->viewVars['vv_authenticator']['Authenticator']['plugin'];
    $this->Authenticator->$plugin->setConfig($this->viewVars['vv_authenticator']);        
    
    $this->set('vv_status', $this->Authenticator->$plugin->status($this->request->params['named']['copersonid']));
    
    $this->set('title_for_layout', $this->viewVars['vv_authenticator']['Authenticator']['description']);
  }
  
  /**
   * Default implementation to manage an Authenticator, for "Simple" plugins.
   *
   * @since  COmanage Registry v3.1.0
   */
  
  public function manage() {
    $this->set('title_for_layout', _txt('op.manage-a', array($this->viewVars['vv_authenticator']['Authenticator']['description'])));
    
    $plugin = $this->viewVars['vv_authenticator']['Authenticator']['plugin'];
    $this->Authenticator->$plugin->setConfig($this->viewVars['vv_authenticator']);        
    
    // Pull current data, if any
      
    $this->set('vv_current',
               $this->Authenticator->$plugin->current($this->viewVars['vv_authenticator']['Authenticator']['id'],
                                                      $this->viewVars['vv_authenticator'][$plugin]['id'],
                                                      $this->request->params['named']['copersonid']));
    
    if($this->request->is('get')) {
      // Just let the form render
    } else {
      // Hand off to the plugin to handle the save.
      // Note we don't do anything to the data received from the plugin's form
      // other than let the normal Cake routines (validation, callbacks) run.
            
      try {
        // We pass the data to the main Plugin model rather than to the actual
        // authenticator backend for a couple of reasons, though this might change in the future.
        // (1) The authenticator will need the configuration information to work with
        // (2) It's one less interface (parent model) to define
        
        $msg = $this->Authenticator->$plugin->manage($this->request->data,
                                                     $this->Session->read('Auth.User.co_person_id'));
        
        $this->Authenticator->provision($this->request->params['named']['copersonid']);
                                                     
        $this->Flash->set($msg, array('key' => 'success'));
        $this->performRedirect();
      }
      catch(Exception $e) {
        $this->Flash->set(filter_var($e->getMessage(),FILTER_SANITIZE_SPECIAL_CHARS), array('key' => 'error'));
      }
    }
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.1.0
   */
  
  public function performRedirect() {
    $target = array();
    $target['plugin'] = null;
    $target['controller'] = "authenticators";
    $target['action'] = 'status';
    $target['copersonid'] = $this->request->params['named']['copersonid'];
    
    $this->redirect($target);
  }
  
  /**
   * Default implementation to reset an Authenticator, for "Simple" plugins.
   *
   * @since  COmanage Registry v3.1.0
   */
  
  public function reset() {
    $plugin = $this->viewVars['vv_authenticator']['Authenticator']['plugin'];
    $this->Authenticator->$plugin->setConfig($this->viewVars['vv_authenticator']);        
    
    try {
      $this->Authenticator->$plugin->reset($this->request->params['named']['copersonid'],
                                           $this->Session->read('Auth.User.co_person_id'));
      
      $this->Authenticator->provision($this->request->params['named']['copersonid']);
      
      $this->Flash->set(_txt('rs.authr.reset',
                             array($this->viewVars['vv_authenticator']['Authenticator']['description'])),
                        array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set(filter_var($e->getMessage(),FILTER_SANITIZE_SPECIAL_CHARS), array('key' => 'error'));
    }
    
    // No view, always redirect
    $this->performRedirect();
  }
}
