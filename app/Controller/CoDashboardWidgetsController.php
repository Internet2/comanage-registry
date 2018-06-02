<?php
/**
 * COmanage Registry CO Dashboard Widgets Controller
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");
  
class CoDashboardWidgetsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoDashboardWidgets";
  
  // Use the javascript helper for the Views (for drag/drop in particular)
  public $helpers = array('Js');

  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoDashboardWidget.ordr' => 'asc'
    )
  );
  
  // We don't directly require a CO, but indirectly we do.
  public $requires_co = true;

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v3.2.0
   * @throws InvalidArgumentException
   */

  function beforeFilter() {
    parent::beforeFilter();

    $plugins = $this->loadAvailablePlugins('dashboardwidget');
    
    // Bind the models so Cake can magically pull associated data. Note this will
    // create associations with *all* dashboard widget plugins, not just the one that
    // is actually associated with this Dashboard Widget. Given that most installations
    // will only have a handful of such plugins, that seems OK (vs parsing the request
    // data to figure out which type of Plugin we should bind).

    foreach(array_values($plugins) as $plugin) {
      $relation = array('hasOne' => array("Co" . $plugin => array('dependent' => true)));
      
      // Set reset to false so the bindings don't disappear after the first find
      $this->CoDashboardWidget->bindModel($relation, false);
    }

    $this->set('plugins', $plugins);
  }
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v3.2.0
   */
  
  function beforeRender() {
    parent::beforeRender();
    
    if(!$this->request->is('restful')) {
      // Figure out our CO Dashboard ID
      
      $codbid = null;
      
      if($this->action == 'add' || $this->action == 'index' || $this->action == 'order') {
        // Accept codashboard id from the url or the form
        
        if(!empty($this->request->params['named']['codashboard'])) {
          $codbid = filter_var($this->request->params['named']['codashboard'],FILTER_SANITIZE_SPECIAL_CHARS);
        } elseif(!empty($this->request->data['CoDashboardWidget']['co_dashboard_id'])) {
          $codbid = filter_var($this->request->data['CoDashboardWidget']['co_dashboard_id'],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      } elseif(!empty($this->request->params['pass'][0])) {
        // Map the dashboard from the requested object
        
        $codbid = $this->CoDashboardWidget->field('co_dashboard_id',
                                                  array('id' => $this->request->params['pass'][0]));
      }
      
      $dbname = $this->CoDashboardWidget->CoDashboard->field('name', array('CoDashboard.id' => $codbid));
      
      // Override page title
      $this->set('title_for_layout', $this->viewVars['title_for_layout'] . " (" . $dbname . ")");
      $this->set('vv_db_name', $dbname);
      $this->set('vv_db_id', $codbid);
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v3.2.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    // If a dashboard is specified, use it to get to the CO ID
    
    $codb = null;
    
    if(!empty($this->params->named['codashboard'])) {
      $codb = $this->params->named['codashboard'];
    } elseif(!empty($this->request->data['CoDashboardWidget']['co_dashboard_id'])) {
      $codb = $this->request->data['CoDashboardWidget']['co_dashboard_id'];
    }
    
    if($codb) {
      // Map CO Dashboard to CO
      
      $coId = $this->CoDashboardWidget->CoDashboard->field('co_id', array('CoDashboard.id' => $codb));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_dashboards.1'), $codb)));
      }
    }
    
    // Or try the default behavior
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.2.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Dashboard Widget?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Dashboard Widget?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Dashboard Widget?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit an existing CO Dashboard Widget's order?
    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Dashboard Widget?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Modify ordering for display via AJAX 
    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Dashboard Widget?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v3.2.0
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    // Only retrieve attributes in the current enrollment flow
    
    $ret = array();
    
    $ret['conditions']['CoDashboardWidget.co_dashboard_id'] = $this->request->params['named']['codashboard'];
    
    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.2.0
   */
  
  function performRedirect() {
    if($this->action == 'add' && !empty($this->request->data['CoDashboardWidget']['plugin'])) {
      // Redirect to the appropriate plugin to set up whatever it wants

      $pluginName = filter_var($this->request->data['CoDashboardWidget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
      $modelName = $pluginName;

      $target = array();
      $target['plugin'] = Inflector::underscore($pluginName);
      $target['controller'] = "co_" . Inflector::tableize($modelName);
      $target['action'] = 'edit';
      $target[] = $this->CoDashboardWidget->_targetid;

      $this->redirect($target);
    }
  }
}
