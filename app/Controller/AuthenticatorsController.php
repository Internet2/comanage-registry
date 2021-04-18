<?php
/**
 * COmanage Registry Authenticators Controller
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

App::uses("SAuthController", "Controller");

class AuthenticatorsController extends SAuthController {
  // Class name, used by Cake
  public $name = "Authenticators";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'description' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // Used by AppController::calculateImpliedCoId
  public $impliedCoIdActions = array(
    'status' => 'copersonid'
  );
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v3.1.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    $plugins = $this->loadAvailablePlugins('authenticator');
    
    // Bind the models so Cake can magically pull associated data. Note this will
    // create associations with *all* authenticator plugins, not just the one that
    // is actually associated with this Authenticator. Given that most installations
    // will only have a handful of authenticators, that seems OK (vs parsing the request
    // data to figure out which type of Plugin we should bind).
    
    foreach(array_values($plugins) as $plugin) {
      $this->Authenticator->bindModel(array('hasOne' => array($plugin => array('dependent' => true))), false);
    }
    
    $this->set('plugins', $plugins);
    
    // Provide a list of message templates
    $args = array();
    $args['conditions']['co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['status'] = SuspendableStatusEnum::Active;
    $args['conditions']['context'] = array(
      MessageTemplateEnum::Authenticator
    );
    $args['fields'] = array(
      'CoMessageTemplate.id',
      'CoMessageTemplate.description',
      'CoMessageTemplate.context'
    );

    $this->set('vv_message_templates',
               $this->Authenticator->CoMessageTemplate->find('list', $args));
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    // Annoyingly, the read() call in standardController resets the associations made
    // by the bindModel() call in beforeFilter(), above. Beyond that, deep down in
    // Cake's Model, a find() is called as part of the delete() which also resets the associations.
    // So we have to manually delete any dependencies.
    
    // While this conceptually belongs in Authenticator::beforeDelete, that doesn't
    // have access to the list of plugins or $curdata, so it's easier to do here.
    
    // Use the previously obtained list of plugins as a guide
    $plugins = $this->viewVars['plugins'];
    
    foreach(array_values($plugins) as $plugin) {
      $model = $plugin;
      
      if(!empty($curdata[$model]['id'])) {
        $this->$model->delete($curdata[$model]['id']);
      }
    }
    
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();           // Who we authenticated as
    $pids = $this->parsePersonID($this->request->data); // Who we're asking for
    
    // Is this a record we can manage?
    $managed = false;
    $self = false;
    
    if(!empty($roles['copersonid'])
       && !empty($this->request->params['named']['copersonid'])) {
      if($roles['copersonid'] == $this->request->params['named']['copersonid']) {
        $self = true;
      }
      
      $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                        $this->request->params['named']['copersonid']);
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform.
    
    // Note these permissions are applied to either a given Authenticator configuration
    // or to an individual's Authenticator, as appropriate for the specified action.
    
    // Add a new Authenticator?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Authenticator?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Authenticator?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Authenticators?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View a given CO Person's Authenticator?
    // Corresponds to SAMController
    $p['info'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || $managed
                    || $self);
    
    // Lock a given CO Person's Authenticators?
    $p['lock'] = ($roles['cmadmin']
                  || $roles['coadmin']
                  || $managed);
    
    // Set a given CO Person's Authenticators?
    // Corresponds to SAMController
    $p['manage'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || $managed
                    || $self);
    
    // Reset a given CO Person's Authenticators?
    // Corresponds to SAMController
    // Unclear if this should be self service, so for now it isn't
    $p['reset'] = ($roles['cmadmin']
                   || $roles['coadmin']
                   || $managed);
    
    // View a given CO Person's Authenticators?
    $p['status'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || $managed
                    || $self);
    
    // Unlock a given CO Person's Authenticators?
    $p['unlock'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || $managed);
    
    // View an existing Authenticator?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Lock an Authenticator for a CO Person.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $id Authenticator ID
   */
  
  public function lock($id) {
    if(!empty($this->request->params['named']['copersonid'])) {
      try {
        // Perform the lock
        $this->Authenticator->lock($id,
                                   $this->request->params['named']['copersonid'],
                                   $this->Session->read('Auth.User.co_person_id'));
        
        $this->Flash->set(_txt('rs.updated-a3', array(_txt('fd.status'))), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    } else {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('ct.co_people.1'))),
                        array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.1.0
   */
  
  function performRedirect() {
    if($this->action == 'add' && !empty($this->request->data['Authenticator']['plugin'])) {
      // Redirect to the appropriate plugin to set up whatever it wants
      
      $pluginName = filter_var($this->request->data['Authenticator']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
      $modelName = $pluginName;
      
      $target = array();
      $target['plugin'] = Inflector::underscore($pluginName);
      $target['controller'] = Inflector::tableize($modelName);
      $target['action'] = 'edit';
      $target[] = $this->Authenticator->_targetid;
      
      $this->redirect($target);
    } elseif(!empty($this->request->params['named']['copersonid'])) {
      // Redirect to the CO Person's authenticator status page
      
      $target = array();
      $target['controller'] = 'authenticators';
      $target['action'] = 'status';
      $target['copersonid'] = filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS);
      
      $this->redirect($target);
    } else {
      parent::performRedirect();
    }
  }
  
  /**
   * Reset an Authenticator for a CO Person.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $id Authenticator ID
   */
  
  public function reset($id) {
    if(!empty($this->request->params['named']['copersonid'])) {
      try {
        // Perform the reset
        $this->Authenticator->reset($id,
                                    $this->request->params['named']['copersonid'],
                                    $this->Session->read('Auth.User.co_person_id'));
        
        $this->Flash->set(_txt('rs.authr.reset'), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    } else {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('ct.co_people.1'))),
                        array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
  
  /**
   * Obtain the status of an Authenticator for a CO Person.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $id Authenticator ID
   */
  
  public function status() {
    $status = array();
    
    if(!empty($this->request->params['named']['copersonid'])) {
      // Pull the list of configured authenticators
      
      $args = array();
      $args['conditions']['Authenticator.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['Authenticator.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = false;
      // Pull the related models so we have their instantiated model ID
      $args['contain'] = array_keys($this->viewVars['plugins']);
      
      $authenticators = $this->Authenticator->find('all', $args);
      
      foreach($authenticators as $a) {
        // Only populate the necessary information for the view
        
        $st = array(
          'id'          => $a['Authenticator']['id'],
          'description' => $a['Authenticator']['description'],
          'plugin'      => $a['Authenticator']['plugin']
        );
        
        if(!empty($a[ $a['Authenticator']['plugin'] ]['id'])) {
          $st['plugin_id'] = $a[ $a['Authenticator']['plugin'] ]['id'];
        }
        
        // Does this plugin support multiple authenticators per instantiation?
        $plugin = $a['Authenticator']['plugin'];
        $st['multiple'] = $this->$plugin->multiple;
        
        // Pull the Authenticator status
        $st['status'] = $this->Authenticator->status($a['Authenticator']['id'],
                                                     $this->request->params['named']['copersonid']);
        
        $status[] = $st;
      }
      
      // Pull CO Person and name for breadcrumbs, etc
      
      $args = array();
      $args['conditions']['CoPerson.id'] = $this->request->params['named']['copersonid'];
      $args['contain'][] = 'PrimaryName';
      
      $this->set('vv_co_person', $this->Authenticator->Co->CoPerson->find('first', $args));
    }
    
    $this->set('title_for_layout', _txt('ct.authenticators.pl'));
    
    $this->set('vv_authenticator_status', $status);
  }
  
  /**
   * Unlock an Authenticator for a CO Person.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $id Authenticator ID
   */
  
  public function unlock($id) {
    if(!empty($this->request->params['named']['copersonid'])) {
      try {
        // Perform the unlock
        $this->Authenticator->unlock($id,
                                     $this->request->params['named']['copersonid'],
                                     $this->Session->read('Auth.User.co_person_id'));
        
        $this->Flash->set(_txt('rs.updated-a3', array(_txt('fd.status'))), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    } else {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('ct.co_people.1'))),
                        array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
}
