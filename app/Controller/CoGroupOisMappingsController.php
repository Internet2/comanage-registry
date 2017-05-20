<?php
/**
 * COmanage Registry CO Group OIS Mappings Controller
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
  
class CoGroupOisMappingsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoGroupOisMappings";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoGroupOisMapping.attribute' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    if(!$this->request->is('restful')) {
      // Find the OIS id. We do this here rather than beforeRender() because the
      // latter doesn't run on delete.
      
      $ois_id = null;
      
      if($this->action == 'add' || $this->action == 'index') {
        if(!empty($this->request->params['named']['org_identity_source'])) {
          $ois_id = $this->request->params['named']['org_identity_source'];
        } elseif(!empty($this->request->data['CoGroupOisMapping']['org_identity_source_id'])) {
          $ois_id = $this->request->data['CoGroupOisMapping']['org_identity_source_id'];
        }
      } elseif(isset($this->viewVars['co_group_ois_mappings'][0]['CoGroupOisMapping']['org_identity_source_id'])) {
        $ois_id = $this->viewVars['co_group_ois_mappings'][0]['CoGroupOisMapping']['org_identity_source_id'];
      } elseif(!empty($this->request->params['pass'][0])) {
        // Look up the OIS ID
        $ois_id = $this->CoGroupOisMapping->field('org_identity_source_id',
                                                  array('CoGroupOisMapping.id' =>
                                                        $this->request->params['pass'][0]));
      }
      
      $this->set('vv_ois_id', $ois_id);
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   *
   * @since  COmanage Registry v2.0.0
   */
  
  function beforeRender() {
    if(!$this->request->is('restful')) {
      // Find the OIS id
      $ois_id = $this->viewVars['vv_ois_id'];
      
      // Instantiate the backend to get the available group mapping attributes.
      
      try {
        $Backend = $this->CoGroupOisMapping->OrgIdentitySource->instantiateBackendModel($ois_id);
        
        $this->set('vv_ois_group_attrs', $Backend->groupableAttributes());
        
        // And provide the available set of groups to query
        $args = array();
        $args['conditions']['CoGroup.status'] = SuspendableStatusEnum::Active;
        $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
        $args['fields'] = array('CoGroup.id', 'CoGroup.name');
        $args['order'] = array('CoGroup.name' => 'ASC');
        $args['contain'] = false;
        
        $this->set('vv_groups', $this->CoGroupOisMapping->CoGroup->find('list', $args));
      }
      catch(Exception $e) {
        // Just fail silently
      }
    }
    
    parent::beforeRender();
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Array $data Array of data for parsing Person ID
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    // For index view, the CO is implied by the OIS requested
    
    if($this->action == 'add' || $this->action == 'index') {
      $oisId = null;
      
      if(!empty($this->request->params['named']['org_identity_source'])) {
        $oisId = $this->request->params['named']['org_identity_source'];
      } elseif(!empty($this->request->data['CoGroupOisMapping']['org_identity_source_id'])) {
        $oisId = $this->request->data['CoGroupOisMapping']['org_identity_source_id'];
      }
      
      if($oisId) {
        $coId = $this->CoGroupOisMapping->OrgIdentitySource->field('co_id',
                                                                   array('id' => $oisId));
        
        if($coId) {
          return $coId;
        } else {
          // Note that we currently don't support org identities pooled (where $coID would be null)
          throw new InvalidArgumentException(_txt('er.notfound',
                                                  array(_txt('ct.org_identity_sources.1'),
                                                        filter_var($oisId,FILTER_SANITIZE_SPECIAL_CHARS))));
        }
      } else {
        // Throw error, we need an OIS
        throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.org_identity_sources.1'))));
      }
    }
    
    return parent::calculateImpliedCoId($data);
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
    
    // Add a new CO Group OIS Mapping?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Group OIS Mapping?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Group OIS Mapping?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Group OIS Mapping?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Group OIS Mapping?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v2.0.0
   * @return Array An array suitable for use in $this->paginate
   */
  
  public function paginationConditions() {
    // Get a pointer to our model
    $req = $this->modelClass;
    
    $ret = array();
    
    if(!empty($this->request->params['named']['org_identity_source'])) {
      // Only retrieve members of the current CO
      $ret['conditions'][$req.'.org_identity_source_id'] = $this->request->params['named']['org_identity_source'];
    }
    
    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v2.0.0
   */
  
  function performRedirect() {
    // Redirect to index with org_identity_source_id
    
    if(!empty($this->viewVars['vv_ois_id'])) {
      $this->redirect(array(
        'action' => 'index',
        'org_identity_source' => $this->viewVars['vv_ois_id']
      ));
    } else {
      parent::performRedirect();
    }
  }
}