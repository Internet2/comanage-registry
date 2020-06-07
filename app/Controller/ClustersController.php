<?php
/**
 * COmanage Registry Clusters Controller
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

class ClustersController extends StandardController {
  // Class name, used by Cake
  public $name = "Clusters";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'description' => 'asc'
    )
  );
  
  public $view_contains = array();
  
  public $edit_contains = array();
  
  public $delete_contains = array();
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  // Used by AppController::calculateImpliedCoId
  public $impliedCoIdActions = array(
    'assign' => 'copersonid',
    'status' => 'copersonid'
  );
  
  /**
   * Assign Cluster Accounts for a CO Person.
   *
   * @since  COmanage Registry v3.3.0
   */
  
  public function assign() {
    $coPersonId = null;
    
    if($this->request->is('restful')) {
      // XXX See IdentifiersController for example
    } else {
      if(!empty($this->request->params['named']['copersonid'])) {
        $coPersonId = $this->request->params['named']['copersonid'];
      }
    }
    
    if($coPersonId) {
      // Similar pattern to Identifiers::assign()
      
      $res = $this->Cluster->assign($coPersonId, $this->Session->read('Auth.User.co_person_id'));
      
      if(!empty($res)) {
        // Loop through the results and build result messages
        
        $errs = "";             // Unexpected errors
        $assigned = array();    // Cluster Accounts that were assigned
        $existed = array();     // Cluster Accounts that already existed

        foreach(array_keys($res) as $cluster) {
          if($res[$cluster] === false) {
            $existed[] = $cluster;
          } elseif($res[$cluster] === true) {
            $assigned[] = $cluster;
          } else {
            $errs .= $cluster . ": " . $res[$cluster] . "<br />\n";
          }
        }
        
        if($this->request->is('restful')) {
          if($errs != "") {
            $this->Api->restResultHeader(500, $errs);
          } else {
            $this->Api->restResultHeader(200, "OK");
          }
        } else {
          if($errs != "") {
            $this->Flash->set($errs, array('key' => 'error'));
          }

          if(!empty($assigned)) {
            $this->Flash->set(_txt('rs.cluster.acct.ok', array(implode(',', $assigned))),
                              array('key' => 'success'));
          }

          if(!empty($existed)) {
            $this->Flash->set(_txt('er.cluster.acct.already', array(implode(',', $existed))),
                              array('key' => 'information'));
          }
        }
      } else {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(200, "OK");
        } else {
          $this->Flash->set(_txt('er.cluster.acct.none'), array('key' => 'information'));
        }
      }
    } else {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "CoPerson Does Not Exist");
      } else {
        $this->Flash->set(_txt('er.cop.unk'), array('key' => 'error'));
      }
    }
    
    if(!$this->request->is('restful')) {
      $this->performRedirect();
    }
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v3.3.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    $plugins = $this->loadAvailablePlugins('cluster');
    
    // Bind the models so Cake can magically pull associated data. Note this will
    // create associations with *all* authenticator plugins, not just the one that
    // is actually associated with this Authenticator. Given that most installations
    // will only have a handful of authenticators, that seems OK (vs parsing the request
    // data to figure out which type of Plugin we should bind).
    
    foreach(array_values($plugins) as $plugin) {
      $this->Cluster->bindModel(array('hasOne' => array($plugin => array('dependent' => true))), false);
      
      $this->delete_contains[] = $plugin;
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
    $roles = $this->Role->calculateCMRoles();           // Who we authenticated as
    $pids = $this->parsePersonID($this->request->data); // Who we're asking for
    
// XXX we probably want $managed, but do we want $self?
    // Is this a record we can manage?
    $managed = false;
    $self = false;
    
/*
    if(!empty($roles['copersonid'])
       && !empty($this->request->params['named']['copersonid'])) {
      if($roles['copersonid'] == $this->request->params['named']['copersonid']) {
        $self = true;
      }
      
      $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                        $this->request->params['named']['copersonid']);
    }*/
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform.
    
    // Note these permissions are applied to either a given Authenticator configuration
    // or to an individual's Authenticator, as appropriate for the specified action.
    
    // Add a new Cluster?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
// XXX maybe this should also allow $managed || $self?
    // Auto-assign Accounts for a CO Person on a Cluster?
    $p['assign'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Cluster?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Cluster?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Clusters?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Manage a given CO Person's Clusters?
    $p['manage'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || $managed
                    || $self);
    
    // View a given CO Person's Clusters?
    $p['status'] = ($roles['cmadmin']
                    || $roles['coadmin']
                    || $managed
                    || $self);
    
    // View an existing Cluster?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
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
    if($this->action == 'add' && !empty($this->request->data['Cluster']['plugin'])) {
      // Redirect to the appropriate plugin to set up whatever it wants
      
      $pluginName = filter_var($this->request->data['Cluster']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
      $modelName = $pluginName;
      
      $target = array();
      $target['plugin'] = Inflector::underscore($pluginName);
      $target['controller'] = Inflector::tableize($modelName);
      $target['action'] = 'edit';
      $target[] = $this->Cluster->_targetid;
      
      $this->redirect($target);
    } elseif(!empty($this->request->params['named']['copersonid'])) {
      // Redirect to the CO Person's authenticator status page
      
      $target = array();
      $target['controller'] = 'clusters';
      $target['action'] = 'status';
      $target['copersonid'] = filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS);
      
      $this->redirect($target);
    } else {
      parent::performRedirect();
    }
  }
  
  /**
   * Obtain the status of a Cluster for a CO Person.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer $id Cluster ID
   */
  
  public function status() {
    $status = array();
    
    if(!empty($this->request->params['named']['copersonid'])) {
      // Pull the list of configured clusters
      
      $args = array();
      $args['conditions']['Cluster.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['Cluster.status'] = SuspendableStatusEnum::Active;
      // Pull the related models so we have their instantiated model ID
      $args['contain'] = array_keys($this->viewVars['plugins']);
      
      $clusters = $this->Cluster->find('all', $args);
      
      foreach($clusters as $c) {
        // Only populate the necessary information for the view
        
        $st = array(
          'id'          => $c['Cluster']['id'],
          'description' => $c['Cluster']['description'],
          'plugin'      => $c['Cluster']['plugin']
        );
        
        if(!empty($c[ $c['Cluster']['plugin'] ]['id'])) {
          $st['plugin_id'] = $c[ $c['Cluster']['plugin'] ]['id'];
        }
        
        // Pull the Cluster status
        $st['status'] = $this->Cluster->status($c['Cluster']['id'],
                                               $this->request->params['named']['copersonid']);
        
        $status[] = $st;
      }
      
      // Pull CO Person and name for breadcrumbs, etc
      
      $args = array();
      $args['conditions']['CoPerson.id'] = $this->request->params['named']['copersonid'];
      $args['contain'][] = 'PrimaryName';
      
      $this->set('vv_co_person', $this->Cluster->Co->CoPerson->find('first', $args));
    }
    
    $this->set('title_for_layout', _txt('ct.clusters.pl'));
    
    $this->set('vv_cluster_status', $status);
  }
}
