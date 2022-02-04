<?php
/**
 * COmanage Registry Service Eligibilities Controller
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class ServiceEligibilitiesController extends StandardController {
  // Class name, used by Cake
  public $name = "ServiceEligibilities";
  
  public $uses = array('ServiceEligibilityEnroller.ServiceEligibility', 
                       'ServiceEligibilityEnroller.ServiceEligibilitySetting',
                       'CoPersonRole');
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'co_service_id' => 'asc'
    )
  );

  // This controller needs a CO Person to be set
  public $requires_person = true;
  
  public $delete_contains = array();
  
  /**
   * Add a new Service Eligibility.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  public function add() {
    // Unlike standard add(), we only get here via form
    
    if($this->request->is('post')
       && !empty($this->request->data['ServiceEligibility']['co_person_role_id'])
       && !empty($this->request->data['ServiceEligibility']['co_service_id'])) {
      // Process add, then redirect back
      
      try {
        $this->ServiceEligibility->add($this->request->data['ServiceEligibility']['co_person_role_id'],
                                       $this->request->data['ServiceEligibility']['co_service_id'],
                                       $this->Session->read('Auth.User.co_person_id'));
        
        $this->Flash->set(_txt('rs.saved'), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
      
      // We need the CoPerson ID to redirect to
      $coPersonId = $this->ServiceEligibility->CoPersonRole->field('co_person_id', array('CoPersonRole.id' => $this->request->data['ServiceEligibility']['co_person_role_id']));
      
      $this->redirect(array(
        'plugin'      => 'service_eligibility_enroller',
        'controller'  => 'service_eligibilities',
        'action'      => 'index',
        'copersonid'  => $coPersonId
      ));
    }
  }
  
  /**
   * Render the available set of Service Eligibilities for the requested CO Person.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  public function index() {
    // This is a bit different from other index views. We pull the list of
    // available services, collate it with the set of selected services, and
    // give the user a list of add/remove options.
    
    if(empty($this->request->params['named']['copersonid'])) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('ct.co_people.1'))));
    }
    
    // Map the requested person to a CO
    $coId = $this->ServiceEligibility->CoPersonRole->CoPerson->findCoForRecord($this->request->params['named']['copersonid']);

    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.co.specify'));
    }
    
    // Pull the Settings for this CO
    $args = array();
    $args['conditions']['ServiceEligibilitySetting.co_id'] = $coId;
    $args['contain'] = array('Co');
    
    $this->set('vv_settings', $this->ServiceEligibilitySetting->find('first', $args));
    
    // Compatibility with breadcrumbs, etc
    $this->set('cur_co', $this->viewVars['vv_settings']);

    // Pull the available services
    try {
      $services = $this->ServiceEligibility->availableServices($coId);
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
      $this->performRedirect();
    }
    
    $this->set('vv_available_services', $services);
    
    // Pull the roles for this person (and the person)
    // for now, regardless of status
    $args = array();
    $args['conditions']['CoPerson.id'] = $this->request->params['named']['copersonid'];
    $args['contain'] = array('CoPersonRole', 'PrimaryName');
    
    $this->set('vv_co_person', $this->ServiceEligibility->CoPersonRole->CoPerson->find('all', $args));
    
    // Pull the set of current service eligibilities for this CO Person
    $this->set('vv_eligibilities', $this->ServiceEligibility->servicesByRole($this->request->params['named']['copersonid']));
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v4.1.0
   * @return Integer The CO ID if found, or -1 if not
   */

  public function parseCOID($data = null) {
    if($this->action == 'index') {
      if(!empty($this->request->params['named']['copersonid'])) {
        // Map the CO Person to a CO
        $coId = $this->ServiceEligibility->CoPersonRole->CoPerson->field('co_id', array('CoPerson.id' => $this->request->params['named']['copersonid']));

        if($coId) {
          return $coId;
        }
      }
    }

    return parent::parseCOID();
  }
  
  /**
   * Remove a Service Eligibility.
   * 
   * @since  COmanage Registry v4.1.0
   */
  
  public function remove() {
    // We only get here via form
    
    if($this->request->is('post')
       && !empty($this->request->data['ServiceEligibility']['co_person_role_id'])
       && !empty($this->request->data['ServiceEligibility']['co_service_id'])) {
      // Process remove, then redirect back
      
      try {
        $this->ServiceEligibility->remove($this->request->data['ServiceEligibility']['co_person_role_id'],
                                          $this->request->data['ServiceEligibility']['co_service_id'],
                                          $this->Session->read('Auth.User.co_person_id'));
        
        $this->Flash->set(_txt('rs.saved'), array('key' => 'success'));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
      
      // We need the CoPerson ID to redirect to
      $coPersonId = $this->ServiceEligibility->CoPersonRole->field('co_person_id', array('CoPersonRole.id' => $this->request->data['ServiceEligibility']['co_person_role_id']));
      
      $this->redirect(array(
        'plugin'      => 'service_eligibility_enroller',
        'controller'  => 'service_eligibilities',
        'action'      => 'index',
        'copersonid'  => $coPersonId
      ));
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    $self = ($this->action == 'index'
             && !empty($roles['copersonid'])
             && !empty($this->request->params['named']['copersonid'])
             && $roles['copersonid'] == $this->request->params['named']['copersonid']);
    
    // Figure out which roles the current user can manage. This does NOT include
    // $self, since a user can't edit their own Eligibilities.
    $managedRoles = array();
    
    if($roles['cmadmin'] || $roles['coadmin'] || $roles['couadmin']) {
      $subjectRoles = array();
      
      if($this->action == 'index') {
        // Map the requested person to their roles
        
        $args = array();
        $args['conditions']['CoPersonRole.co_person_id'] = $this->request->params['named']['copersonid'];
        $args['fields'] = array('id', 'cou_id');
        $args['contain'] = false;
        
        $subjectRoles = $this->CoPersonRole->find('list', $args);
      } else {
        // There is only one CO Person Role of interest, and it's in the form data
        
        $args = array();
        $args['conditions']['CoPersonRole.id'] = $this->request->data['ServiceEligibility']['co_person_role_id'];
        $args['fields'] = array('id', 'cou_id');
        $args['contain'] = false;
        
        $subjectRoles = $this->CoPersonRole->find('list', $args);
      }
      
      if($roles['couadmin'] && $roles['copersonid']) {
        // Remove any roles the COU Admin isn't responsible for
        
        foreach($subjectRoles as $coprid => $couid) {
          if($this->Role->isCouAdmin($roles['copersonid'], $couid)) {
            $managedRoles[] = $coprid;
          }
        }
      } else {
        $managedRoles = array_keys($subjectRoles);
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a Service Eligibility for a CO Person?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin'] || !empty($managedRoles));
    
    // View Service Eligibilities for a CO Person?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin'] || !empty($managedRoles) || $self);
    // Manage Service Eligibilities for specific CO Person Roles?
    // This is not a separate view, but is used by index to determine read/write
    $p['managed'] = $managedRoles;
    
    // Remove a Service Eligibility for a CO Person?
    $p['remove'] = ($roles['cmadmin'] || $roles['coadmin'] || !empty($managedRoles));
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
