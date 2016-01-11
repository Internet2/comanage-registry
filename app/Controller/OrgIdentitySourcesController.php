<?php
/**
 * COmanage Registry Organizational Identity Sources Controller
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class OrgIdentitySourcesController extends StandardController {
  // Class name, used by Cake
  public $name = "OrgIdentitySources";
  
  // When using additional models, we must also specify our own
  public $uses = array('OrgIdentitySource', 'CmpEnrollmentConfiguration');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'description' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = false;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: $plugins set
   *
   * @since  COmanage Registry v1.1.0
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
    }
    
    $this->set('plugins', $plugins);
    
    if(!empty($this->request->params['pass'][0])
       && is_numeric($this->request->params['pass'][0])) {
      // Pull the plugin information associated with the ID
      $args = array();
      $args['conditions']['OrgIdentitySource.id'] = $this->request->params['pass'][0];
      // Do not set contain = false, we need the related model to pass to the backend
      
      $ois = $this->OrgIdentitySource->find('first', $args);
      
      if(empty($ois)) {
        $this->Flash->set(_txt('er.notfound', array(_txt('ct.org_identity_sources.1'), $id)), array('key' => 'error'));
        $this->performRedirect();
      }
      
      $this->set('vv_org_identity_source', $ois['OrgIdentitySource']);
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v1.1.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId() {
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
                                                      Sanitize::html($this->request->params['named']['copetitionid']))));
      }
    }
    
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v1.1.0
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
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to search
   */
  
  public function create($id) {
    if(!empty($this->request->params['named']['key'])) {
      try {
        $key = Sanitize::html($this->request->params['named']['key']);
        
        $coId = null;
        
        if(!empty($this->cur_co['Co']['id'])) {
          $coId = $this->cur_co['Co']['id'];
        }
        
        $orgid = $this->OrgIdentitySource->createOrgIdentity($id,
                                                             $key,
                                                             $this->Session->read('Auth.User.co_person_id'),
                                                             $coId);
        
        if(!empty($this->request->params['named']['copetitionid'])) {
          // Redirect back into the enrollment flow to link the identity
          
          $args = array(
            'controller'    => 'co_petitions',
            'action'        => 'selectOrgIdentity',
            Sanitize::html($this->request->params['named']['copetitionid']),
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
      }
    } else {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('fd.sorid'))),
                        array('key' => 'error'));
    }
    
    // No create view, so always perform redirect
    $this->performRedirect();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v1.1.0
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
    
    // Retrieve a record from an Org Identity Source?
    $p['retrieve'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    
    // Search an Org Identity Source?
    $p['search'] = $roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin'];
    
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
   * @since  COmanage Registry v1.1.0
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
   * Retrieve a record from an Org Identity Source.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to search
   */
  
  public function retrieve($id) {
    if(!empty($this->request->params['named']['key'])) {
      try {
        $r = $this->OrgIdentitySource->retrieve($id, $this->request->params['named']['key']);
        
        $this->set('title_for_layout',
                   _txt('op.view-a', array(Sanitize::html($this->request->params['named']['key']))));
        
        if(!empty($r['orgidentity'])) {
          $this->set('vv_org_source_record', $r['orgidentity']);
        }
        
        if(!empty($r['raw'])) {
          $this->set('vv_raw_source_record', $r['raw']);
        }
        
        // See if there is an associated Org Identity
        $args = array();
        $args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $id;
        $args['conditions']['OrgIdentitySourceRecord.sorid'] = $this->request->params['named']['key'];
        $args['contain'] = false;
        
        $rec = $this->OrgIdentitySource->OrgIdentitySourceRecord->find('first', $args);
        
        $this->set('vv_ois_record', $rec);
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
   * Search an Org Identity Source.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to search
   */
  
  public function search($id) {
    if($this->request->is('post')) {
      // Convert the request to use named parameters to be consistent with every
      // other form, eg search/#/field:value
      
      $url = array();
      $url['action'] = 'search/' . $id;
      
      foreach($this->request->data['Search'] as $field => $value) {
        if(!empty($value)) {
          $url['Search.'.$field] = $value; 
        }
      }
      
      if(!empty($this->request->data['OrgIdentitySource']['copetitionid'])) {
        $url['copetitionid'] = $this->request->data['OrgIdentitySource']['copetitionid'];
      }
      
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
      // Search terms are of the form Search.field, we want "field"
      if(strncmp($k, "Search.", 7)==0) {
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
   * Select an Org Identity Source to operate over.
   *
   * @since  COmanage Registry v1.1.0
   */
  
  public function select() {
    // Set page title
    $this->set('title_for_layout', _txt('ct.org_identity_sources.pl'));
    
    // Obtain a list of available sources
    
    $args = array();
    $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
    if(!empty($this->cur_co['Co']['id'])) {
      $args['conditions']['OrgIdentitySource.co_id'] = $this->cur_co['Co']['id'];
    }
    $args['fields'] = array('id', 'description');
    $args['contain'] = false;
    
    $this->set('vv_org_id_sources', $this->OrgIdentitySource->find('list', $args));
  }
  
  /**
   * Sync an Org Identity from an Org Identity Source.
   *
   * @since  COmanage Registry v1.1.0
   * @param  Integer $id OrgIdentitySource to search
   */
  
  public function sync($id) {
    if(!empty($this->request->params['named']['key'])) {
      try {
        $key = Sanitize::html($this->request->params['named']['key']);
        
        $ret = $this->OrgIdentitySource->syncOrgIdentity($id,
                                                         $key,
                                                         $this->Session->read('Auth.User.co_person_id'));
        
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
