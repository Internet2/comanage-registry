<?php
/**
 * COmanage Registry Match Server Attributes Controller
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
  
class MatchServerAttributesController extends StandardController {
  // Class name, used by Cake
  public $name = "MatchServerAttributes";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'MatchServerAttribute.attribute' => 'asc'
    )
  );
  
  public $uses = array('MatchServerAttribute',
                       'CoExtendedType');
  
  // We don't directly require a CO, but indirectly we do.
  public $requires_co = true;
  
  public $edit_contains = array();
  
  public $view_contains = array();

  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  function beforeRender() {
    parent::beforeRender();
    
    if(!$this->request->is('restful')) {
      $msid = null;
      
      if(!empty($this->params->named['matchserver'])) {
        $msid = $this->params->named['matchserver'];
      } elseif(!empty($this->request->data['MatchServerAttribute']['match_server_id'])) {
        $msid = $this->request->data['MatchServerAttribute']['match_server_id'];
      }
      
      $this->set('vv_msid', $msid);
      
      // To get the Match Server description, we have to walk all the way back
      // to the Server
      
      if($msid) {
        $serverId = $this->MatchServerAttribute->MatchServer->field('server_id', array('MatchServer.id' => $msid));
        
        if($serverId) {
          $this->set('vv_server_desc', $this->MatchServerAttribute->MatchServer->Server->field('description', array('Server.id' => $serverId)));
        }
      }
      
      // Pull the set of attributes available for the match service and
      // structure them suitably for the view
      
      $supportedAttrs = $this->MatchServerAttribute->supportedAttributes();
      
      $attrs = array();
      $attrTypes = array();
      
      foreach($supportedAttrs as $a => $acfg) {
        $attrs[$a] = $acfg['label'];
        
        if($acfg['type']) {
          $attrTypes[$a] = $this->CoExtendedType->active($this->cur_co['Co']['id'], $acfg['type']);
        }
      }
      
      $this->set('vv_available_attributes', $attrs);
      $this->set('vv_available_attribute_types', $attrTypes);
    }
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.0.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId($data = null) {
    // If an enrollment flow is specified, use it to get to the CO ID
    
    $msid = null;
    
    if(!empty($this->params->named['matchserver'])) {
      $msid = $this->params->named['matchserver'];
    } elseif(!empty($this->request->data['MatchServerAttribute']['match_server_id'])) {
      $msid = $this->request->data['MatchServerAttribute']['match_server_id'];
    }
    
    if($msid) {
      // Map Match Server to Server to CO
      
      $server = $this->MatchServerAttribute->MatchServer->field('server_id', array('id' => $msid));
      
      if(!$server) {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.match_servers.1'), $msid)));
      }
      
      $coId = $this->MatchServerAttribute->MatchServer->Server->field('co_id', array('id' => $server));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.servers.1'), $server)));
      }
    }
    
    // Or try the default behavior
    return parent::calculateImpliedCoId();
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
    
    // Add a new Match Server Attribute?
    $p['add'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Delete an existing Match Server Attribute?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // Edit an existing Match Server Attribute?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View all existing Match Server Attributes?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Match Server Attribute?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array An array suitable for use in $this->paginate
   */
  
  function paginationConditions() {
    // Only retrieve attributes in the current match server
    
    $ret = array();
    
    $ret['conditions']['MatchServerAttribute.match_server_id'] = $this->request->params['named']['matchserver'];
    
    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.0.0
   */
  
  function performRedirect() {
    // Append the Match Server ID to the redirect
    
    $msid = null;
    
    if(isset($this->request->data['MatchServerAttribute']['match_server_id']))
      $msid = $this->request->data['MatchServerAttribute']['match_server_id'];
    elseif(isset($this->request->params['named']['matchserver']))
      $msid = filter_var($this->request->params['named']['matchserver'],FILTER_SANITIZE_SPECIAL_CHARS);
    
    $this->redirect(array('controller' => 'match_server_attributes',
                          'action' => 'index',
                          'matchserver' => $msid));
  }
}
