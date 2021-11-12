<?php
/**
 * COmanage Registry Application Preferences Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class ApplicationPreferencesController extends StandardController {
  // We don't want the standard behavior because our 
  public $requires_co = false;
  
  // Class name, used by Cake
  public $name = "ApplicationPreferences";
  
  // Establish pagination parameters for HTML views
  /*
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'description' => 'asc'
    )
  );*/
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Because we accept an unsolicited JSON document (that wasn't generated
    // by FormHelper), we need to unlock store().
    $this->Security->unlockedActions = array('store');
  }
  
  /**
   * Retrieve an Application Preference for the current CO Person.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function retrieve() {
    $coPersonId = $this->Session->read('Auth.User.co_person_id');
    
    // Add a trailing slash if using a dot separated key
    if(!empty($this->request->params['tag'])) {
      $ret = array(
        'co_person_id' => $coPersonId,
        'tag'          => $this->request->params['tag'],
        // value is null if tag not found, or found but null
        'value'        => $this->ApplicationPreference->retrieve($coPersonId, $this->request->params['tag'])
      );
      
      $this->set('vv_ret', $ret);
    } else {
      $this->Api->restResultHeader(400, "No Tag Specified");
    }
    
    $this->RequestHandler->respondAs('json');
    $this->render('json/retrieve', 'json/default');
  }
  
  /**
   * Store an Application Preference for the current CO Person.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function store() {
    $coPersonId = $this->Session->read('Auth.User.co_person_id');
    
    if(!empty($this->request->params['tag'])) {
      // We expect a simple json doc of { "value": "foo" }, but if we don't have
      // a valid value we'll just treat it as a null. The client can also pass
      // { "value": null }
      $value = !empty($this->request->data['value']) ? $this->request->data['value'] : null;
      
      try {
        $this->ApplicationPreference->store($coPersonId, $this->request->params['tag'], $value);
      }
      catch(Exception $e) {
        $this->Api->restResultHeader(500, $e->getMessage());
      }
    } else {
      $this->Api->restResultHeader(400, "No Tag Specified");
    }
    
    // We don't have anything to render in the response body
    $this->autoRender = false;
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    // Since this API is intended to be called from within the browser, we handle
    // things a bit differently. Specifically, we expect a valid session to
    // already exist.
    
    // Note if the current user is a Platform admin, we will store the preferences
    // on the CMP Admin record (ie: for the CO Person associated with the
    // COmanage CO)
    $coPersonId = $this->Session->read('Auth.User.co_person_id');
    
    $self = !empty($coPersonId);
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Retrieve an Application Preference?
    $p['retrieve'] = $self;
    
    // Store an Application Preference?
    $p['store'] = $self;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
