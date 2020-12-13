<?php
/**
 * COmanage Registry Kafka Servers Controller
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class KafkaServersController extends StandardController {
  // Class name, used by Cake
  public $name = "KafkaServers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 50,
    'order' => array(
      'KafkaServer.brokers' => 'asc'
    )
  );
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  public $view_contains = array(
    'Server'
  );
  
  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function beforeRender() {
    // Determine select values from validation rules
    
    $this->set('vv_sasl_mechanisms', $this->KafkaServer->validate['sasl_mechanism']['rule'][1]);
    $this->set('vv_security_protocols', $this->KafkaServer->validate['security_protocol']['rule'][1]);
    
    parent::beforeRender();
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
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Edit an existing SQL Server?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View this SQL Server?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
// XXX maybe we need an intermediate controller for servers?
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.0.0
   */

  function performRedirect() {
    $target = array();
    $target['plugin'] = null;

    if(!empty($this->request->params['pass'][0])) {
      $target['controller'] = 'kafka_servers';
      $target['action'] = 'edit';
      $target[] = filter_var($this->request->params['pass'][0], FILTER_SANITIZE_SPECIAL_CHARS);
    } else {
      $target['controller'] = "servers";
      $target['action'] = 'index';
      $target['co'] = $this->cur_co['Co']['id'];
    }

    $this->redirect($target);
  }
}
