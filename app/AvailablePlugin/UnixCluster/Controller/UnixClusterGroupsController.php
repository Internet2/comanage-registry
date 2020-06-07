<?php
/**
 * COmanage Registry Unix Cluster Groups Controller
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
 * @package       registry-plugin
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class UnixClusterGroupsController extends StandardController {
  // Class name, used by Cake
  public $name = "UnixClusterGroups";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'UnixClusterGroup.co_group_id' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

  // Edit and view need Name for rendering view
  public $edit_contains = array(
    'CoGroup',
  );

  public $view_contains = array(
    'CoGroup',
  );
  
  // We need to track the Unix Cluster ID under certain circumstances to enable performRedirect
  private $ucid = null;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v3.3.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Figure out our Unix Cluster ID. We do this here rather than in beforeRender
    // because we need $ucid on redirect after save.

    $ucid = null;

    if($this->action == 'add' || $this->action == 'delete' || $this->action == 'index') {
      // Accept ucid from the url or the form
      // For delete we should really grab it via $id before deleting the object,
      // but we only use it to redirect back to the index view

      if(!empty($this->request->params['named']['ucid'])) {
        $ucid = filter_var($this->request->params['named']['ucid'],FILTER_SANITIZE_SPECIAL_CHARS);
      } elseif(!empty($this->request->data['UnixClusterGroup']['unix_cluster_id'])) {
        $ucid = filter_var($this->request->data['UnixClusterGroup']['unix_cluster_id'],FILTER_SANITIZE_SPECIAL_CHARS);
      }
    } elseif(!empty($this->request->params['pass'][0])) {
      /* XXX is there a use case for this here?
      // Map the dashboard from the requested object

      $codbid = $this->CoDashboardWidget->field('co_dashboard_id',
                                                array('id' => $this->request->params['pass'][0]));*/
    }
    
    if(!empty($ucid)) {
      $args = array();
      $args['conditions']['UnixCluster.id'] = $ucid;
      $args['contain'] = array('Cluster');
      
      $cluster = $this->UnixClusterGroup->UnixCluster->find('first', $args);
      
      if($cluster) {
        $this->set('vv_unix_cluster', $cluster);
        $this->ucid = $cluster['UnixCluster']['id'];
      }
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v3.3.0
   */

  function beforeRender() {
    parent::beforeRender();
    
    if(!$this->request->is('restful')) {
      // Pull the list of available groups
      
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      $args['order'] = 'CoGroup.name ASC';
      $args['contain'] = false;
      
      $this->set('vv_available_groups', $this->UnixClusterGroup->CoGroup->find('list', $args));
    }
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
    // If a unix cluster is specified, use it to get to the CO ID

    $ucid = null;

    if(in_array($this->action, array('add', 'index'))
       && !empty($this->params->named['ucid'])) {
      $ucid = $this->params->named['ucid'];
    } elseif(!empty($this->request->data['UnixClusterGroup']['unix_cluster_id'])) {
      $ucid = $this->request->data['UnixClusterGroup']['unix_cluster_id'];
    }

    if($ucid) {
      // Map UnixCluster to CO
      
      $cid = $this->UnixClusterGroup->UnixCluster->field('cluster_id', array('UnixCluster.id' => $ucid));
      
      if(!$cid) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.unix_clusters.1'), $ucid)));
      }

      $coId = $this->UnixClusterGroup->UnixCluster->Cluster->field('co_id', array('Cluster.id' => $cid));
      
      if(!$coId) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.clusters.1'), $cid)));
      }
      
      return $coId;
    }

    // Or try the default behavior
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
    $roles = $this->Role->calculateCMRoles();             // What was authenticated
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Unix Cluster Group?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Unix Cluster Group?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit an existing Unix Cluster Group?
    $p['edit'] = false; // We don't really have a use case for edit yet...

    // View all existing Unix Cluster Groups?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing Unix Cluster Group?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v3.3.0
   * @return Array An array suitable for use in $this->paginate
   */

  function paginationConditions() {
    // Only retrieve attributes in the current unix cluster

    $ret = array();

    $ret['conditions']['UnixClusterGroup.unix_cluster_id'] = $this->request->params['named']['ucid'];

    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.3.0
   */
  
  function performRedirect() {
    // Figure out where to redirect back to based on how we were called
    
    if(isset($this->ucid)) {
      $params = array(
        'plugin'     => 'unix_cluster',
        'controller' => 'unix_cluster_groups',
        'action'     => 'index',
        'ucid'       => $this->ucid
      );
    } else {
      // A perhaps not ideal default, but we shouldn't get here
      $params = array(
        'plugin'     => 'unix_cluster',
        'controller' => 'clusters',
        'action'     => 'index',
        'co'         => $this->cur_co['Co']['id']
      );
    }
    
    $this->redirect($params);
  }
}
