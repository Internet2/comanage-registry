<?php
/**
 * COmanage Registry Organizational Identity Sources Controller
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class OrgIdentitySourcesController extends StandardController {
  // Class name, used by Cake
  public $name = "OrgIdentitySources";
  
  // When using additional models, we must also specify our own
  public $uses = array('OrgIdentitySource',
                       'CmpEnrollmentConfiguration',
                       'CoPetition');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'description' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = false;

  // We want to contain the plugins, but we don't know what they are yet.
  // We'll add them in beforeFilter(). (Don't use recursive here or we'll pull
  // all affiliated OIS records, which would be bad.)
  public $view_contains = array();
  
  public $edit_contains = array();
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v2.0.0
   * @throws InvalidArgumentException
   */
  
  function beforeFilter() {
    // This controller may or may not require a CO, depending on how
    // the CMP Enrollment Configuration is set up. Check and adjust before
    // beforeFilter is called.
    
    $pool = $this->CmpEnrollmentConfiguration->orgIdentitiesPooled();
    
    if(!$pool) {
      $this->requires_co = true;
      
      // Associate the CO model
      $this->OrgIdentitySource->bindModel(array('belongsTo' => array('Co')));
    }
    
    // The views will also need this
    $this->set('pool_org_identities', $pool);
    
    parent::beforeFilter();
    
    $plugins = $this->loadAvailablePlugins('orgidsource');
    
    // Bind the models so Cake can magically pull associated data. Note this will
    // create associations with *all* orgid source plugins, not just the one that
    // is actually associated with this Org Identity Source. Given that most installations
    // will only have a handful of source plugins, that seems OK (vs parsing the request
    // data to figure out which type of Plugin we should bind).
    
    foreach(array_values($plugins) as $plugin) {
      $relation = array('hasOne' => array($plugin => array('dependent' => true)));
      
      // Set reset to false so the bindings don't disappear after the first find
      $this->OrgIdentitySource->bindModel($relation, false);
      
      // Make this plugin containable
      $this->edit_contains[] = $plugin;
      $this->view_contains[] = $plugin;
    }
    
    $this->set('plugins', $plugins);
    
    if(!empty($this->request->params['pass'][0])
       && is_numeric($this->request->params['pass'][0])) {
      // Pull the plugin information associated with the ID
      
      // Pull OIS group mappings, but as they only fire via pipelines,
      // (which only work when not pooled) check the pooled status.
      
      if(!$pool) {
        try {
          $Backend = $this->OrgIdentitySource->instantiateBackendModel($this->request->params['pass'][0]);
          
          // With the backend instantiated, we can see if the instance supports groupable attributes
          // The groupable attributes are not needed for a `delete`. While during the calculation process,
          // we might throw an Exception, e.g., FileSource. Throwing is meaningful for other actions
          // but not for a `delete`. As a result, we will skip the calculation and allow the `delete` to conclude.
          // XXX CO-2804
          if(!$pool && $this->action !== 'delete') {
            // Group mappings only fire via pipelines, which only work when not pooled
            $this->set('vv_plugin_group_attrs', $Backend->groupableAttributes());
          }
        }
        catch(Exception $e) {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
          $this->performRedirect();
        }
      }
      
      $args = array();
      $args['conditions']['OrgIdentitySource.id'] = $this->request->params['pass'][0];
      $args['contain'] = $this->edit_contains;
      
      $ois = $this->OrgIdentitySource->find('first', $args);
      
      if(empty($ois)) {
        $this->Flash->set(_txt('er.notfound', array(_txt('ct.org_identity_sources.1'), $id)), array('key' => 'error'));
        $this->performRedirect();
      }
      
      $this->set('vv_org_identity_source', $ois['OrgIdentitySource']);
    }
    
    if(!$pool) {
      // Pull the set of available pipelines. This is only possible for unpooled.
      $args = array();
      $args['conditions']['CoPipeline.status'] = SuspendableStatusEnum::Active;
      $args['conditions']['CoPipeline.co_id'] = $this->cur_co['Co']['id'];
      $args['fields'] = array('CoPipeline.id', 'CoPipeline.name');
      $args['order'] = 'CoPipeline.name ASC';
      $args['contain'] = false;
      
      $this->set('vv_co_pipelines', $this->OrgIdentitySource->CoPipeline->find('list', $args));
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   *
   * @since  COmanage Registry v2.0.0
   */

  public function beforeRender() {
    if(!$this->request->is('restful')) {
      $this->set('vv_identifier_types', $this->OrgIdentitySource->Co->CoPerson->Identifier->types($this->cur_co['Co']['id'], 'type'));
      
      $args = array();
      $args['conditions']['DataFilter.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['DataFilter.status'] = SuspendableStatusEnum::Active;
      $args['conditions']['DataFilter.context'] = DataFilterContextEnum::OrgIdentitySource;
      $args['fields'] = array('id', 'description');
      $args['order'] = 'description';
      $args['contain'] = false;
      
      $this->set('vv_available_filters', $this->OrgIdentitySource->OrgIdentitySourceFilter->DataFilter->find('list', $args));
    }
    
    parent::beforeRender();
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v2.0.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    if($this->action == "select"
       && !empty($this->request->params['named']['copetitionid'])) {
      // Pull the CO from the Petition
      
      $coId = $this->OrgIdentitySource->Co->CoPetition->field('co_id',
                                                              array('id' => $this->request->params['named']['copetitionid']));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_petitions.1'),
                                                      filter_var($this->request->params['named']['copetitionid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }
    
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkDeleteDependencies($curdata) {
    // Annoyingly, the read() call in standardController resets the associations made
    // by the bindModel() call in beforeFilter(), above. Beyond that, deep down in
    // Cake's Model, a find() is called as part of the delete() which also resets the associations.
    // So we have to manually delete any dependencies.
    
    // This is basically the same logic as CoProvisioningTargetsController.php
    
    // Use the previously obtained list of plugins as a guide
    $plugins = $this->viewVars['plugins'];
    
    foreach(array_values($plugins) as $plugin) {
      $model = $plugin;
      
      if(!empty($curdata[$model]['id'])) {
        $this->loadModel($plugin . "." . $model);
        $this->$model->delete($curdata[$model]['id']);
      }
    }
    
    return true;
  }
  
  /**
   * Create an Org Identity from an Org Identity Source.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id OrgIdentitySource to search
   */
  
  public function create($id) {
    if(!empty($this->request->params['named']['key'])) {
      try {
        $key = filter_var($this->request->params['named']['key'],FILTER_SANITIZE_SPECIAL_CHARS);
        
        $coId = null;
        $targetCoPersonId = null;
        $provision = true;
        
        if(!empty($this->cur_co['Co']['id'])) {
          $coId = $this->cur_co['Co']['id'];
        }
        
        if(!empty($this->request->params['named']['copetitionid'])) {
          // See if there is already a CO Person ID associated with this petition.
          // If so, force link the new org identity to that petition.
          $targetCoPersonId = $this->CoPetition->field('enrollee_co_person_id',
                                                       array('CoPetition.id' => $this->request->params['named']['copetitionid']));
          
          $provision = false;
        }
        
        $orgid = $this->OrgIdentitySource->createOrgIdentity($id,
                                                             $key,
                                                             $this->Session->read('Auth.User.co_person_id'),
                                                             $coId,
                                                             $targetCoPersonId,
                                                             $provision);
        
        if(!empty($this->request->params['named']['copetitionid'])) {
          // Redirect back into the enrollment flow to link the identity
          
          $args = array(
            'controller'    => 'co_petitions',
            'action'        => 'selectOrgIdentity',
            filter_var($this->request->params['named']['copetitionid'],FILTER_SANITIZE_SPECIAL_CHARS),
            'orgidentityid' => $orgid
          );
        } else {
          $this->Flash->set(_txt('rs.added-a2', array(_txt('ct.org_identity_sources.pl'),
                                                      $key)),
                            array('key' => 'success'));
          
          // Redirect to org identity
          $args = array(
            'controller' => 'org_identities',
            // Identities from source can't be edited, so send to view
            'action'     => 'view',
            $orgid
          );
        }
        
        $this->redirect($args);
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
        
        // Redirect back to retrieve key
        $args = array(
          'controller' => 'org_identity_sources',
          'action'     => 'retrieve',
          $id,
          'key'        => $key
        );
        
        $this->redirect($args);
      }
    } else {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('fd.sorid'))),
                        array('key' => 'error'));
    }
    
    // No create view, so always perform redirect
    $this->performRedirect();
  }
  
  /**
   * Obtain all records from a backend. Because this just obtains record keys,
   * it is primarily intended for developers and debugging.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id OrgIdentitySource to inventory
   */
  
  public function inventory($id) {
    $this->set('title_for_layout',
           _txt('op.ois.inventory', array($this->viewVars['vv_org_identity_source']['description'])));
    
    try {
      $keys = $this->OrgIdentitySource->obtainSourceKeys($id);
      sort($keys);
      
      $this->set('vv_source_keys', $keys);
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v2.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    $coadmin = false;
    
    if($roles['coadmin'] && !$this->CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
      // CO Admins can only manage org identity sources if org identities are NOT pooled
      $coadmin = true;
    }
    
    // Add a new Org Identity Source?
    $p['add'] = $roles['cmadmin'] || $coadmin;
    
    // Create a new Org Identity from a Source?
    $p['create'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    
    // Delete an existing Org Identity Source?
    $p['delete'] = $roles['cmadmin'] || $coadmin;
    
    // Edit an existing Org Identity Source?
    $p['edit'] = $roles['cmadmin'] || $coadmin;
    
    // View all existing Org Identity Sources?
    $p['index'] = $roles['cmadmin'] || $coadmin;
    
    // Retrieve all records from an Org Identity Source?
    $p['inventory'] = $roles['cmadmin'] || $coadmin;
    
    // Retrieve a record from an Org Identity Source?
    $p['retrieve'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    
    // Query an Org Identity Source?
    $p['query'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    
    // Select an Org Identity Source (in order to create an Org Identity)?
    $p['select'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    
    // (Re)sync an Org Identity from its Source?
    $p['sync'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    
    // View an existing Org Identity Source?
    $p['view'] = $roles['cmadmin'] || $coadmin;
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v2.0.0
   */
  
  function performRedirect() {
    if($this->action == 'add' && !empty($this->OrgIdentitySource->data)) {
      // Redirect to the appropriate plugin to set up whatever it wants
      
      $pluginName = $this->OrgIdentitySource->data['OrgIdentitySource']['plugin'];
      $modelName = $pluginName;
      $pluginModelName = $pluginName . "." . $modelName;
      
      $target = array();
      $target['plugin'] = Inflector::underscore($pluginName);
      $target['controller'] = Inflector::tableize($modelName);
      $target['action'] = 'edit';
      $target[] = $this->OrgIdentitySource->data[$pluginName]['id'];
      
      $this->redirect($target);
    } else {
      parent::performRedirect();
    }
  }
  
  /**
   * Query an Org Identity Source. This is not called "search" because it
   * would conflict with the function signature for StandardController::search.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id OrgIdentitySource to search
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
      
      if(!empty($this->request->data['OrgIdentitySource']['copetitionid'])) {
        $url['copetitionid'] = $this->request->data['OrgIdentitySource']['copetitionid'];
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
    
    if(isset($this->viewVars['vv_org_identity_source']['status'])
       && $this->viewVars['vv_org_identity_source']['status'] != SuspendableStatusEnum::Active) {
      $this->Flash->set(_txt('er.perm.status',
                             array(_txt('en.status.susp', null, $this->viewVars['vv_org_identity_source']['status']))),
                        array('key' => 'error'));
      $this->performRedirect();
    }
    
    $this->set('title_for_layout',
               _txt('op.search-a', array($this->viewVars['vv_org_identity_source']['description'])));
    
    // Obtain the searchable attributes and pass to the view
    
    $this->set('vv_search_attrs', $this->OrgIdentitySource->searchableAttributes($id));
    
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
        $this->set('vv_search_results', $this->OrgIdentitySource->search($id, $searchQuery));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    }
  }
  
  /**
   * Retrieve a record from an Org Identity Source.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id OrgIdentitySource to search
   */
  
  public function retrieve($id) {
    if(!empty($this->request->params['named']['key'])) {
      try {
        $r = $this->OrgIdentitySource->retrieve($id, $this->request->params['named']['key']);
        
        $this->set('title_for_layout',
                   _txt('op.view-a', array(filter_var($this->request->params['named']['key'],FILTER_SANITIZE_SPECIAL_CHARS))));
        
        if(!empty($r['orgidentity'])) {
          $this->set('vv_org_source_record', $r['orgidentity']);
        }
        
        if(!empty($r['raw'])) {
          $this->set('vv_raw_source_record', $r['raw']);
          
          // Also generate a group mapping
          $groupAttrs = $this->OrgIdentitySource->resultToGroups($id, $r['raw']);
          $mappedGroups = $this->OrgIdentitySource->CoGroupOisMapping->mapGroups($id, $groupAttrs);
          
          if(!empty($mappedGroups)) {
            // Pull the Group Names
            $args = array();
            $args['conditions']['CoGroup.id'] = array_keys($mappedGroups);
            $args['contain'] = false;
            
            $coGroups = $this->OrgIdentitySource->Co->CoGroup->find('all', $args);
            
            // Insert a CoGroupMember object for each found group
            
            for($i = 0;$i < count($coGroups);$i++) {
              $coGroups[$i]['CoGroupMember'] = array(
                'member' => (isset($mappedGroups[ $coGroups[$i]['CoGroup']['id'] ]['role'])
                             && $mappedGroups[ $coGroups[$i]['CoGroup']['id'] ]['role'] == 'member'),
                'valid_from' => $mappedGroups[ $coGroups[$i]['CoGroup']['id'] ]['valid_from'],
                'valid_through' => $mappedGroups[ $coGroups[$i]['CoGroup']['id'] ]['valid_through'],
              );
            }
            
            $this->set('vv_mapped_groups', $coGroups);
          }
        }
        
        if(!empty($r['hash'])) {
          $this->set('vv_source_record_hash', $r['hash']);
        }
        
        // See if there is an associated Org Identity
        $args = array();
        $args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $id;
        $args['conditions']['OrgIdentitySourceRecord.sorid'] = $this->request->params['named']['key'];
        $args['contain'] = false;
        
        $rec = $this->OrgIdentitySource->OrgIdentitySourceRecord->find('first', $args);
        
        $this->set('vv_ois_record', $rec);
      }
      catch(InvalidArgumentException $e) {
        // No records found. We'll let this fall through to the view in case an admin
        // is looking at an OrgIdentity record where the source is no longer available.
        $this->set('vv_not_found', true);
        $this->set('title_for_layout', _txt('er.notfound', array(_txt('fd.key'), filter_var($this->request->params['named']['key'],FILTER_SANITIZE_SPECIAL_CHARS))));
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
   * Select an Org Identity Source to operate over.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  public function select() {
    // Set page title
    $this->set('title_for_layout', _txt('ct.org_identity_sources.pl'));
    
    $args = array();
    
    if(!empty($this->request->params['named']['copetitionid'])) {
      // Obtain a list of available sources, as configured for the enrollment flow
      // to which the current petition is attached.
      
      // Note that this filtering of available sources is advisory, and not intended
      // to (eg) restrict COU admins from seeing other sources. As of the current
      // implementation, and CO/U admin can query and OIS backend manually, so
      // enforcing a restriction here would not actually prevent a COU admin from
      // being able to see data.
      
      // Map the petition ID to an enrollment flow to Enrollment Sources to Org Identity Sources
      
      $args['joins'][0]['table'] = 'co_enrollment_sources';
      $args['joins'][0]['alias'] = 'CoEnrollmentSource';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'CoEnrollmentSource.org_identity_source_id=OrgIdentitySource.id';
      $args['joins'][1]['table'] = 'co_enrollment_flows';
      $args['joins'][1]['alias'] = 'CoEnrollmentFlow';
      $args['joins'][1]['type'] = 'INNER';
      $args['joins'][1]['conditions'][0] = 'CoEnrollmentSource.co_enrollment_flow_id=CoEnrollmentFlow.id';
      $args['joins'][2]['table'] = 'co_petitions';
      $args['joins'][2]['alias'] = 'CoPetition';
      $args['joins'][2]['type'] = 'INNER';
      $args['joins'][2]['conditions'][0] = 'CoPetition.co_enrollment_flow_id=CoEnrollmentFlow.id';
      $args['conditions']['CoPetition.id'] = $this->request->params['named']['copetitionid'];
      $args['conditions']['CoEnrollmentSource.org_identity_mode'] = EnrollmentOrgIdentityModeEnum::OISSelect;
    }
    // else we're here from Org Identities > Add From OIS
    
    $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['OrgIdentitySource.co_id'] = $this->cur_co['Co']['id'];
    $args['fields'] = array('OrgIdentitySource.id', 'OrgIdentitySource.description');
    $args['contain'] = false;

    $this->set('vv_org_id_sources', $this->OrgIdentitySource->find('list', $args));     
  }
  
  /**
   * Sync an Org Identity from an Org Identity Source.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id OrgIdentitySource to search
   */
  
  public function sync($id) {
    if(!empty($this->request->params['named']['key'])) {
      try {
        $key = filter_var($this->request->params['named']['key'],FILTER_SANITIZE_SPECIAL_CHARS);
        
        $ret = $this->OrgIdentitySource->syncOrgIdentity($id,
                                                         $key,
                                                         $this->Session->read('Auth.User.co_person_id'),
                                                         null,
                                                         // Always force sync on manual request (CO-1556)
                                                         true);
        
        $this->Flash->set(_txt('rs.org.src.'.$ret['status']), array('key' => 'success'));
        
        // Redirect to org identity
        $args = array(
          'controller' => 'org_identities',
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
