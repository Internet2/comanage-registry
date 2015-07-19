<?php
/**
 * COmanage Registry CO Settings Controller
 *
 * Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("StandardController", "Controller");

class CoSettingsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoSettings";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoSettings.co_id' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  /**
   * Add a CO Setting
   *
   * @since  COmanage Registry v0.9.1
   */
  
  public function add() {
    // add() here deviates a bit from the typical behavior, since CO Setting has
    // a 1-1 relationship to CO. We first check to see if there is an existing
    // CO Setting record for the current CO. If there isn't we create it. Then,
    // we redirect to edit(), which behaves more normally.
    
    $settingId = null;
    
    $args = array();
    $args['conditions']['CoSetting.co_id'] = $this->cur_co['Co']['id'];
    $args['contain'] = false;
    
    $c = $this->CoSetting->find('first', $args);
    
    if($c) {
      $settingId = $c['CoSetting']['id'];
    } else {
      // Create the record.
      
      try {      
        $settingId = $this->CoSetting->addDefaults($this->cur_co['Co']['id']);
      }
      catch(Exception $e) {
        $this->Session->setFlash($e->getMessage(), '', array(), 'error');
        $this->redirect('/');
      }
    }
    
    // Redirect to edit
    
    $this->redirect(array('action' => 'edit', $settingId));
  }
  
  /**
   * Update a CO Setting.
   * - precondition: Model specific attributes in $this->request->data (optional)
   * - precondition: <id> must exist
   * - postcondition: On GET, $<object>s set (HTML)
   * - postcondition: On POST success, object updated
   * - postcondition: On POST, session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: On POST error, $invalid_fields set (REST)
   *
   * @since  COmanage Registry v0.9.1
   * @param  integer Object identifier (eg: cm_co_groups:id) representing object to be retrieved
   */
  
  public function edit($id) {
    parent::edit($id);
    
    // Create a more useful title
    $this->set('title_for_layout', _txt('op.edit-f',
                                        array(_txt('ct.co_settings.pl'),
                                              $this->viewVars['co_settings'][0]['Co']['name'])));
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   *
   * @since  COmanage Registry v0.9.1
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId() {
    // if add(), accept what's in the URL
    
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Generate a display key to be used in messages such as "Item Added".
   *
   * @since  COmanage Registry v0.9.1
   * @param  Array A cached object (eg: from prior to a delete)
   * @return string A string to be included for display.
   */
  
  function generateDisplayKey($c = null) {
    // In this case, we always use the string "CO Settings"
    
    return _txt('ct.co_settings.pl');
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
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO Setting set?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit CO Settings?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v0.9.1
   */
  
  function performRedirect() {
    // Back to the edit view we go...
    
    $this->redirect(array('action' => 'edit',
                          $this->viewVars['co_settings'][0]['CoSetting']['id']));
    
  }
}
