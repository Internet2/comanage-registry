<?php
/**
 * COmanage Registry CO Services Controller
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

class CoServicesController extends StandardController {
  // Class name, used by Cake
  public $name = "CoServices";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoServices.name' => 'asc'
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
    
    if($this->action == 'portal') {
      // We need the list of services to see if we should allow anonymous
      // access to this view. We'll also need it when we render the view,
      // so we'll set the view var here.
      
      // We may or may not have a current CO Person ID
      $coPersonId = $this->Session->read('Auth.User.co_person_id');
      
      // Note the drop menu will generally also have to call this (on most
      // page loads), so we'll probably be pulling data that we've already
      // pulled.
      
      $services = $this->CoService->findServicesByPerson($this->Role,
                                                         $this->cur_co['Co']['id'],
                                                         $coPersonId,
                                                         (!empty($this->request->params['named']['cou'])
                                                          ? $this->request->params['named']['cou']
                                                          : null));
      
      // We use co_services rather than vv_co_services to be consistent
      // with the index view
      $this->set('co_services', $services);
      
      if(!empty($services)) {
        if(!$coPersonId) {
          // Allow anonymous access
          
          $this->Auth->allow('portal');
        } else {
          // Pull the list of group memberships for the current Person.
          
          $memberGroups = $this->CoService->CoGroup->findForCoPerson($coPersonId, null, null, null, false);
          
          if($memberGroups) {
            $this->set('vv_member_groups', Hash::extract($memberGroups, '{n}.CoGroup.id'));
          }
          
          // Pull the list of open groups.
          
          $args = array();
          $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
          $args['conditions']['CoGroup.open'] = true;
          $args['fields'] = array('id');
          $args['contain'] = false;
          
          $openGroups = $this->CoService->CoGroup->find('list', $args);
          
          if($openGroups) {
            $this->set('vv_open_groups', $openGroups);
          }
        }
      }
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   *
   * @since  COmanage Registry v2.0.0
   */
  
  function beforeRender() {
    if(!$this->request->is('restful')
       // We don't need this for portal view, since we pull stuff in beforefilter
       && $this->action != 'portal') {
      // Pull the list of available groups
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['CoGroup.status'] = SuspendableStatusEnum::Active;
      $args['order'] = array('CoGroup.name ASC');

      $this->set('vv_co_groups', $this->Co->CoGroup->find("list", $args));
      
      // and COUs
      $args = array();
      $args['conditions']['Cou.co_id'] = $this->cur_co['Co']['id'];
      $args['order'] = array('Cou.name ASC');

      $this->set('vv_cous', $this->Co->Cou->find("list", $args));
    }
    
    parent::beforeRender();
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
    
    // Add a new CO Service?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Service?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Service?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Service?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Join the group associated with a CO Service?
    $p['join'] = $roles['comember'];
    
    // Leave the group associated with a CO Service?
    $p['leave'] = $roles['comember'];
    
    // View the CO Service Portal?
    $p['portal'] = true;
    
    // View an existing CO Service?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Join the CO Group associated with the specified service
   *
   * @since  COmanage Registry v3.1.0
   * @param  $id CO Service ID
   */
  
  public function join($id) {
    try {
      $this->CoService->setServiceGroupMembership($id,
                                                  $this->Role,
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  'join');
      
      $this->Flash->set(_txt('rs.svc.grmem'), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
  
  /**
   * Leave the CO Group associated with the specified service
   *
   * @since  COmanage Registry v3.1.0
   * @param  $id CO Service ID
   */
  
  public function leave($id) {
    try {
      $this->CoService->setServiceGroupMembership($id,
                                                  $this->Role,
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  $this->Session->read('Auth.User.co_person_id'),
                                                  'leave');
      
      $this->Flash->set(_txt('rs.svc.grmem'), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set(_txt('rs.svc.grmem'), array('key' => 'error'));
    }
    
    $this->performRedirect();
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v2.0.0
   * @return Integer The CO ID if found, or -1 if not
   */

  public function parseCOID($data = null) {
    if($this->action == 'portal') {
      if(!empty($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
      
      if(!empty($this->request->params['named']['cou'])) {
        // Map the COU to a CO
        $coId = $this->CoService->Cou->field('co_id', array('Cou.id' => $this->request->params['named']['cou']));
        
        if($coId) {
          return $coId;
        }
      }
    }

    return parent::parseCOID();
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.1.0
   */

  function performRedirect() {
    if($this->action == 'join'
       || $this->action == 'leave') {
      // Redirect to the portal
      
      $args = array(
        'controller' => 'co_services',
        'action'     => 'portal'
      );
      
      if(!empty($this->request->params['named']['cou'])) {
        // This was an advisory parameter. We don't do anything with it
        // other than pass it back into the redirect... if someone dorked
        // with it findServiceByPerson() will still do the right thing.
        
        $args['cou'] = filter_var($this->request->params['named']['cou'],FILTER_SANITIZE_SPECIAL_CHARS);
      } else {
        $args['co'] = $this->cur_co['Co']['id'];
      }
      
      $this->redirect($args);
    }
    
    parent::performRedirect();
  }
  
  /**
   * Render the CO Service Portal.
   *
   * @since  COmanage Registry v2.0.0
   */
  
  public function portal() {
    $this->set('title_for_layout', _txt('ct.co_services.pl'));
  }
}
