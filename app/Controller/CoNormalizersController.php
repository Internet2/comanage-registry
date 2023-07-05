<?php
/**
 * COmanage Registry Normalizers Controller
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");
  
class CoNormalizersController extends StandardController {
  // Class name, used by Cake
  public $name = "CoNormalizers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoNormalizer.description' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v4.3.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Pull the set of validators
    $plugins = $this->loadAvailablePlugins('normalizer');
    
    $this->set('vv_plugins', $plugins);
    
    // And track which are instantiated
    $iPlugins = array();
    
    foreach(array_keys($plugins) as $p) {
      // Walk the list of plugins to see which ones are instantiated,
      // then create the association. This will pull associated data for the views.

      if($this->$p->cmPluginInstantiate) {
        $this->CoNormalizer->bindModel(array('hasOne' => array($p)), false);
        $iPlugins[$p] = true;
      } else {
        $iPlugins[$p] = false;
      }
    }
    
    $this->set('vv_inst_plugins', $iPlugins);
  }

  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v4.3.0
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    // Based on OrgIdentitySourcesController::checkDeleteDependencies,
    // which in turn is basically the same logic as CoProvisioningTargetsController.php
    
    // Annoyingly, the read() call in standardController resets the associations made
    // by the bindModel() call in beforeFilter(), above. Beyond that, deep down in
    // Cake's Model, a find() is called as part of the delete() which also resets the associations.
    // So we have to manually delete any dependencies.
    
    // Use the previously obtained list of plugins as a guide
    
    foreach(array_keys($this->viewVars['vv_inst_plugins']) as $plugin) {
      if($this->viewVars['vv_inst_plugins'][$plugin]) {
        // Plugin is instantiated
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
    }
    
    return true;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.3.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Normalizer?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Normalizer?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Normalizer?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Normalizers?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Normalizer?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Reorder an existing Normalizer?
    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);
    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v4.3.0
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
   * @since  COmanage Registry v4.3.0
   */
  
  function performRedirect() {
    if($this->action == 'add'
       && !empty($this->CoNormalizer->data['CoNormalizer']['plugin'])
       && $this->viewVars['vv_inst_plugins'][ $this->CoNormalizer->data['CoNormalizer']['plugin'] ]) {
      // Redirect to the appropriate plugin to set up whatever it wants,
      // if it is instantiated
      
      $pluginName = $this->CoNormalizer->data['CoNormalizer']['plugin'];
      $modelName = $pluginName;
      
      $target = array();
      $target['plugin'] = Inflector::underscore($pluginName);
      $target['controller'] = Inflector::tableize($modelName);
      $target['action'] = 'edit';
      $target[] = $this->CoNormalizer->data[$pluginName]['id'];
      
      $this->redirect($target);
    } else {
      parent::performRedirect();
    }
  }
}
