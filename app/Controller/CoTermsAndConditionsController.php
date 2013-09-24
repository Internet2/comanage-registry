<?php
/**
 * COmanage Registry CO Terms and Conditions Controller
 *
 * Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoTermsAndConditionsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoTermsAndConditions";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoTermsAndConditions.description' => 'asc'
    )
  );
  
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
        
        if($this->restful) {
          // XXX CO-698
        } else {
          $this->Session->setFlash(_txt('rs.tc.agree.ok'), '', array(), 'success');        
        }
      }
      catch(Exception $e) {
        if($this->restful) {
          // XXX CO-698
        } else {
          $this->Session->setFlash($e->getMessage(), '', array(), 'error');
        }
      }
      
      if(!$this->restful) {
        // Perform redirect to CO Person view
        
        $args = array('controller' => 'co_people',
                      'action'     => 'edit',
                      $copersonid,
                      'co'         => $this->cur_co['Co']['id']);
        
        $this->redirect($args);
      }
    } else {
      if($this->restful) {
        // XXX CO-698
      } else {
        $this->Session->setFlash($e->getMessage(), '', array(), 'error');
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
    if(!$this->restful) {
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
    $args['contain'][] = 'Name';
    
    $this->set('vv_co_person', $this->Co->CoPerson->find('first', $args));
  }
}
