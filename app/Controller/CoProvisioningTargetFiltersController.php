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

class CoProvisioningTargetFiltersController extends StandardController {
  // Class name, used by Cake
  public $name = "CoProvisioningTargetFilters";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'ordr' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  // We want to contain the plugins, but we don't know what they are yet.
  // We'll add them in beforeFilter(). (Don't use recursive here or we'll pull
  // all affiliated OIS records, which would be bad.)
  public $view_contains = array(
    'DataFilter'
  );
  
  public $edit_contains = array(
    'DataFilter'
  );
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   *
   * @since  COmanage Registry v3.3.0
   */

  public function beforeRender() {
    parent::beforeRender();
    
    // Figure out our provisioning target ID
    
    $coptid = null;

    if($this->action == 'add' || $this->action == 'index' || $this->action == 'order') {
      // Accept coptid from the url or the form
      
      if(!empty($this->request->params['named']['copt'])) {
        $coptid = filter_var($this->request->params['named']['copt'],FILTER_SANITIZE_SPECIAL_CHARS);
      } elseif(!empty($this->request->data['CoProvisioningTargetFilter']['co_provisioning_target_id'])) {
        $coptid = $this->request->data['CoProvisioningTargetFilter']['co_provisioning_target_id'];
      }
    } elseif(($this->action == 'edit' || $this->action == 'delete')
             && !empty($this->request->params['pass'][0])) {
      // Map the provisioning target from the requested object

      $coptid = $this->CoProvisioningTargetFilter->field('co_provisioning_target_id',
                                                         array('id' => $this->request->params['pass'][0]));
    }

    $ptname = $this->CoProvisioningTargetFilter->CoProvisioningTarget->field('description', array('CoProvisioningTarget.id' => $coptid));

    // Override page title
    $this->set('title_for_layout', _txt('ct.co_provisioning_target_filters.pl') . " (" . $ptname . ")");
    $this->set('vv_pt_name', $ptname);
    $this->set('vv_pt_id', $coptid);
    
    // Pull the set of available data filters.
    
    $args = array();
    $args['conditions']['DataFilter.co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['DataFilter.status'] = SuspendableStatusEnum::Active;
    $args['fields'] = array('id', 'description');
    $args['order'] = 'description';
    $args['contain'] = false;
    
    $this->set('vv_avail_filters', $this->CoProvisioningTargetFilter->DataFilter->find('list', $args));
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v3.3.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    // If a provisioning target is specified, use it to get to the CO ID
    
    $copt = null;
    
    if(in_array($this->action, array('add', 'index', 'order', 'reorder'))
       && !empty($this->params->named['copt'])) {
      $copt = $this->params->named['copt'];
    } elseif(!empty($this->request->data['CoProvisioningTargetFilter']['co_provisioning_target_id'])) {
      $copt = $this->request->data['CoProvisioningTargetFilter']['co_provisioning_target_id'];
    }
    
    if($copt) {
      // Map CO Provisioning Target to CO

      $coId = $this->CoProvisioningTargetFilter->CoProvisioningTarget->field('co_id',
                                                                             array('id' => $copt));

      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_provisioning_targets.1'), $coef)));
      }
    }
    
    return parent::calculateImpliedCoId();
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

    // Add a new CO Provisioning Target Filter?
    $p['add'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Delete an existing CO Provisioning Target Filter?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing CO Provisioning Target Filter?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all existing CO Provisioning Target Filters?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing CO Provisioning Target Filter's order?
    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Modify ordering for display via AJAX 
    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Provisioning Target Filter?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v3.3.0
   * @return Array An array suitable for use in $this->paginate
   */

  function paginationConditions() {
    // Only retrieve attributes in the current enrollment flow

    $ret = array();

    $ret['conditions']['CoProvisioningTargetFilter.co_provisioning_target_id'] = $this->request->params['named']['copt'];

    return $ret;
  }
    
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.3.0
   */
  
  function performRedirect() {
    // Append the provisioning target ID to the redirect

    if(isset($this->request->data['CoProvisioningTargetFilter']['co_provisioning_target_id']))
      $coptid = $this->request->data['CoProvisioningTargetFilter']['co_provisioning_target_id'];
    elseif(isset($this->request->params['named']['copt']))
      $coptid = filter_var($this->request->params['named']['copt'],FILTER_SANITIZE_SPECIAL_CHARS);

    $this->redirect(array('controller' => 'co_provisioning_target_filters',
                          'action' => 'index',
                          'copt' => $coptid));
  }
}
