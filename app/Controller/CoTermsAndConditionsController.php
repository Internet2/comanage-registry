<?php
/**
 * COmanage Registry CO Terms and Conditions Controller
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
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class CoTermsAndConditionsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoTermsAndConditions";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoTermsAndConditions.ordr' => 'asc'
    )
  );
  
  // When using $uses, include the Controller's model first
  public $uses = array("CoTermsAndConditions", "CoSetting");
 
  // This controller needs a CO to be set
  public $requires_co = true;

  /**
   * Agree to T&C
   * - precondition: The named parameter 'copersonid' is populated with the target CO Person ID
   * - postcondition: Redirect issued
   *
   * @since  COmanage Registry v0.8.3
   * @param  Integer CO Terms and Agreements identifier
   */
  
  public function agree($id) {
    if(!empty($this->request->params['named']['copersonid'])) {
      $copersonid = $this->request->params['named']['copersonid'];
      
      try {
        $this->CoTermsAndConditions->CoTAndCAgreement->record($id,
                                                              $copersonid,
                                                              $this->Session->read('Auth.User.co_person_id'),
                                                              $this->Session->read('Auth.User.username'));
        
        if($this->request->is('restful')) {
          // XXX CO-698
        } else {
          $this->Flash->set(_txt('rs.tc.agree.ok'), array('key' => 'success')); 
        }
      }
      catch(Exception $e) {
        if($this->request->is('restful')) {
          // XXX CO-698
        } else {
          $this->Flash->set($e->getMessage(), array('key' => 'error'));
        }
      }
      
      if(!$this->request->is('restful')) {
        if($this->CoSetting->getTAndCLoginMode($this->cur_co['Co']['id']) == TAndCLoginModeEnum::RegistryLogin) {
          // Update the session cache set by UsersController
          
          $pending = $this->CoTermsAndConditions->pending($copersonid);
          
          if(!empty($pending)) {
            $this->Session->write('Auth.User.tandc.pending.' . $this->cur_co['Co']['id'], $pending);
          } else {
            $this->Session->delete('Auth.User.tandc.pending.' . $this->cur_co['Co']['id']);
            
            if(!empty($this->request->params['named']['mode'])
               && $this->request->params['named']['mode'] == 'login') {
              // We're done with the required T&C at login, so redirect to dashboard
              
              $args = array(
                'controller' => 'co_dashboards',
                'action'     => 'dashboard',
                'co'         => $this->cur_co['Co']['id']
              );
              
              $this->redirect($args);
            }
          }
        }
        
        // Perform redirect back to list of T&C
        
        $args = array('controller' => 'co_terms_and_conditions',
                      'action'     => 'review',
                      'copersonid' => $copersonid);
        
        if(!empty($this->request->params['named']['mode'])) {
          $args['mode'] = $this->request->params['named']['mode'];
        }
        
        $this->redirect($args);
      }
    } else {
      if($this->request->is('restful')) {
        // XXX CO-698
      } else {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
        $this->performRedirect();
      }
    }
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   * - postcondition: $cous may be set.
   *
   * @since  COmanage Registry v0.8.3
   */
  
  function beforeRender() {
    if(!$this->request->is('restful')) {
      $this->set('cous', $this->Co->Cou->allCous($this->cur_co['Co']['id'], "hash"));
    }
    
    parent::beforeRender();
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.8.3
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Is this our own record?
    $self = false;
    
    if($roles['comember'] && $roles['copersonid']
       && (isset($this->request->params['named']['copersonid'])
           && ($roles['copersonid'] == $this->request->params['named']['copersonid']))) {
      $self = true;
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Terms and Conditions?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Agree to CO Terms and Conditions? (admins can agree on behalf of people)
    $p['agree'] = ($roles['cmadmin'] || $roles['coadmin'] || $self);
    
    // Delete an existing CO Terms and Conditions?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Terms and Conditions?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing CO Terms and Conditions?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Review an individual's CO Terms and Conditions (and agreements)?
    $p['review'] = ($roles['cmadmin'] || $roles['coadmin'] || $self);
    
    // View an existing CO Terms and Conditions?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit an existing CO Terms and Condition's order?
    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Modify ordering for display via AJAX
    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Pull T&C for review
   * - precondition: The named parameter 'copersonid' is populated with the target CO Person ID
   * - postcondition: $vv_co_terms_and_conditions set with calculated permissions
   *
   * @since  COmanage Registry v0.8.3
   */
  
  public function review() {
    // Retrieve the set of T&Cs
    $this->set('vv_co_terms_and_conditions',
               $this->CoTermsAndConditions->status($this->params['named']['copersonid']));
    
    // And also this CO Person
    $args = array();
    $args['conditions']['CoPerson.id'] = $this->params['named']['copersonid'];
    $args['contain'][] = 'PrimaryName';
    
    $this->set('vv_co_person', $this->Co->CoPerson->find('first', $args));
  }

  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array $data Array of data for calculating implied CO ID
   * @return Integer The CO ID if found, or -1 if not
   */

  function parseCOID($data = null) {
    if ($this->action == 'order'
      || $this->action == 'reorder') {
      if (isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }

    return parent::parseCOID();
  }
}
