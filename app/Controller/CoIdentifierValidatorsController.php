<?php
/**
 * COmanage Registry CO Identifier Validators Controller
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");
  
class CoIdentifierValidatorsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoIdentifierValidators";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoIdentifierValidator.description' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v2.0.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Pull the set of validators
    $plugins = $this->loadAvailablePlugins('identifiervalidator');
    
    $this->set('vv_plugins', $plugins);
    
    // And track which are instantiated
    $iPlugins = array();
    
    foreach(array_keys($plugins) as $p) {
      // Walk the list of plugins to see which ones are instantiated,
      // then create the association. This will pull associated data for the views.

      if($this->$p->cmPluginInstantiate) {
        $this->CoIdentifierValidator->bindModel(array('hasOne' => array($p)), false);
        $iPlugins[$p] = true;
      } else {
        $iPlugins[$p] = false;
      }
    }
    
    $this->set('vv_inst_plugins', $iPlugins);
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   *
   * @since  COmanage Registry v2.0.0
   */

  function beforeRender() {
    // Pull the set of Extended Types. We support both EmailAddress and Identifier
    $types = array();
    // Normally we wouldn't use a localized language string as an array key,
    // but in this case it's used to render the select popup
    $types[_txt('ct.email_addresses.1')] = $this->Co->CoExtendedType->active($this->cur_co['Co']['id'], 'EmailAddress.type', 'keyed');
    $types[_txt('ct.identifiers.1')] = $this->Co->CoExtendedType->active($this->cur_co['Co']['id'], 'Identifier.type', 'keyed');
    
    $this->set('vv_available_types', $types);
    
    parent::beforeRender();
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v2.0.0
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
   * @since  COmanage Registry v2.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Identifier Validator?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Identifier Validator?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Identifier Validator?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Identifier Validators?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Identifier Validator?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v2.0.0
   */
  
  function performRedirect() {
    if($this->action == 'add'
       && !empty($this->CoIdentifierValidator->data['CoIdentifierValidator']['plugin'])
       && $this->viewVars['vv_inst_plugins'][ $this->CoIdentifierValidator->data['CoIdentifierValidator']['plugin'] ]) {
      // Redirect to the appropriate plugin to set up whatever it wants,
      // if it is instantiated
      
      $pluginName = $this->CoIdentifierValidator->data['CoIdentifierValidator']['plugin'];
      $modelName = $pluginName;
      
      $target = array();
      $target['plugin'] = Inflector::underscore($pluginName);
      $target['controller'] = Inflector::tableize($modelName);
      $target['action'] = 'edit';
      $target[] = $this->CoIdentifierValidator->data[$pluginName]['id'];
      
      $this->redirect($target);
    } else {
      parent::performRedirect();
    }
  }
}
