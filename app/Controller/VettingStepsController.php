<?php
/**
 * COmanage Registry Vetting Steps Controller
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

class VettingStepsController extends StandardController {
  // Class name, used by Cake
  public $name = "VettingSteps";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'ordr' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $edit_contains = array();
  public $view_contains = array();
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v4.1.0
   * @throws InvalidArgumentException
   */   
  
  function beforeFilter() {
    parent::beforeFilter();
    
    $plugins = $this->loadAvailablePlugins('vetter');
    
    // Bind the models so Cake can magically pull associated data. Note this
    // will create associations with *all* vetter plugins, not just the one that
    // is actually associated with this VettingStep. Given that most
    // installations will only have a handful of vetters, that seems OK (vs
    // parsing the request data to figure out which type of Plugin we should
    // bind).
    
    foreach(array_values($plugins) as $plugin) {
      $this->VettingStep->bindModel(array('hasOne'
                                           => array($plugin
                                                    => array('dependent' => true))),
                                    false);
      
      $this->edit_contains[] = $plugin;
      $this->view_contains[] = $plugin;
    }
    
    $this->set('plugins', $plugins);
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v4.1.0
   */

  function beforeRender() {
    if(!$this->request->is('restful')) {
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['CoGroup.status'] = SuspendableStatusEnum::Active;
      $args['order'] = array('CoGroup.name ASC');
      $args['contain'] = false;

      $this->set('vv_available_groups', $this->Co->CoGroup->find("list", $args));
    }
    
    parent::beforeRender();
  }

  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v4.1.0
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */

  function checkDeleteDependencies($curdata) {
    // Annoyingly, the read() call in standardController resets the associations made
    // by the bindModel() call in beforeFilter(), above. Beyond that, deep down in
    // Cake's Model, a find() is called as part of the delete() which also resets the associations.
    // So we have to manually delete any dependencies.
    
    // Use the previously obtained list of plugins as a guide
    $plugins = $this->viewVars['plugins'];
    
    foreach(array_values($plugins) as $plugin) {
      $model = $plugin;
      
      if(!empty($curdata[$model]['id'])) {
        // (CO-1988)Remove the plugin object from the instance and enforce the creation of a new one
        if(!empty(ClassRegistry::getObject($model))) {
          ClassRegistry::removeObject($model);
        }
        $this->loadModel($plugin . "." . $model);
        $this->$model->delete($curdata[$model]['id']);
      }
    }
    
    return true;
  }
  
  /**
   * Perform any followups following a write operation.  Note that if this
   * method fails, it must return a warning or REST response, but that the
   * overall transaction is still considered a success (add/edit is not
   * rolled back).
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.1.0
   * @param  Array Request data
   * @param  Array Current data
   * @param  Array Original request data (unmodified by callbacks)
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
    if(!$curdata) {
      // Create an instance of the plugin vetting step. We do this here to avoid
      // an inconsistent state where the vetting step is created without a
      // corresponding plugin record.
      
      // A better check would be to see if there is an existing corresponding row
      // (rather than !$curdata) since we don't fail if the initial attempt to create
      // the row fails.
      
      $pluginName = $reqdata['VettingStep']['plugin'];
      $modelName = $pluginName;
      $pluginModelName = $pluginName . "." . $modelName;
      
      $target = array();
      $target[$modelName]['vetting_step_id'] = $this->VettingStep->id;
      
      // Note that we have to disable validation because we want to create an empty row.
      $this->loadModel($pluginModelName);
      
      if(!$this->$modelName->save($target, false)) {
        return false;
      }
      $this->_targetid = $this->$modelName->id;
    }
    
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Vetting Step?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Vetting Step?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Vetting Step?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Vetting Steps?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Vetting Step's order?
    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Modify ordering for display via AJAX
    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Vetting Step?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v4.1.0
   * @return Integer The CO ID if found, or -1 if not
   */
  
  public function parseCOID($data = NULL) {
    if($this->action == 'order'
       || $this->action == 'reorder') {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }
    
    return parent::parseCOID();
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.1.0
   */
  
  function performRedirect() {
    if($this->action == 'add' && !empty($this->request->data['VettingStep']['plugin'])) {
      // Redirect to the appropriate plugin to set up whatever it wants
      
      $pluginName = filter_var($this->request->data['VettingStep']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
      $modelName = $pluginName;
      $pluginModelName = $pluginName . "." . $modelName;
      
      $target = array();
      $target['plugin'] = Inflector::underscore($pluginName);
      $target['controller'] = Inflector::tableize($modelName);
      $target['action'] = 'edit';
      $target[] = $this->_targetid;
      
      $this->redirect($target);
    } else {
      parent::performRedirect();
    }
  }
}
