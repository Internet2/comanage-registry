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
  
  public $uses = array('Authenticator', 'CoPerson');
  
  /**
   * Add an Authenticator Model Object.
   * Primarily intended for Authenticators that support multiple values per instantiation.
   *
   * @since  COmanage Registry v3.1.0
   */
  
  public function add() {
    parent::add();
    
    $this->set('title_for_layout', _txt('op.add-a', array($this->viewVars['vv_authenticator']['Authenticator']['description'])));
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v3.1.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    // Force the model class to be that of our child model/controller.
    // (Otherwise StandardController will think we're Authenticator, since
    // that's first in $uses.)
    $this->modelClass = Inflector::singularize($this->name);
    
    parent::beforeFilter();
    
    // These are set by calculateParentPermissions, if that's called
    $this->setViewVars(!empty($this->request->params['named']['authenticatorid'])
                       ? $this->request->params['named']['authenticatorid']
                       : null,
                       !empty($this->request->params['named']['copersonid'])
                       ? $this->request->params['named']['copersonid']
                       : null);
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
    if(!empty($this->request->params['named']['authenticatorid'])) {
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
   * @param  Boolean $multiple Whether the calling plugin supports multiple authenticators per instantiation
   * @return Array Permissions array
   */
  
  protected function calculateParentPermissions($multiple=false) {
    $roles = $this->Role->calculateCMRoles();           // Who we authenticated as
    $pids = $this->parsePersonID($this->request->data); // Who we're asking for
    
    // Is this a record we can manage?
    $managed = false;
    $self = false;
    $locked = false;
    
    $authenticatorId = null;
    $coPersonId = null;
    
    if(!empty($this->request->params['pass'][0])) {
      // Look up the identifiers associated with this record
      
      $model = $this->modelClass;
      $coPersonId = $this->$model->field('co_person_id',
                                         array($this->modelClass.'.id' => $this->request->params['pass'][0]));
      
      $authmodel = $this->modelClass . "Authenticator";
      $modelAuthenticatorId = $this->$model->field(strtolower($this->modelClass) . '_authenticator_id',
                                                   array($this->modelClass.'.id' => $this->request->params['pass'][0]));
      
      if($modelAuthenticatorId) {
        $authenticatorId = $this->$model->$authmodel->field('authenticator_id',
                                                            array($authmodel.'.id' => $modelAuthenticatorId));
      }
    } else {
      if(!empty($this->request->params['named']['authenticatorid'])) {
        $authenticatorId = $this->request->params['named']['authenticatorid'];
      }
      
      if(!empty($this->request->params['named']['copersonid'])) {
        $coPersonId = $this->request->params['named']['copersonid'];
      }
    }
    
    // Set view vars and load plugins in case they're needed at various points
    $this->setViewVars($authenticatorId, $coPersonId);
    
    if(!empty($roles['copersonid']) && $coPersonId) {
      if($roles['copersonid'] == $coPersonId) {
        $self = true;
        
        if(!empty($authenticatorId)) {
          // See if the authenticator is locked
          
          $status = $this->Authenticator->status($authenticatorId, $roles['copersonid']);
          
          if($status['status'] == AuthenticatorStatusEnum::Locked) {
            $locked = true;
          }
        }
      }
      
      $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'], $coPersonId);
      
      // Set for use in the view
      if($self || $managed) {
        $this->set('vv_co_person_id', $coPersonId);
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
    
    if($multiple) {
      // Adjust permissions to required views
      
      $p['add'] = ($roles['cmadmin']
                   || $roles['coadmin']
                   || $managed
                   || ($self && !$locked));
      
      $p['edit'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || $managed
                    || ($self && !$locked));
      
      $p['delete'] = ($roles['cmadmin']
                      || $roles['coadmin']
                      || $managed
                      || ($self && !$locked));
      
      $p['index'] = ($roles['cmadmin']
                     || $roles['coadmin']
                     || $managed
                     || ($self && !$locked));
      
      $p['view'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || $managed
                    || ($self && !$locked));
    }
    
    return $p;
  }
  
  /**
   * Delete an Authenticator Model Object.
   * Primarily intended for Authenticators that support multiple values per instantiation.
   *
   * @since  COmanage Registry v3.1.0
   */
  
  function delete($id) {
    // Pull the CO Person ID, we'll need it for the redirect
    $model = $this->modelClass;
    $coPersonId = $this->$model->field('co_person_id', array($this->modelClass.'.id' => $id));
    
    $this->setViewVars(null, $coPersonId);
    
    parent::delete($id);
  }
  
  /**
   * Edit an Authenticator Model Object.
   * Primarily intended for Authenticators that support multiple values per instantiation.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $id Authenticator Model Object ID
   */
  
  function edit($id) {
    parent::edit($id);
    
    // If we're rendering a form, we won't have the Authenticator ID or CO Person ID
    // until after parent::edit() runs, so we have to do similar work to beforeFilter(), here.
    
    $modelpl = strtolower($this->name);
    $authmodel = $this->modelClass . 'Authenticator';
    
    $this->setViewVars(!empty($this->viewVars[$modelpl][0][$authmodel]['authenticator_id'])
                       ? $this->viewVars[$modelpl][0][$authmodel]['authenticator_id']
                       : null,
                       !empty($this->viewVars[$modelpl][0][$this->modelClass]['co_person_id'])
                       ? $this->viewVars[$modelpl][0][$this->modelClass]['co_person_id']
                       : null);
  }
  
  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v3.1.0
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */

  public function generateHistory($action, $newdata, $olddata) {
    $req = $this->modelClass;
    $model = $this->$req;
    $modelpl = Inflector::tableize($req);
    $authmodel = $req . "Authenticator";
    $authcfg = $this->$req->$authmodel->getConfig();
    
    // Build a change string
    $cstr = "";

    switch($action) {
      case 'add':
        $cstr = _txt('rs.added-a2', array($authcfg['Authenticator']['description'],
                                          $newdata['Certificate']['description']));
        break;
      case 'delete':
        $cstr = _txt('rs.deleted-a2', array($authcfg['Authenticator']['description'],
                                            $olddata['Certificate']['description']));
        break;
      case 'edit':
        $cstr = _txt('rs.edited-a2', array($authcfg['Authenticator']['description'],
                                           $newdata['Certificate']['description']));
        break;
    }
    
    switch($action) {
      case 'add':
      case 'edit':
        $model->CoPerson->HistoryRecord->record($newdata[$req]['co_person_id'],
                                                null,
                                                null,
                                                $this->Session->read('Auth.User.co_person_id'),
                                                ActionEnum::AuthenticatorEdited,
                                                $cstr);
        break;
      case 'delete':
        $model->CoPerson->HistoryRecord->record($olddata[$req]['co_person_id'],
                                                null,
                                                null,
                                                $this->Session->read('Auth.User.co_person_id'),
                                                ActionEnum::AuthenticatorDeleted,
                                                $cstr);
        break;
    }

    return true;
  }
  
  /**
   * Obtain an index of Authenticator Model Objects (for a given authenticator and CO Person).
   * Primarily intended for Authenticators that support multiple values per instantiation.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $id Authenticator Model Object ID
   */
  
  public function index() {
    parent::index();
    
    $this->set('title_for_layout', $this->viewVars['vv_authenticator']['Authenticator']['description']);
  }
  
  /**
   * Obtain the current status information for an Authenticator.
   *
   * @since  COmanage Registry v3.1.0
   */
  
  public function info() {
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
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v3.1.0
   * @return Array An array suitable for use in $this->paginate
   * @throws InvalidArgumentException
   */

  function paginationConditions() {
    // Only retrieve records for the specified Authenticator and CO Person.
    // This is really for Authenticators where $multiple is true, since we
    // won't call index() for others.
    
    $ret = array();
    
    if(!empty($this->viewVars['vv_authenticator']['Authenticator']['plugin'])) {
      $plugin = $this->viewVars['vv_authenticator']['Authenticator']['plugin'];
      $key = Inflector::underscore($plugin) . "_id";
      
      if(!empty($this->viewVars['vv_authenticator'][$plugin]['id'])) {
        $ret['conditions'][$this->modelClass . '.' . $key] = $this->viewVars['vv_authenticator'][$plugin]['id'];
      }
      
      if(!empty($this->viewVars['vv_co_person']['CoPerson']['id'])) {
        $ret['conditions'][$this->modelClass . '.co_person_id'] = $this->viewVars['vv_co_person']['CoPerson']['id'];
        
        return $ret;
      }
    }
    
    throw new InvalidArgumentException(_txt('er.notprov'));
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.1.0
   */
  
  public function performRedirect() {
    $copersonid = null;
    
    if(!empty($this->viewVars['vv_co_person']['CoPerson']['id'])) {
      $copersonid = $this->viewVars['vv_co_person']['CoPerson']['id'];
    } else {
      // Probably completed an edit() call
      $modelpl = strtolower($this->name);
      $authmodel = $this->modelClass . 'Authenticator';
    
      if(!empty($this->viewVars[$modelpl][0][$this->modelClass]['co_person_id'])) {
        $copersonid = $this->viewVars[$modelpl][0][$this->modelClass]['co_person_id'];
      }
    }

    $target = array();
    $target['plugin'] = null;
    $target['controller'] = "authenticators";
    $target['action'] = 'status';
    $target['copersonid'] = $copersonid;
    
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
  
  /**
   * Set view vars for Authenticator plugin views.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $authenticatorId Authenticator ID (or null)
   * @param  integer $coPersonId      CO Person ID (or null)
   */
  
  protected function setViewVars($authenticatorId, $coPersonId) {
    // We might be called multiple times, since calculateParentPermissions will call us,
    // but not all plugins will use that.
    
    if(!isset($this->viewVars['vv_authenticator'])) {
      if($authenticatorId) {
        // Pull the Plugin from the Authenticator.
        
        $plugin = $this->Authenticator->field('plugin', array('id' => $authenticatorId));
        
        if(empty($plugin)) {
          throw new InvalidArgumentException(_txt('er.notfound', array('ct.authenticators.1',
                                                                       filter_var($authenticatorId,FILTER_SANITIZE_SPECIAL_CHARS))));
        }
        
        $this->Authenticator->bindModel(array('hasOne' => array($plugin.".".$plugin => array('dependent' => true))));
        
        // While we're here, load the plugin information and set in the view.
        
        $args = array();
        $args['conditions']['Authenticator.id'] = $authenticatorId;
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
    
    if(!isset($this->viewVars['vv_co_person'])) {
      if($coPersonId) {
        // Pull info about the CO Person for the views
        
        $args = array();
        $args['conditions']['CoPerson.id'] = $coPersonId;
        $args['contain'][] = 'PrimaryName';
        
        $this->set('vv_co_person', $this->CoPerson->find('first', $args));
      }
    }
  }
}
