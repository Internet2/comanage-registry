<?php
/**
 * COmanage Registry CO Enrollment Flow Wedges Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");
  
class CoEnrollmentFlowWedgesController extends StandardController {
  // Class name, used by Cake
  public $name = "CoEnrollmentFlowWedges";
  
  // Use the javascript helper for the Views (for drag/drop in particular)
  public $helpers = array('Js');

  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoEnrollmentFlowWedge.ordr' => 'asc'
    )
  );
  
  // We don't directly require a CO, but indirectly we do.
  public $requires_co = true;
  
  public $view_contains = array();
  public $edit_contains = array();

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v4.0.0
   * @throws InvalidArgumentException
   */

  function beforeFilter() {
    parent::beforeFilter();

    $plugins = $this->loadAvailablePlugins('enroller');
    
    // Bind the models so Cake can magically pull associated data.

    foreach(array_values($plugins) as $plugin) {
      $relation = array('hasOne' => array($plugin => array('dependent' => true)));
      
      // Set reset to false so the bindings don't disappear after the first find
      $this->CoEnrollmentFlowWedge->bindModel($relation, false);
      
      $this->view_contains[] = $plugin;
      $this->edit_contains[] = $plugin;
    }

    $this->set('plugins', $plugins);
  }
  
  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  function beforeRender() {
    parent::beforeRender();
    
    if(!$this->request->is('restful')) {
      // Figure out our CO Enrollment Flow ID
      
      $coefid = null;
      
      if($this->action == 'add' || $this->action == 'index' || $this->action == 'order') {
        // Accept co enrollment flow id from the url or the form
        
        if(!empty($this->request->params['named']['coef'])) {
          $coefid = filter_var($this->request->params['named']['coef'],FILTER_SANITIZE_SPECIAL_CHARS);
        } elseif(!empty($this->request->data['CoEnrollmentFlowWedge']['co_enrollment_flow_id'])) {
          $coefid = filter_var($this->request->data['CoEnrollmentFlowWedge']['co_enrollment_flow_id'],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      } elseif(!empty($this->request->params['pass'][0])) {
        // Map the enrollment flow from the requested object
        
        $coefid = $this->CoEnrollmentFlowWedge->field('co_enrollment_flow_id',
                                                      array('id' => $this->request->params['pass'][0]));
      }
      
      $efname = $this->CoEnrollmentFlowWedge->CoEnrollmentFlow->field('name', array('CoEnrollmentFlow.id' => $coefid));
      
      // Override page title
      $this->set('title_for_layout', $this->viewVars['title_for_layout'] . " (" . $efname . ")");
      $this->set('vv_ef_name', $efname);
      $this->set('vv_ef_id', $coefid);
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.0.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    // If a dashboard is specified, use it to get to the CO ID
    
    $coef = null;
    
    if(($this->action == 'add' || $this->action == 'index')
       && !empty($this->params->named['coef'])) {
      $coef = $this->params->named['coef'];
    } elseif(!empty($this->request->data['CoEnrollmentFlowWedge']['co_enrollment_flow_id'])) {
      $coef = $this->request->data['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
    }
    
    if($coef) {
      // Map CO Enrollment Flow to CO
      
      $coId = $this->CoEnrollmentFlowWedge->CoEnrollmentFlow->field('co_id', array('CoEnrollmentFlow.id' => $coef));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_enrollment_flows.1'), $coef)));
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
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Enrollment Flow Wedge?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Enrollment Flow Wedge?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Enrollment Flow Wedge?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit an existing CO Enrollment Flow Wedge's order?
// AJAX ordering not yet implemented
//    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Enrollment Flow Wedge?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Modify ordering for display via AJAX 
//    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Enrollment Flow Wedge?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    // Only retrieve attributes in the current enrollment flow
    
    $ret = array();
    
    $ret['conditions']['CoEnrollmentFlowWedge.co_enrollment_flow_id'] = $this->request->params['named']['coef'];
    
    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.0.0
   */
  
  function performRedirect() {
    if($this->action == 'add' && !empty($this->request->data['CoEnrollmentFlowWedge']['plugin'])) {
      // Redirect to the appropriate plugin to set up whatever it wants

      $pluginName = filter_var($this->request->data['CoEnrollmentFlowWedge']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
      $modelName = $pluginName;

      $target = array();
      $target['plugin'] = Inflector::underscore($pluginName);
      $target['controller'] = Inflector::tableize($modelName);
      $target['action'] = 'edit';
      $target[] = $this->CoEnrollmentFlowWedge->_targetid;

      $this->redirect($target);
    }
    
    if(!empty($this->request->data['CoEnrollmentFlowWedge']['co_enrollment_flow_id'])) {
      // Redirect to the widget index
      
      $target = array();
      $target['plugin'] = null;
      $target['controller'] = 'co_enrollment_flow_wedges';
      $target['action'] = 'index';
      $target['coef'] = filter_var($this->request->data['CoEnrollmentFlowWedge']['co_enrollment_flow_id'],FILTER_SANITIZE_SPECIAL_CHARS);

      $this->redirect($target);
    } else {
      // This was probably a delete, redirect to the enrollment flow index as
      // a lazy but minimal default
      
      $target = array();
      $target['plugin'] = null;
      $target['controller'] = 'co_enrollment_flows';
      $target['action'] = 'index';
      $target['co'] = $this->cur_co['Co']['id'];
      
      $this->redirect($target);
    }
    
    parent::performRedirect();
  }
}
