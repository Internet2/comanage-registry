<?php
/**
 * COmanage Registry Organization Sources Controller
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class OrganizationSourcesController extends StandardController {
  // Class name, used by Cake
  public $name = "OrganizationSources";
  
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
   * @since  COmanage Registry v4.4.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    $plugins = $this->loadAvailablePlugins('orgsource');
    
    // Bind the models so Cake can magically pull associated data. Note this will
    // create associations with *all* orgid source plugins, not just the one that
    // is actually associated with this Org Identity Source. Given that most installations
    // will only have a handful of source plugins, that seems OK (vs parsing the request
    // data to figure out which type of Plugin we should bind).
    
    foreach(array_values($plugins) as $plugin) {
      $relation = array('hasOne' => array($plugin => array('dependent' => true)));
      
      // Set reset to false so the bindings don't disappear after the first find
      $this->OrganizationSource->bindModel($relation, false);
      
      // Make this plugin containable
      $this->edit_contains[] = $plugin;
      $this->view_contains[] = $plugin;
    }
    
    $this->set('plugins', $plugins);

    if(!empty($this->request->params['pass'][0])
       && is_numeric($this->request->params['pass'][0])) {
      $args = array();
      $args['conditions']['OrganizationSource.id'] = $this->request->params['pass'][0];
      $args['contain'] = $this->edit_contains;
      
      $os = $this->OrganizationSource->find('first', $args);
      
      if(empty($os)) {
        $this->Flash->set(_txt('er.notfound', array(_txt('ct.organization_sources.1'), $id)), array('key' => 'error'));
        $this->performRedirect();
      }
      
      $this->set('vv_organization_source', $os);
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.4.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new Organization Source?
    $p['add'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Delete an existing Organization Source?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Edit an existing Organization Source?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View all existing Organization Sources?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Retrieve a record from an Orgaziation Source?
    $p['retrieve'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // Query an Organization Source?
    $p['query'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // (Re)sync an Organization from its Source?
    $p['sync'] = $roles['cmadmin'] || $roles['coadmin'];
    
    // View an existing Organization Source?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.4.0
   */
  
  function performRedirect() {
    if($this->action == 'add' && !empty($this->OrganizationSource->data)) {
      // Redirect to the appropriate plugin to set up whatever it wants
      
      $pluginName = $this->OrganizationSource->data['OrganizationSource']['plugin'];
      $modelName = $pluginName;
      $pluginModelName = $pluginName . "." . $modelName;
      
      $target = array();
      $target['plugin'] = Inflector::underscore($pluginName);
      $target['controller'] = Inflector::tableize($modelName);
      $target['action'] = 'edit';
      $target[] = $this->OrganizationSource->data[$pluginName]['id'];
      
      $this->redirect($target);
    } else {
      parent::performRedirect();
    }
  }
  
  /**
   * Query an Organization Source. This is not called "search" because it
   * would conflict with the function signature for StandardController::search,
   * and also for compatibility with OrgIdentitySource.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Integer $id OrganizationSource to search
   */
  
  public function query($id) {
    if($this->request->is('post')) {
      // Convert the request to use named parameters to be consistent with every
      // other form, eg search/#/field:value
      
      $url = array();
      $url['action'] = 'query/' . $id;
      
      foreach($this->request->data['search'] as $field => $value) {
        if(!empty($value)) {
          $url['search.'.$field] = $value;
        }
      }
      
      // We don't really need this, except that if the last parameter has a dot in it
      // (eg: an email address), Cake will parse the new URL as having an extension.
      // eg: /query/23/search.email=foo@bar.com => request with extension type "com"
      // By ensuring the last parameter is this, the search parameter will not be munged.
      // eg: /query/23/search.email=foo@bar.com/op=search
      $url['op'] = 'search';
      
      // redirect to the new url
      $this->redirect($url, null, true);
    }
    
    if(isset($this->viewVars['vv_organization_source']['status'])
       && $this->viewVars['vv_organization_source']['status'] != SuspendableStatusEnum::Active) {
      $this->Flash->set(_txt('er.perm.status',
                             array(_txt('en.status.susp', null, $this->viewVars['vv_organization_source']['status']))),
                        array('key' => 'error'));
      $this->performRedirect();
    }

    $this->set('title_for_layout',
               _txt('op.search-a', array($this->viewVars['vv_organization_source']['OrganizationSource']['description'])));
    
    // Obtain the searchable attributes and pass to the view
    
    $this->set('vv_search_attrs', $this->OrganizationSource->searchableAttributes($id));

    // See if any search parameters were passed
    
    $searchQuery = array();
    
    foreach($this->request->params['named'] as $k => $v) {
      // Search terms are of the form search.field, we want "field"
      if(strncmp($k, "search.", 7)==0) {
        $qk = explode('.', $k, 2);
        
        $searchQuery[ $qk[1] ] = $v;
      }
    }
    
    if(!empty($searchQuery)) {
      // We have a search query, pass it to the backend
      
      $this->set('vv_search_query', $searchQuery);
      
      try {
        $this->set('vv_search_results', $this->OrganizationSource->search($id, $searchQuery));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    }
  }
  
  /**
   * Retrieve a record from an Organization Source.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Integer $id OrganizationSource to search
   */
  
  public function retrieve($id) {
    if(!empty($this->request->params['named']['key'])) {
      // Un-base64 the key
      $key = cmg_urldecode($this->request->params['named']['key']);
      
      try {
        $r = $this->OrganizationSource->retrieve($id, $key);

        $this->set('title_for_layout',
                   _txt('op.view-a', array(filter_var($key,FILTER_SANITIZE_SPECIAL_CHARS))));
        
        if(!empty($r['rec']['Organization'])) {
          $this->set('vv_organization_record', $r['rec']);
        }
        
        if(!empty($r['raw'])) {
          $this->set('vv_raw_source_record', $r['raw']);
        }
                
        // See if there is an associated Organization
        $args = array();
        $args['conditions']['OrganizationSourceRecord.organization_source_id'] = $id;
        $args['conditions']['OrganizationSourceRecord.source_key'] = $key;
        $args['contain'] = false;
        
        $rec = $this->OrganizationSource->OrganizationSourceRecord->find('first', $args);
        
        $this->set('vv_os_record', $rec);
      }
      catch(InvalidArgumentException $e) {
        // No records found. We'll let this fall through to the view in case an admin
        // is looking at an OrgIdentity record where the source is no longer available.
        $this->set('vv_not_found', true);
        $this->set('title_for_layout', _txt('er.notfound', array(_txt('fd.sorid'), filter_var($key,FILTER_SANITIZE_SPECIAL_CHARS))));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
        $this->performRedirect();
      }
    } else {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('fd.sorid'))),
                        array('key' => 'error'));
    }
  }
  
  /**
   * Sync an Organization from an Organization Source.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Integer $id OrganizationSource to search
   */
  
  public function sync($id) {
    if(!empty($this->request->params['named']['key'])) {
      try {
        $key = filter_var($this->request->params['named']['key'],FILTER_SANITIZE_SPECIAL_CHARS);
        
        $ret = $this->OrganizationSource->syncOrganization($id,
                                                           cmg_urldecode($key),
                                                           $this->Session->read('Auth.User.co_person_id'),
                                                           null,
                                                           // Always force sync on manual request
                                                           true);
        
        $this->Flash->set(_txt('rs.os.src.'.$ret['status']), array('key' => 'success'));
        
        // Redirect to organization
        $args = array(
          'controller' => 'organizations',
          // Identities from source can't be edited, so send to view
          'action'     => 'view',
          $ret['id']
        );
        
        $this->redirect($args);
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    } else {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('fd.sorid'))),
                        array('key' => 'error'));
    }
    
    // No sync view, so always perform redirect
    $this->performRedirect();
  }
}
