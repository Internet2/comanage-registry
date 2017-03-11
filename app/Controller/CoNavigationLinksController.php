<?php
/**
 * COmanage Registry CO Navigation Links Controller
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
 * @since         COmanage Registry v0.8.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");
  
class CoNavigationLinksController extends StandardController {
  // Class name, used by Cake
  public $name = "CoNavigationLinks";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoNavigationLink.ordr' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;

 /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Auth component is configured 
   *
   * @since  COmanage Registry v0.8.2
   */
  
  function beforeFilter() {
    
    parent::beforeFilter();
    
    // Sub optimally, we need to unlock reorder so that the AJAX calls could get through 
    // for drag/drop reordering.
    // XXX It would be good to be more specific, and just call unlockField()
    // on specific fields, but some initial testing does not make it obvious which
    // fields need to be unlocked.
    $this->Security->unlockedActions = array('reorder');
  }

  /**
   * Get location options for view.
   * - postcondition: vv_link_location_options set
   *
   * @since  COmanage Registry v0.8.2
   */
  
  function beforeRender() {
    //globals
    global $cm_lang, $cm_texts;

    // Pass the location options
    $link_location_options = array(LinkLocationEnum::topBar => $cm_texts[ $cm_lang ]['en.nav.location'][LinkLocationEnum::topBar]);
    $this->set('vv_co_link_location_options', $link_location_options);

    parent::beforeRender();
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.8.2
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Add a new CO link?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing CO Link?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing CO Link?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Reorder Links?
    $p['reorder'] = ($roles['cmadmin'] || $roles['coadmin']);
    $p['order'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View all existing CO Links?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing CO Link?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   *
   * @since  COmanage Registry v0.9.3
   * @return Integer The CO ID if found, or -1 if not
   */
  
  public function parseCOID($data = null) {
    if($this->action == 'order') {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }
    
    return parent::parseCOID();
  }
}