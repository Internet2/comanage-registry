<?php
/**
 * COmanage Registry Data Filters Controller
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class DataFiltersController extends StandardController {
  // Class name, used by Cake
  public $name = "DataFilters";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'description' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  // We want to contain the plugins, but we don't know what they are yet.
  // We'll add them in beforeFilter(). (Don't use recursive here or we'll pull
  // all affiliated OIS records, which would be bad.)
  public $view_contains = array();
  
  public $edit_contains = array();
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v3.3.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    $plugins = $this->loadAvailablePlugins('datafilter');
    
    // Bind the models so Cake can magically pull associated data. Note this will
    // create associations with *all* data filter plugins, not just the one that
    // is actually associated with this Data Filter. Given that most installations
    // will only have a handful of plugins, that seems OK (vs parsing the request
    // data to figure out which type of Plugin we should bind).
    
    foreach(array_values($plugins) as $plugin) {
      $relation = array('hasOne' => array($plugin => array('dependent' => true)));
      
      // Set reset to false so the bindings don't disappear after the first find
      $this->DataFilter->bindModel($relation, false);
      
      // Make this plugin containable
      $this->edit_contains[] = $plugin;
      $this->view_contains[] = $plugin;
    }
    
    $this->set('plugins', $plugins);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();

    // Add a new Data Filter?
    $p['add'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Delete an existing Data Filter?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing Data Filter?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all existing Data Filters?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View an existing Data Filter?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.3.0
   */
  
  function performRedirect() {
    if($this->action == 'add' && !empty($this->DataFilter->data)) {
      // Redirect to the appropriate plugin to set up whatever it wants
      
      $pluginName = $this->DataFilter->data['DataFilter']['plugin'];
      $modelName = $pluginName;
      $pluginModelName = $pluginName . "." . $modelName;
      
      $target = array();
      $target['plugin'] = Inflector::underscore($pluginName);
      $target['controller'] = Inflector::tableize($modelName);
      $target['action'] = 'edit';
      $target[] = $this->DataFilter->data[$pluginName]['id'];
      
      $this->redirect($target);
    } else {
      parent::performRedirect();
    }
  }
}
