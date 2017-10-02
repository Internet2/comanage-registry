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
  /*
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoDashbord.description' => 'asc'
    )
  );
  */
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $uses = array(
    'Address',
    'CoDepartment',
    'CoEmailList',
    'CoEnrollmentFlow',
    'CoGroup',
    'CoPersonRole',
    'CoService',
    'EmailAddress',
    'Identifier',
    'Name',
    'TelephoneNumber'
  );

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
   * Render the CO Dashboard.
   *
   * @since  COmanage Registry v0.9.2
   */

  public function dashboard() {
    // XXX implement this

    $this->set('title_for_layout', $this->cur_co['Co']['name']);
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
    
    // Lock down the configuration dashboard to only cmadmin and coadmin for now (might change in future)
    $p['configuration'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View the dashboard for the specified CO?
    $p['dashboard'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);
    
    // Execute a cross-model search?
    $p['search'] = ($roles['cmadmin'] || $roles['coadmin'] || $roles['comember']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a cross model search.
   *
   * @since  COmanage Registry v3.1.0
   */
  
  public function search() {
    $results = array();
    $roles = array();
    
    if(!empty($this->request->query['q'])) {
      /* To add a new backend to search:
       * (1) Implement $model->search($id, $q)
       * (2) Add the model here, and define which roles can query it
       * (3) Add the model to $uses, above
       * (4) Add the model to View/CoDashboards/search.ctp
       * (5) Update documentation at https://spaces.internet2.edu/display/COmanage/Searching+and+Filtering
       */
      
      $models = array(
        'Address' => array('cmadmin', 'coadmin', 'couadmin'),
        'CoDepartment' => array('cmadmin', 'coadmin', 'couadmin', 'comember'),
        'CoEmailList' => array('cmadmin', 'coadmin', 'couadmin'),
        'CoEnrollmentFlow' => array('cmadmin', 'coadmin'),
        'CoGroup' => array('cmadmin', 'coadmin', 'couadmin', 'comember'),
        'CoPersonRole' => array('cmadmin', 'coadmin', 'couadmin'),
        // CoService does not allow comember since portals may be constrained by cou
        'CoService' => array('cmadmin', 'coadmin', 'couadmin'),
        'EmailAddress' => array('cmadmin', 'coadmin', 'couadmin'),
        'Identifier' => array('cmadmin', 'coadmin', 'couadmin'),
        'Name' => array('cmadmin', 'coadmin', 'couadmin'),
        'TelephoneNumber' => array('cmadmin', 'coadmin', 'couadmin'),
      );
      
      // Which models we search depends on our permissions
      
      $roles = $this->Role->calculateCMRoles();
      
      foreach(array_keys($models) as $m) {
        $authorized = false;
        
        foreach($models[$m] as $role) {
          if(isset($roles[$role]) && $roles[$role]) {
            $authorized = true;
            break;
          }
        }
        
        if($authorized) {
          // Query backend
          
          $results[$m] = $this->$m->search($this->cur_co['Co']['id'],
                                           $this->request->query['q']);
        }
      }
    }
    
    // If we get exactly one person search result, redirect to that record
    
    $matches = Hash::extract($results, '{s}.{n}');
    
    if(count($matches) == 1) {
      $match = Hash::get($matches, '0');
      
      // Figure out what model the results are for
      $matchModel = key($match);
      
      $this->Flash->set(_txt('rs.search.1',
                             array(filter_var($this->request->query['q'],FILTER_SANITIZE_SPECIAL_CHARS),
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
      $args = array(
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