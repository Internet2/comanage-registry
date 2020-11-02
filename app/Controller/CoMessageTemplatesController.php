<?php
/**
 * COmanage Registry CO Message Templates Controller
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
  
class CoMessageTemplatesController extends StandardController {
  // Class name, used by Cake
  public $name = "CoMessageTemplates";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'CoMessageTemplate.description' => 'asc'
    )
  );

  // This controller needs a CO to be set
  public $requires_co = true;

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: post request should be of type ajax
   *
   * @since  COmanage Registry v4.0.0
   */
  public function beforeFilter() {
      if( $this->request->is('ajax') ) {
          $this->Security->unlockedActions = array('test');
      }
      // Since we're overriding, we need to call the parent to run the authz check
      parent::beforeFilter();
  }

  /**
   * Generate and send a test email to provided recepient
   *
   * @since  COmanage Registry v4.0.0
   */
  public function test() {
    // Parse the Tempate id from the request
    if(empty($this->request->params['named']['cfg'])) {
      $this->response->body(_txt('er.mt.unknown', array("Configuration")));
      $this->response->statusCode(204);
      return $this->response;
    }
    if(!empty($this->request->data["input"])) {
      $email = filter_var($this->request->data["input"], FILTER_SANITIZE_EMAIL);
      if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->response->body(_txt('er.mt.invalid', array("Email Format")));
        $this->response->statusCode(406);
        return $this->response;
      }
    } else {
      $this->response->body(_txt('er.mt.unknown', array("Email Recepient")));
      $this->response->statusCode(204);
      return $this->response;
    }

    $this->CoMessageTemplate->templateTest($this->request->params['named']['cfg'], $this->request->data["input"]);
    $this->response->body(_txt('er.mt.msg', array($this->request->data["input"])));
    $this->response->statusCode(200);
    return $this->response;
  }
  
  /**
   * Duplicate an existing Message Template
   * - postcondition: Redirect issued
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id CO Message Template ID
   */
  
  public function duplicate($id) {
    try {
      $this->CoMessageTemplate->duplicate($id);
      $this->Flash->set(_txt('rs.copy-a1', array(_txt('ct.co_message_templates.1'))), array('key' => 'success'));
    }
    catch(Exception $e) {
      $this->Flash->set($e->getMessage(), array('key' => 'error'));
    }
    
    $this->performRedirect();
  }

  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v4.0.0
   * @return Integer The CO ID if found, or -1 if not
   */

  public function parseCOID($data = null) {
    // Define Controller's own actions
    if($this->action == 'test') {
      if(!empty($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }

    return parent::parseCOID($data);
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
    
    // Add a new Message Template?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Message Template?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Duplicate an existing Message Template?
    $p['duplicate'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Message Template?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View all existing Message Templates?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Message Template?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Test/Preview an existing Message Template?
    $p['test'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}