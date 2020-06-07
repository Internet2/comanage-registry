<?php
/**
 * COmanage Registry Unix Cluster Accounts Controller
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

class UnixClusterAccountsController extends StandardController {
  // Class name, used by Cake
  public $name = "UnixClusterAccounts";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'UnixClusterAccount.gecos' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $requires_person = true;

  // Edit and view need Name for rendering view
  public $edit_contains = array(
    'CoPerson',
  );

  public $view_contains = array(
    'CoPerson',
  );
  
  // We need to track the Unix Cluster ID under certain circumstances to enable performRedirect
  private $ucid = null;
  private $copid = null;
  
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
      // Accept cluster id from the url or unix cluster id from the form, and map it.
      // Note because we get here via CO Person > Clusters, we use Cluster ID at
      // entry, then Unix Cluster ID once we have it.
      // For delete we should really grab it via $id before deleting the object,
      // but we only use it to redirect back to the index view.
      
      if($this->action == 'index' && !empty($this->request->params['named']['clusterid'])) {
        // Map clusterid to unix_cluster_id
        $ucid = $this->UnixClusterAccount->UnixCluster->field('id', array('UnixCluster.cluster_id' => $this->request->params['named']['clusterid']));
      } elseif(!empty($this->request->params['named']['ucid'])) {
        $ucid = filter_var($this->request->params['named']['ucid'],FILTER_SANITIZE_SPECIAL_CHARS);
      } elseif(!empty($this->request->data['UnixClusterGroup']['unix_cluster_id'])) {
        $ucid = filter_var($this->request->data['UnixClusterGroup']['unix_cluster_id'],FILTER_SANITIZE_SPECIAL_CHARS);
      }
    } elseif(!empty($this->request->params['pass'][0])) {
      // Map the ucid from the requested object

      $ucid = $this->UnixClusterAccount->field('unix_cluster_id',
                                                array('id' => $this->request->params['pass'][0]));
    }
    
    if(!empty($ucid)) {
      $args = array();
      $args['conditions']['UnixCluster.id'] = $ucid;
      $args['contain'] = array('Cluster');
      
      $cluster = $this->UnixClusterAccount->UnixCluster->find('first', $args);
      
      if($cluster) {
        $this->set('vv_unix_cluster', $cluster);
        $this->ucid = $cluster['UnixCluster']['id'];
      }
    }
    
    if(in_array($this->action, array('add', 'index'))
       && !empty($this->request->params['named']['copersonid'])) {
      $this->copid = $this->request->params['named']['copersonid'];
    } elseif(!empty($this->request->params['pass'][0])) {
      // Map the co person ID from the requested object
      $this->copid = $this->UnixClusterAccount->field('co_person_id',
                                                array('id' => $this->request->params['pass'][0]));
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
      if($this->copid) {
        // Pull the CO Person record
        $args = array();
        $args['conditions']['CoPerson.id'] = $this->copid;
        // Make sure the CO Person is in the CO
        $args['conditions']['CoPerson.co_id'] = $this->cur_co['Co']['id'];
        $args['contain'] = array('PrimaryName');
      
        $this->set('vv_co_person', $this->UnixClusterAccount->CoPerson->find('first', $args));
      }
      
      if(!empty($this->ucid)) {
        // Pull the list of available Cluster groups
        $this->set('vv_available_groups', $this->UnixClusterAccount->UnixCluster->UnixClusterGroup->availableUnixGroups($this->ucid));
      }
      
      if($this->action == 'index') {
        $this->set('title_for_layout', _txt('pl.unixcluster.accounts', array($this->viewVars['vv_unix_cluster']['Cluster']['description'])));
      }
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
    // We'll accept either a Cluster ID or a Unix Cluster ID for index, since
    // we can get here with the former from the Cluster management index or the
    // latter via perform redirect / etc.
    
    if($this->action == 'index' && !empty($this->params->named['clusterid'])) {
      // We'll use the cluster ID to determine the CO
      $coId = $this->UnixClusterAccount->UnixCluster->Cluster->field('co_id', array('Cluster.id' => $this->params->named['clusterid']));
      
      if(!$coId) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.clusters.1'), $this->params->named['clusterid'])));
      }
      
      return $coId;
    } else {
      // If a unix cluster is specified, use it to get to the CO ID

      $ucid = null;

      if(in_array($this->action, array('add', 'index'))
         && !empty($this->params->named['ucid'])) {
        $ucid = $this->params->named['ucid'];
      } elseif(!empty($this->request->data['UnixClusterAccount']['unix_cluster_id'])) {
        $ucid = $this->request->data['UnixClusterAccount']['unix_cluster_id'];
      }

      if($ucid) {
        // Map UnixCluster to CO
        
        $cid = $this->UnixClusterAccount->UnixCluster->field('cluster_id', array('UnixCluster.id' => $ucid));
        
        if(!$cid) {
          throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.unix_clusters.1'), $ucid)));
        }

        $coId = $this->UnixClusterAccount->UnixCluster->Cluster->field('co_id', array('Cluster.id' => $cid));
        
        if(!$coId) {
          throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.clusters.1'), $ucid)));
        }
        
        return $coId;
      }
    }

    // Or try the default behavior
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v3.3.0
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */
  
  public function generateHistory($action, $newdata, $olddata) {
    switch($action) {
      case 'add':
        $this->UnixClusterAccount->CoPerson->HistoryRecord->record($newdata['UnixClusterAccount']['co_person_id'],
                                                                   null,
                                                                   null,
                                                                   $this->Session->read('Auth.User.co_person_id'),
                                                                   ActionEnum::ClusterAccountAdded,
                                                                   _txt('pl.unixcluster.rs.added', array($newdata['UnixClusterAccount']['unix_cluster_id'],
                                                                                                         $newdata['UnixClusterAccount']['username'],
                                                                                                         $newdata['UnixClusterAccount']['uid'])));
        break;
      case 'delete':
        $this->UnixClusterAccount->CoPerson->HistoryRecord->record($olddata['UnixClusterAccount']['co_person_id'],
                                                                   null,
                                                                   null,
                                                                   $this->Session->read('Auth.User.co_person_id'),
                                                                   ActionEnum::ClusterAccountDeleted,
                                                                   _txt('pl.unixcluster.rs.deleted', array($olddata['UnixClusterAccount']['unix_cluster_id'],
                                                                                                           $olddata['UnixClusterAccount']['username'],
                                                                                                           $olddata['UnixClusterAccount']['uid'])));
        break;
      case 'edit':
        $this->UnixClusterAccount->CoPerson->HistoryRecord->record($olddata['UnixClusterAccount']['co_person_id'],
                                                                   null,
                                                                   null,
                                                                   $this->Session->read('Auth.User.co_person_id'),
                                                                   ActionEnum::ClusterAccountEdited,
                                                                   _txt('pl.unixcluster.rs.edited', array($olddata['UnixClusterAccount']['unix_cluster_id'],
                                                                                                          $olddata['UnixClusterAccount']['username'],
                                                                                                          $olddata['UnixClusterAccount']['uid'],
                                                                                                          $newdata['UnixClusterAccount']['unix_cluster_id'],
                                                                                                          $newdata['UnixClusterAccount']['username'],
                                                                                                          $newdata['UnixClusterAccount']['uid'])));
        break;
    }
    
    return true;
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
    
// XXX Probably need to add in $managed and $self here?
    // Add a new Unix Cluster Account?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Unix Cluster Account?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit an existing Unix Cluster Account?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View all existing Unix Cluster Accounts?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing Unix Cluster Account?
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
    
    if(!empty($this->ucid)) {
      $ret['conditions']['UnixClusterAccount.unix_cluster_id'] = $this->ucid;
    }
    
    if(!empty($this->copid)) {
      $ret['conditions']['UnixClusterAccount.co_person_id'] = $this->copid;
    }

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

    $copersonid = (!empty($this->request->data['UnixClusterAccount']['co_person_id'])
                   ? $this->request->data['UnixClusterAccount']['co_person_id']
                   : (!empty($this->params->named['copersonid'])
                      ? $this->params->named['copersonid']
                      : null));      
    
    if(isset($this->ucid) && $copersonid) {
      $params = array(
        'plugin'     => 'unix_cluster',
        'controller' => 'unix_cluster_accounts',
        'action'     => 'index',
        'ucid'       => $this->ucid,
        'copersonid' => $copersonid
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
