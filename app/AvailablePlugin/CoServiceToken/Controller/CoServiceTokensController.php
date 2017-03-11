<?php
/**
 * COmanage Registry CO Services Tokens Controller
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

class CoServiceTokensController extends StandardController {
  // Class name, used by Cake
  public $name = "CoServiceTokens";

  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'co_person_id' => 'asc'
    )
  );
  
  // This controller needs a CO Person to be set
  public $requires_person = true;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v2.0.0
   */

  public function beforeFilter() {
    parent::beforeFilter();
    
    $this->CoServiceToken->CoService->bindModel(array('hasOne' =>
                                                      array('CoServiceTokenSetting')),
                                                false);
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.8.5
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = NULL) {
    if(!empty($this->request->params['named']['copersonid'])) {
      $coId = $this->CoServiceToken->CoPerson->field('co_id',
                                                     array('id' => $this->request->params['named']['copersonid']));

      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_people.1'),
                                                      filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
    }
    
    return parent::calculateImpliedCoId($data);
  }
  
  /**
   * Generate a CO Service Token.
   *
   * @since  COmanage Registry v2.0.0
   */

  public function generate() {
    // Pull the service configuration
    
    if(empty($this->request->params['named']['tokensetting'])) {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('ct.co_service_token_settings.1'))),
                        array('key' => 'error'));
      $this->performRedirect();
    }
    
    if(empty($this->request->params['named']['copersonid'])) {
      $this->Flash->set(_txt('er.notprov.id', array(_txt('ct.co_people.1'))),
                        array('key' => 'error'));
      $this->performRedirect();
    }
    
    $args = array();
    $args['conditions']['CoServiceTokenSetting.id'] = $this->request->params['named']['tokensetting'];
// XXX Not sure why containable isn't picking up the relation...
//    $args['contain'][] = 'CoService';
    
    $tokenSetting = $this->CoServiceToken->CoService->CoServiceTokenSetting->find('first', $args);
    
    if(!empty($tokenSetting)) {
      $args = array();
      $args['conditions']['CoService.id'] = $tokenSetting['CoServiceTokenSetting']['co_service_id'];
      $args['contain'] = false;
      
      $this->set('vv_co_service', $this->CoServiceToken->CoService->find('first', $args));
    }
    
    $this->set('vv_co_person_id', $this->request->params['named']['copersonid']);
    $this->set('vv_token', $this->CoServiceToken->generate($this->request->params['named']['copersonid'],
                                                           $tokenSetting['CoServiceTokenSetting']['co_service_id'],
                                                           $tokenSetting['CoServiceTokenSetting']['token_type'],
                                                           $this->Session->read('Auth.User.co_person_id')));
    $this->set('title_for_layout', _txt('ct.co_service_tokens.1'));
  }
  
  /**
   * Obtain all CO Service Tokens.
   * - postcondition: $<object>s set on success (REST or HTML), using pagination (HTML only)
   * - postcondition: HTTP status returned (REST)
   * - postcondition: Session flash message updated (HTML) on suitable error
   *
   * @since  COmanage Registry v2.0.0
   */

  public function index() {
    parent::index();
    
    $args = array();
    $args['conditions']['CoService.co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['CoService.status'] = SuspendableStatusEnum::Active;
    $args['conditions']['CoServiceTokenSetting.enabled'] = true;
    $args['order'][] = 'CoService.name ASC';
    $args['contain'][] = 'CoServiceTokenSetting';
    
    $this->set('vv_co_services', $this->CoServiceToken->CoService->find('all', $args));
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
    
    $self = (!empty($roles['copersonid'])
             && !empty($this->request->params['named']['copersonid'])
             && ($roles['copersonid'] == $this->request->params['named']['copersonid']));
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();

    // Determine what operations this user can perform
    
    // Generate a CO Service Tokens (for this CO Person)?
    $p['generate'] = ($roles['cmadmin'] || $roles['coadmin']) || $self;

    // View all existing CO Service Tokens (for this CO Person)?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']) || $self;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  /**
   * Determine the conditions for pagination of the index view, when rendered via the UI.
   *
   * @since  COmanage Registry v2.0.0
   * @return Array An array suitable for use in $this->paginate
   * @throws InvalidArgumentException
   */

  function paginationConditions() {
    // We only want Settings attached to CO Services that are in our CO
    
    $ret = array();
    $ret['joins'][0]['table'] = 'co_services';
    $ret['joins'][0]['alias'] = 'CoService';
    $ret['joins'][0]['type'] = 'INNER';
    $ret['joins'][0]['conditions'][0] = 'CoServiceTokenSetting.co_service_id=CoService.id';
    $ret['conditions'][0]['CoService.co_id'] = $this->cur_co['Co']['id'];
    
    return $ret;
  }
  
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v2.0.0
   */
  
  public function performRedirect() {
    if(!empty($this->request->params['named']['copersonid'])) {
      $this->redirect(array(
        'plugin'     => 'co_service_token',
        'controller' => 'co_service_tokens',
        'action'     => 'index',
        'copersonid' => filter_var($this->request->params['named']['copersonid'], FILTER_SANITIZE_SPECIAL_CHARS)
      ));
    } else {
      $this->redirect('/');
    }
  }
}