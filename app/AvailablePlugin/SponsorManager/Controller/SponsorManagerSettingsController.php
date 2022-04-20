<?php
/**
 * COmanage Registry Sponsor Manager Settings Controller
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class SponsorManagerSettingsController extends StandardController {
  // Class name, used by Cake
  public $name = "SponsorManagerSettings";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'co_id' => 'asc'
    )
  );

  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v4.1.0
   */

  function beforeRender() {
    parent::beforeRender();

    if(!$this->request->is('restful')) {
      // Pull the available email types
      $this->set('vv_email_types', $this->Co->CoPerson->EmailAddress->types($this->cur_co['Co']['id'], 'type'));

      // Pull the available identifier types
      $this->set('vv_identifier_types', $this->Co->CoPerson->Identifier->types($this->cur_co['Co']['id'], 'type'));
    }
  }
  
  /**
   * Render Namespace Assigner Settings for this CO.
   *
   * @since  COmanage Registry v4.1.0
   */
  
  public function index() {
    // This operates a bit like CO Settings... basically we insert a row for
    // this CO if there isn't one, then we redirect to edit for that row.
    // This is basically copied from the Service Eligibility Enroller.
    
    $settingId = $this->SponsorManagerSetting->field('id', array('SponsorManagerSetting.co_id' => $this->cur_co['Co']['id']));
    
    if(!$settingId) {
      // Create the row
      
      $settings = array(
        'co_id'            => $this->cur_co['Co']['id'],
        'lookahead_window' => 14,
        'renewal_term'     => 365
      );
      
      $this->SponsorManagerSetting->clear();
      $this->SponsorManagerSetting->save($settings);
      
      $settingId = $this->SponsorManagerSetting->id;
    }
    
    $this->redirect(
      array(
        'action' => 'edit',
        $settingId
      )
    );
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
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Edit an existing Sponsor Manager Setting?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Sponsor Manager Setting?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Sponsor Manager Setting?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
