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
  
  // Confusingly, we only want this for the REST API
  public $requires_person = false;
  
  public $uses = array('Authenticator',
                       'CoEnrollmentAuthenticator',
                       'CoEnrollmentFlow',
                       'CoPerson',
                       'CoPetition');
  
  /**
   * Add an Authenticator Model Object.
   * Primarily intended for Authenticators that support multiple values per instantiation.
   *
   * @since  COmanage Registry v3.1.0
   */
  
  public function add() {
    parent::add();
    
    if(!$this->request->is('restful')) {
      $this->set('title_for_layout', _txt('op.add-a', array($this->viewVars['vv_authenticator']['Authenticator']['description'])));
    }
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
    
    if($this->request->is('restful')) {
      $this->requires_person = true;
    }
    
    if(($this->action == 'add' || $this->action == 'manage')
       && !empty($this->request->params['named']['authenticatorid'])
       && !empty($this->request->params['named']['copetitionid'])) {
      $args = array();
      $args['conditions']['CoPetition.id'] = $this->request->params['named']['copetitionid'];
      $args['contain'] = array('CoEnrollmentFlow' => 'CoEnrollmentAuthenticator');
      
      $pt = $this->CoPetition->find('first', $args);
      
      // Make sure the requested authenticator ID is attached to the enrollment
      // flow attached to the petition
      
      $ea = Hash::extract($pt, "CoEnrollmentFlow.CoEnrollmentAuthenticator.{n}[authenticator_id=".$this->request->params['named']['authenticatorid']."]");
      
      if(empty($ea) || $ea[0]['required'] == RequiredEnum::NotPermitted) {
        throw new InvalidArgumentException(_txt('er.permission'));
      }
      
      // If we're in an enrollment flow, we may need to check the petition token for authorization.
      // We'll do this when all of the following are true:
      // - The action is "add" or "manage"
      // - A copetitionid is provided, and maps to an Enrollment Flow where
      //   - the petition is in a valid state (created or confirmed)
      //   - the enrollment flow is active and does NOT require authentication
      // - A token is provided, and matches the enrollee token in the petition
      
      if(!empty($pt['CoPetition'])
         && !empty($pt['CoEnrollmentFlow'])
         && !empty($this->request->params['named']['token'])
         && ($pt['CoPetition']['status'] == PetitionStatusEnum::Created
             || $pt['CoPetition']['status'] == PetitionStatusEnum::Confirmed)
         && !empty($pt['CoPetition']['enrollee_co_person_id'])
         && $pt['CoEnrollmentFlow']['status'] == SuspendableStatusEnum::Active
         && !$pt['CoEnrollmentFlow']['require_authn']
         && $this->request->params['named']['token'] === $pt['CoPetition']['enrollee_token']) {
        
        $this->Auth->allow($this->action);
        
        // Load the Authenticator plugin
        $this->setViewVars($this->request->params['named']['authenticatorid'], $pt['CoPetition']['enrollee_co_person_id']);
        
        // Set permissions for views
        $this->set('permissions', array($this->action => true));
      }
    }
    
    parent::beforeFilter();
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
    $request_data = $this->Api->getData();

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
      
      $authfield = $this->request->plugin . "_id";
      $authmodel = Inflector::classify($this->request->plugin);
      
      $modelAuthenticatorId = $this->$model->field($authfield,
                                                   array($this->modelClass.'.id' => $this->request->params['pass'][0]));
      
      if($modelAuthenticatorId) {
        $authenticatorId = $this->$model->$authmodel->field('authenticator_id',
                                                            array($authmodel.'.id' => $modelAuthenticatorId));
      }
    } elseif (!empty($request_data)) {
      $model = $this->modelClass;
      $authfield = $this->request->plugin . "_id";
      $authmodel = Inflector::classify($this->request->plugin);

      if(!empty($request_data[$authfield])) {
        $authenticatorId = $this->$model->$authmodel->field('authenticator_id',
                                                            array($authmodel.'.id' => $request_data[$authfield]));
      }

      if(!empty($request_data['co_person_id'])) {
        $coPersonId = $request_data['co_person_id'];
      }

    } else {
      if(!empty($this->request->params['named']['authenticatorid'])) {
        $authenticatorId = $this->request->params['named']['authenticatorid'];
      }
      
      if(!empty($this->request->params['named']['copersonid'])) {
        $coPersonId = $this->request->params['named']['copersonid'];
      }
    }
    
    // We mostly set view vars at other points, but we need them for
    // Set view vars and load plugins in case they're needed at various points
    $this->setViewVars($authenticatorId, $coPersonId);
    
    if(!empty($roles['copersonid']) && $coPersonId) {
      if($roles['copersonid'] == $coPersonId) {
        $self = true;
        
        if(!empty($authenticatorId)) {
          // See if the authenticator is locked. For this to work we need the plugin
          // models to be loaded, so we trigger setViewVars as a hack to do so.
          
          $this->setViewVars($authenticatorId, $coPersonId);
          
          $status = $this->Authenticator->status($authenticatorId, $roles['copersonid']);
          
          if($status['status'] == AuthenticatorStatusEnum::Locked) {
            $locked = true;
          }
        }
      }
      
      $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'], $coPersonId);
    } else {
      if(!empty($this->request->params['named']['copetitionid'])
         && !empty($this->request->params['named']['token'])) {
        // We're in an enrollment flow and are establishing authenticators.
        // Check for and validate the token.
        
        try {
          // Authenticators can only be set when the petition is in "Confirmed" status,
          // ie: after the enrollee has confirmed email.
          $tokenRole = $this->CoPetition->validateToken($this->request->params['named']['copetitionid'],
                                                        $this->request->params['named']['token'],
                                                        PetitionStatusEnum::Confirmed);
          
          if($tokenRole == 'enrollee') {
            $self = true;

            // If we're in an enrollment flow, pull the enrollment authenticator configuration.
            // We do this here rather than beforeFilter (where it should more logically go)
            // since we've already verified the token status and that copetitionid is valid.
            // We'll also grab the CO Person.
            
            $args = array();
            $args['conditions']['CoPetition.id'] = $this->request->params['named']['copetitionid'];
            $args['contain'] = false;
            
            $pt = $this->CoPetition->find('first', $args);
            
            // We shouldn't get here if $pt is empty since we just verified the token
            $coPersonId = $pt['CoPetition']['enrollee_co_person_id'];
            
            $efId = $pt['CoPetition']['co_enrollment_flow_id'];
            
            if($efId) {
              $args = array();
              $args['conditions']['CoEnrollmentAuthenticator.co_enrollment_flow_id'] = $efId;
              $args['conditions']['CoEnrollmentAuthenticator.authenticator_id'] = $authenticatorId;
              $args['contain'] = false;
              
              $eAuthenticator = $this->CoEnrollmentAuthenticator->find('first', $args);
              
              if(empty($eAuthenticator)
                 || $eAuthenticator['CoEnrollmentAuthenticator']['required'] == RequiredEnum::NotPermitted) {
                throw new InvalidArgumentException(_txt('er.setting'));
              }
              
              $this->set('vv_co_enrollment_authenticator', $eAuthenticator);
            }
          }
        }
        catch(Exception $e) {
          // We don't really need to know why the token failed, we just won't set any permissions
        }
      }
      
      // Set view vars and load plugins in case they're needed at various points
      $this->setViewVars($authenticatorId, $coPersonId);
      
      // Set for use in the view
      if($self || $managed) {
        $this->set('vv_co_person_id', $coPersonId);
      }
    }
    
    $p = array();
    
    if($this->request->is('restful')) {
      // If the plugin supports RESTful calls, we need to calculate a different
      // set of permissions.
      
      $p['add'] = (!$locked && ($roles['cmadmin'] || $roles['coadmin']));
      
      $p['edit'] = (!$locked && ($roles['cmadmin'] || $roles['coadmin']));
      
      $p['delete'] = (!$locked && ($roles['cmadmin'] || $roles['coadmin']));
      
      $p['index'] = (!$locked && ($roles['cmadmin'] || $roles['coadmin']));
      
      $p['view'] = (!$locked && ($roles['cmadmin'] || $roles['coadmin']));
    } else {
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
                      || ($self && !$locked)
                      // If there is no CO Person specified, manage() will
                      // issue a redirect
                      || (!$coPersonId && !empty($this->Session->read('Auth.User.co_person_id'))));
      
      // Reset a given CO Person's Authenticators?
      // Corresponds to AuthenticatorsController
      // Unclear if this should be self service, so for now it isn't
      // Note PasswordAuthenticator implements its own self service reset at /ssr
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
    $authcfg = $this->viewVars['vv_authenticator'];

    $actorCoPersonId = $this->request->is('restful') ? null : $this->Session->read('Auth.User.co_person_id');
    $actorApiUserId = $this->request->is('restful') ? $this->Auth->User('id') : null;

    // Build a change string
    $cstr = "";

    switch($action) {
      case 'add':
        $cstr = _txt('rs.added-a2', array($authcfg['Authenticator']['description'],
                                          $newdata[$req][$model->displayField]));
        break;
      case 'delete':
        $cstr = _txt('rs.deleted-a2', array($authcfg['Authenticator']['description'],
                                            $olddata[$req][$model->displayField]));
        break;
      case 'edit':
        $cstr = _txt('rs.edited-a2', array($authcfg['Authenticator']['description'],
                                           $newdata[$req][$model->displayField]));
        break;
    }
    
    switch($action) {
      case 'add':
      case 'edit':
        $model->CoPerson->HistoryRecord->record($newdata[$req]['co_person_id'],
                                                null,
                                                null,
                                                $actorCoPersonId,
                                                ActionEnum::AuthenticatorEdited,
                                                $cstr,
                                                null, null, null,
                                                $actorApiUserId);
        break;
      case 'delete':
        $model->CoPerson->HistoryRecord->record($olddata[$req]['co_person_id'],
                                                null,
                                                null,
                                                $actorCoPersonId,
                                                ActionEnum::AuthenticatorDeleted,
                                                $cstr,
                                                null, null, null,
                                                $actorApiUserId);
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
    // Get a pointer to our model
    $req = $this->modelClass;
    $model = $this->$req;
    $modelpl = Inflector::tableize($req);
    $modelid = $this->modelKey . "_id";
    $parentkey = $this->modelKey . "_authenticator_id";
    $parentflag = str_replace("_", "", $this->modelKey) . "authid";
    
    if($this->request->is('restful')) {
      if(!empty($this->params['url'][$parentflag])) {
        // Filter the request on the object authenticator ID (ie: password_authenticator_id)
        $args = array();
        $args['conditions'][$model->name . '.' . $parentkey] = $this->params['url'][$parentflag];
        if(!empty($this->params['url']['copersonid'])) {
          $args['conditions'][$model->name . '.co_person_id'] = $this->params['url']['copersonid'];
        }
        $args['contain'] = false;
        
        $t = $model->find('all', $args);
        
        if(empty($t)) {
          // XXX Note the is slightly inconsistent with API v1 behavior, but probablyy not v2
          $this->Api->restResultHeader(404, "Not Found");
          return;
        }
        
        $this->set($modelpl, $this->Api->convertRestResponse($t));
        $this->Api->restResultHeader(200, "OK");
        return;
      } else {
        // We want the default behavior
        parent::index();
      }
    } else {
      parent::index();
      
      $this->set('title_for_layout', $this->viewVars['vv_authenticator']['Authenticator']['description']);
    }
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
    
    if(empty($this->viewVars['vv_co_person']['CoPerson']['id'])
       && !isset($this->request->params['named']['copetitionid'])) {
      // If we don't have a CO Person ID, figure out who we're authenticated
      // as and redirect to that user (unless we're in a petition).
      
      $coPersonId = $this->Session->read('Auth.User.co_person_id');
      
      if(!empty($coPersonId)) {
        $this->redirect(array(
          'authenticatorid' => $this->viewVars['vv_authenticator']['Authenticator']['id'],
          'copersonid'      => $coPersonId
        ));
      } else {
        throw new RuntimeException(_txt('er.notprov.id', array(_txt('ct.co_people.1'))));
      }
    }
    
    // Pull current data, if any
    $this->set('vv_current',
               $this->Authenticator->$plugin->current($this->viewVars['vv_authenticator']['Authenticator']['id'],
                                                      $this->viewVars['vv_authenticator'][$plugin]['id'],
                                                      $this->viewVars['vv_co_person']['CoPerson']['id']));
    
    // If we're in an enrollment flow and the enrollment authenticator is optional,
    // provide a "skip" button.
    
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
        
        if(!isset($this->request->params['named']['copetitionid'])) {
          $this->Authenticator->provision($this->viewVars['vv_co_person']['CoPerson']['id']);
          
          if(!empty($this->viewVars['vv_co_person']['CoPerson']['id'])) {
            // Trigger change notification, if configured
            $this->Authenticator->$plugin->notify($this->viewVars['vv_co_person']['CoPerson']['id']);
          }
        }
        
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
    // Annoyingly, some old code in StandardController calls checkPersonID
    // instead of performRedirect() on delete, so this doesn't get triggered.
    // This should get fixed with Cake 4.
    
    if(!empty($this->request->params['named']['onFinish'])) {
      $this->redirect(urldecode($this->request->params['named']['onFinish']));
    }
    
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
          throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.authenticators.1'),
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
