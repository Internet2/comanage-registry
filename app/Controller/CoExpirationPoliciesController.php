<?php
/**
 * COmanage Registry CO Expiration Policies Controller
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
  
class CoExpirationPoliciesController extends StandardController {
  // Class name, used by Cake
  public $name = "CoExpirationPolicies";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoExpirationPolicy.description' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v0.9.2
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Dynamically adjust validation rules to include the current CO ID for dynamic types.
    
    $vrule = $this->CoExpirationPolicy->validate['act_affiliation']['content']['rule'];
    $vrule[1]['coid'] = $this->cur_co['Co']['id'];
    
    $this->CoExpirationPolicy->validator()->getField('act_affiliation')->getRule('content')->rule = $vrule;
    
    $vrule = $this->CoExpirationPolicy->validate['cond_affiliation']['content']['rule'];
    $vrule[1]['coid'] = $this->cur_co['Co']['id'];
    
    $this->CoExpirationPolicy->validator()->getField('cond_affiliation')->getRule('content')->rule = $vrule;
  }
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   *
   * @since  COmanage Registry v0.9.2
   */
  
  function beforeRender() {
    if(!$this->request->is('restful')) {
      $this->set('vv_copr_affiliation_types', $this->CoExpirationPolicy->Co->CoPerson->CoPersonRole->types($this->cur_co['Co']['id'], 'affiliation'));
      $this->set('vv_cous', $this->CoExpirationPolicy->Co->Cou->allCous($this->cur_co['Co']['id']));
      
      $args = array();
      $args['conditions']['CoGroup.co_id'] = $this->cur_co['Co']['id'];
      $args['order'] = array('CoGroup.name ASC');
      
      $this->set('vv_co_groups', $this->CoExpirationPolicy->Co->CoGroup->find("list", $args));
      
      // Provide a list of message templates
      $args = array();
      $args['conditions']['co_id'] = $this->cur_co['Co']['id'];
      $args['conditions']['status'] = SuspendableStatusEnum::Active;
      $args['conditions']['context'] = MessageTemplateEnum::ExpirationNotification;
      $this->set('vv_message_templates',
                 $this->CoExpirationPolicy->ActNotifyMessageTemplate->find('list', $args));
    }
    
    parent::beforeRender();
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
    
    // Add a new Expiration Policy?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Expiration Policy?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Expiration Policy?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Expiration Policy?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Expiration Policy?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}