<?php
/**
 * COmanage Registry CO Dashboards Controller
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
 * @since         COmanage Registry v0.9.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");
  
class CoDashboardsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoDashboards";
  
  // Establish pagination parameters for HTML views
  
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoDashbord.name' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Auth component is configured
   *
   * @since  COmanage Registry v2.0.0
   * @throws UnauthorizedException (REST)
   */

  function beforeFilter() {
    parent::beforeFilter();

    if($this->action == 'dashboard') {
      // We may or may not have a current CO Person ID
      $coPersonId = $this->Session->read('Auth.User.co_person_id');
      
      if($this->action == 'dashboard'
         && !empty($this->request->params['pass'][0])) {
        // If this dashboard allows unauthenticated users, we need to allow the view
        $visibility = $this->CoDashboard->field('visibility', array('CoDashboard.id' => $this->request->params['pass'][0]));
        
        if($visibility && $visibility == VisibilityEnum::Unauthenticated) {
          $this->Auth->allow('dashboard');
        }
      }
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   *
   * @since  COmanage Registry v3.2.0
   */

  function beforeRender() {
    if(!$this->request->is('restful')) {
      // Pull the list of available CO Groups
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['CoGroup.status'] = SuspendableStatusEnum::Active;
      $args['order'] = array('CoGroup.name ASC');

      $this->set('vv_co_groups', $this->Co->CoGroup->find("list", $args));
    }

    parent::beforeRender();
  }
  
  /**
   * Render the CO Configuration Dashboard.
   *
   * @since  COmanage Registry v3.0.0
   */

  public function configuration() {
    $this->set('title_for_layout', _txt('op.dashboard.configuration', array($this->cur_co['Co']['name'])));
  }
  
  /**
   * Render the CO Dashboard.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer CO Dashboard ID
   */

  public function dashboard($id=null) {
    $dashboardid = $id;
    
    if(!$dashboardid) {
      // Check CO Settings to see if we have a default dashboard for the current CO
      
      $dashboardid = $this->Co->CoSetting->field('co_dashboard_id', array('CoSetting.co_id' => $this->cur_co['Co']['id']));
    }
    
    if($dashboardid) {
      // Pull the dashboard configuration
      
      // Bind the plugins so we can get their instantiated configuration
      $plugins = $this->loadAvailablePlugins('dashboardwidget', 'simple');
      $pcontain = array();
      
      foreach($plugins as $plugin) {
        $this->CoDashboard->CoDashboardWidget->bindModel(
          array('hasOne' => array($plugin.".Co".$plugin => array('dependent' => true))
        ));
        
        $pcontain[] = "Co".$plugin;
        $this->loadModel($plugin.".Co".$plugin);
      }
      
      $args = array();
      $args['conditions']['CoDashboard.id'] = $dashboardid;
      $args['conditions']['CoDashboard.status'] = StatusEnum::Active;
      $args['contain'] = array(
        'CoDashboardWidget' => array('order' => 'CoDashboardWidget.ordr ASC')
      );
      // This doesn't work because the widget model is not (necessarily) Changelog enabled.
      //$args['contain']['CoDashboardWidget'] = $pcontain;
      
      $db = $this->CoDashboard->find('first', $args);
      
      if(empty($db)) {
        $this->Flash->set(_txt('er.notfound', array(_txt('ct.co_dashboards.1'), $dashboardid)), array('key' => 'error'));
        // Not sure exactly where we should go...
        $this->performRedirect();
      }
      
      // Pull the widget configuration separately, since contain() doesn't get it
      for($i = 0;$i < count($db['CoDashboardWidget']);$i++) {
        if($db['CoDashboardWidget'][$i]['status'] == StatusEnum::Active) {
          $plmodel = "Co".$db['CoDashboardWidget'][$i]['plugin'];
          
          $args = array();
          $args['conditions'][$plmodel.".co_dashboard_widget_id"] = $db['CoDashboardWidget'][$i]['id'];
          $args['contain'] = false;
          
          $dbw = $this->$plmodel->find('first', $args);
          
          $db['CoDashboardWidget'][$i][$plmodel] = $dbw[$plmodel];
        }
      }
      
      $this->set('vv_dashboard', $db);
      $this->set('title_for_layout', $db['CoDashboard']['name']);
    } else {
      $this->set('title_for_layout', $this->cur_co['Co']['name']);
    }
    
    // Pull the list of visible dashboards
    
    $args = array();
    $args['conditions']['CoDashboard.co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['CoDashboard.status'] = StatusEnum::Active;
    $args['contain'] = false;
    
    $dbs = $this->CoDashboard->find('all', $args);
    
    // Filter the list on visibility
    $avail = array();
    
    foreach($dbs as $db) {
      if($this->CoDashboard->authorize($db,
                                       $this->Session->read('Auth.User.co_person_id'),
                                       $this->Role)) {
        $avail[ $db['CoDashboard']['id'] ] = $db['CoDashboard']['name'];
      }
    }
    
    $this->set('vv_available_dashboards', $avail);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.9.2
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new Dashboard?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Lock down the configuration dashboard to only cmadmin and coadmin for now (might change in future)
    $p['configuration'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View the dashboard for the specified CO?
    
    if(!empty($this->request->params['pass'][0])) {
      // We need to check the permissions for the given Dashboard.
      
      $args = array();
      $args['conditions']['CoDashboard.id'] = $this->request->params['pass'][0];
      $args['contain'] = false;
      
      $db = $this->CoDashboard->find('first', $args);
      
      if(!empty($db)) {
        $p['dashboard'] = $this->CoDashboard->authorize($db, 
                                                        $this->Session->read('Auth.User.co_person_id'),
                                                        $this->Role);
      } else {
        $p['dashboard'] = false;
      }
    } else {
      // The default dashboard. Since this is the landing page for the CO, it's visible
      // to any registered CO Person. (However the widgets will honor the dashboard's
      // visibility, and so the content of the dashboard might not render.)
      
      $p['dashboard'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    }
    
    // Delete an existing Dashboard?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Dashboard?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Dashboards?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Execute a cross-model search?
    $p['search'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    // View an existing Dashboard?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v0.9.2
   * @return Integer The CO ID if found, or -1 if not
   */
  
  public function parseCOID($data = null) {
    if($this->action == 'dashboard' || $this->action == 'configuration' ) {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }
    
    if($this->action == 'search') {
      if(isset($this->request->query['co'])) {
        return $this->request->query['co'];
      }
    }
    
    return parent::parseCOID();
  }
  
  /**
   * Perform a cross model search.
   *
   * @since  COmanage Registry v3.1.0
   */
  
  public function search() {
    $results = array();
    $roles = array();
    $models = array();
    
    // Obtain our search limit
    $searchLimit = $this->Co->CoSetting->getGlobalSearchLimit($this->cur_co['Co']['id']);
    
    if(!empty($this->request->query['q'])) {
      /* To add a new backend to search:
       * (1) Implement $model->search($id, $q, $limit)
       *     Do NOT use containable behavior (which issues one query per result).
       *     use linkable instead (which uses joins, but results may need reformatting).
       *     This applies even if the model should be relatively unique, as long
       *     as it implements substring searching.
       * (2) Add the model here, and define which roles can query it
       * (3) Add the model to $uses, above
       * (4) Add the model to View/CoDashboards/search.ctp
       * (5) Update documentation at https://spaces.internet2.edu/display/COmanage/Searching+and+Filtering
       */
      
      $models = array(
        'Address' => array(
          'parent' => array('CoPersonRole'),
          'roles' => array('cmadmin', 'coadmin', 'couadmin')
        ),
        'CoDepartment' => array(
          'parent' => array('Co'),
          'roles' => array('cmadmin', 'coadmin', 'couadmin', 'comember')
        ),
        'CoEmailList' => array(
          'parent' => array('Co'),
          'roles' => array('cmadmin', 'coadmin', 'couadmin')
        ),
        'CoEnrollmentFlow' => array(
          'parent' => array('Co'),
          'roles' => array('cmadmin', 'coadmin')
        ),
        'CoGroup' => array(
          'parent' => array('Co'),
          'roles' => array('cmadmin', 'coadmin', 'couadmin', 'comember')
        ),
        'CoPersonRole' => array(
          'parent' => array('CoPreson'),
          'roles' => array('cmadmin', 'coadmin', 'couadmin')
        ),
        // CoService does not allow comember since portals may be constrained by cou
        'CoService' => array(
          'parent' => array('Co'),
          'roles' => array('cmadmin', 'coadmin', 'couadmin')
        ),
        'EmailAddress' => array(
          'parent' => array('CoPerson'),
          'roles' => array('cmadmin', 'coadmin', 'couadmin')
        ),
        'Identifier' => array(
          'parent' => array('CoGroup', 'CoPerson'),
          'roles' => array('cmadmin', 'coadmin', 'couadmin')
        ),
        'Name' => array(
          'parent' => array('CoPerson'),
          'roles' => array('cmadmin', 'coadmin', 'couadmin')
        ),
        'Organization' => array(
          'parent' => array('Co'),
          'roles' => array('cmadmin', 'coadmin', 'couadmin')
        ),
        'TelephoneNumber' => array(
          'parent' => array('CoPersonRole'),
          'roles' => array('cmadmin', 'coadmin', 'couadmin')
        )
      );
      
      // Determine which plugin models are searchable
      $plugins = $this->loadAvailablePlugins('all', 'simple');
      
      // And track the display fields for the view
      $pdisplay = array();

      foreach($plugins as $plugin) {
        if(method_exists($this->$plugin, 'cmPluginSearchModels')) {
          $pSearchInfo = $this->$plugin->cmPluginSearchModels();
          
          foreach($pSearchInfo as $pmodel => $pcfg) {
            $models[$pmodel] = array(
              // Hardcoding parent probably isn't right, but it covers our
              // immediate use case (filtering duplicate search results)
              'parent' => array('Co'),
              'roles' => $pcfg['permissions']
            );
            $pdisplay[$pmodel] = $pcfg['displayField'];
          }
        }
      }
      
      $this->set('vv_plugin_display_fields', $pdisplay);
      
      // Which models we search depends on our permissions
      
      $roles = $this->Role->calculateCMRoles();
      
      foreach(array_keys($models) as $m) {
        $authorized = false;
        
        foreach($models[$m]['roles'] as $role) {
          if(isset($roles[$role]) && $roles[$role]) {
            $authorized = true;
            break;
          }
        }
        
        if($authorized) {
          // Query backend, first making sure we've loaded the model.
          // (We can't use $this->uses since it breaks index().)
          
          $this->loadModel($m);
          
          // If we're searching a plugin model, we need the base name of the 
          // model itself (not the full plugin.model name)
          $smodel = preg_replace('/.*\./', '', $m);
          
          $results[$m] = $this->$smodel->search($this->cur_co['Co']['id'],
                                                $this->request->query['q'],
                                                $searchLimit);
          
          if(count($results[$m]) >= $searchLimit) {
            $this->Flash->set(_txt('rs.search.limit', array($smodel, $searchLimit)),
                              array('key' => 'information'));
          }
        }
      }
    }
    
    // If we get exactly one search result, redirect to that record. This is slightly
    // tricky in that we could get multiple results that point to the same object
    // (eg: because there are multiple similar names attached to a CO Person).
    // So we sort the results according to the parent model. Models with a parent
    // of CO are not considered duplicates.
    
    $c = array(
      // For models with a parent of CO, we just need a count
      'Co' => 0,
      // Otherwise we need to track which IDs we've seen
      'CoGroup' => array(),
      'CoPerson' => array(),
      'CoPersonRole' => array()
    );
    
    foreach(array_keys($models) as $m) {
      foreach($models[$m]['parent'] as $p) {
        if($p == 'Co'
           && isset($results[$m])) {
          $c[$p] += count($results[$m]);
        } elseif($p == 'CoGroup') {
          $c['CoGroup'] = array_filter(array_unique(array_merge($c['CoGroup'], Hash::extract($results, $m.'.{n}.'.$m.'.co_group_id'))));
        } elseif($p == 'CoPerson') {
          $c['CoPerson'] = array_filter(array_unique(array_merge($c['CoPerson'], Hash::extract($results, $m.'.{n}.'.$m.'.co_person_id'))));
        } else {
          $c['CoPersonRole'] = array_filter(array_unique(array_merge($c['CoPersonRole'], Hash::extract($results, $m.'.{n}.'.$m.'.co_person_role_id'))));
        }
      }
    }
    
    // It's a single match if there is a single person or person role result,
    // or if there is a single result overall, redirect to that result.
    if(($c['Co'] == 0 && (count($c['CoPerson']) + count($c['CoPersonRole']) + count($c['CoGroup'])) == 1)
       || ($c['Co'] == 1 && (count($c['CoPerson']) + count($c['CoPersonRole']) + count($c['CoGroup'])) == 0)) {
      $matches = Hash::extract($results, '{s}.{n}');
      $match = Hash::get($matches, '0');
      
      // Figure out what model the results are for
      $matchModel = key($match);
      
      $this->Flash->set(_txt('rs.search.1',
                             array(filter_var($this->request->query['q'], FILTER_SANITIZE_SPECIAL_CHARS),
                                   _txt('ct.'.Inflector::tableize($matchModel).'.1'))),
                        array('key' => 'information'));
      
      // If the record references a person, redirect to the person record instead
      if(!empty($match[$matchModel]['co_person_id'])) {
        $args = array(
          'controller' => 'co_people',
          'action'     => 'canvas',
          $match[$matchModel]['co_person_id']
        );
        
        $this->redirect($args);
      }
      
      // If the record references a person role, redirect to the person role record instead
      if(!empty($match[$matchModel]['co_person_role_id'])) {
        $args = array(
          'controller' => 'co_person_roles',
          'action'     => 'edit',
          $match[$matchModel]['co_person_role_id']
        );
        
        $this->redirect($args);
      }
      
      // Otherwise redirect to the model's view. We don't really know if the current
      // person can edit or just view, so if they're an admin we redirect them to edit
      // otherwise view.
      
      $plugin = null;
      
      if(!isset($models[$matchModel])) {
        // This is probably a plugin model, so we need to walk through the list
        // of models looking for the right one
        
        foreach($models as $m => $d) {
          if(strpos($m, '.')) {
            // We have something of the form Plugin.Model
            
            $ms = explode('.', $m, 2);
            
            if($ms[1] == $matchModel) {
              // This is the right plugin
              $plugin = Inflector::underscore($ms[0]);
              break;
            }
          }
        }
      }
      
      $args = array(
        'plugin'     => $plugin,
        'controller' => Inflector::tableize($matchModel),
        'action'     => ((isset($roles['cmadmin']) && $roles['cmadmin'])
                         || (isset($roles['coadmin']) && $roles['coadmin'])
                         || (isset($roles['couadmin']) && $roles['couadmin']))
                        ? 'edit' : 'view',
        $match[$matchModel]['id']
      );
      
      $this->redirect($args);
    }
    
    // Otherwise drop through to the view
    
    $this->set('vv_results', $results);
  }
}
