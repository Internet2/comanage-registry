<?php
/**
 * COmanage Registry Totp Token Controller
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SAMController", "Controller");

class TotpTokensController extends SAMController {
  // Class name, used by Cake
  public $name = "TotpTokens";
  
  public $requires_person = true;
  
  public $view_contains = array(
    'PrivacyIdeaAuthenticator' => array('Server')
  );
  
  /**
   * Add a Standard Object.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function add() {
    if($this->request->is('get')) {
      parent::add();
      
      $this->set('title_for_layout', _txt('op.add-a', array($this->viewVars['vv_authenticator']['Authenticator']['description'])));
      
      if(!empty($this->request->params['named']['onFinish'])) {
        $this->set('vv_on_finish_url', $this->request->params['named']['onFinish']);
      }
      
      try {
        // As a potential RFE, we could require a button to be pressed before
        // creating the token
        $tokenInfo = $this->PrivacyIdea->createToken($this->viewVars['vv_authenticator']['PrivacyIdeaAuthenticator'],
                                                     $this->viewVars['vv_co_person']['CoPerson']['id']);
        
        $this->set('vv_token_info', $tokenInfo);
        
        $newdata = array(
          'TotpToken' => array(
            'co_person_id' => $this->viewVars['vv_co_person']['CoPerson']['id'],
            'serial' => $tokenInfo['serial']
          )
        );
        
        $this->generateHistory('add', $newdata, array());
        
        if(!empty($tokenInfo['qr_data'])) {
          // The QR data needed to register the token in the app
          $this->set('vv_qr_data', $tokenInfo['qr_data']);
        }
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    } else {
      try {
        // Verify that the user has successfully registered the token in their
        // app by validating a TOTP value
        $this->PrivacyIdea->confirmToken($this->viewVars['vv_authenticator']['PrivacyIdeaAuthenticator'],
                                         $this->request->data['TotpToken']['co_person_id'],
                                         $this->request->data['TotpToken']['serial'],
                                         $this->request->data['TotpToken']['totp_value']);
        
        $newdata = array(
          'TotpToken' => array(
            'co_person_id' => $this->request->data['TotpToken']['co_person_id'],
            'serial' => $this->request->data['TotpToken']['serial']
          )
        );
        
        $this->generateHistory('confirm', $newdata, array());
        
        if(!empty($this->request->data['TotpToken']['onFinish'])) {
          // Redirect back into the enrollment flow
          
          $this->redirect(urldecode($this->request->data['TotpToken']['onFinish']));
        }
        
        $this->Flash->set(_txt('pl.privacyideaauthenticator.token.confirmed'), array('key' => 'success'));
        $this->redirect(array(
          'plugin' => null,
          'controller' => 'authenticators',
          'action' => 'status',
          'copersonid' => $this->viewVars['vv_co_person']['CoPerson']['id']
        ));
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
        
        $this->set('vv_token_info', $this->request->data['TotpToken']);
        
        if(!empty($this->request->data['TotpToken']['onFinish'])) {
          $this->set('vv_on_finish_url', $this->request->data['TotpToken']['onFinish']);
        }
      }
    }
  }

  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   */

  public function beforeFilter() {
    // We operate as a virtual controller, and tweak the settings to pull
    // records for the token type that this instantiation is configured for
    
    $this->uses[] = 'PrivacyIdeaAuthenticator.PrivacyIdea';
    
    parent::beforeFilter();
  }
  
  /**
   * Perform any dependency checks required prior to a delete operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v4.0.0
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */

  function checkDeleteDependencies($curdata) {
    // Remove the TotpToken from the server. This should really happen in
    // TotpToken::beforeDelete(), but for some reason (probably some weird
    // namespace management issue) that callback isn't being called. It's also
    // slightly easier to make the API call from the controller (using the
    // PrivacyIdea model). Ultimately, this will need to be rewritten for
    // Cake 4.
    
    // We need the Serial ID, not the TotpToken ID.
    
    if(empty($curdata['TotpToken']['serial'])) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('pl.privacyideaauthenticator.fd.serial'))));
    }

    $return_response = $this->PrivacyIdea->deleteToken($this->viewVars['vv_authenticator']['PrivacyIdeaAuthenticator'],
	    $curdata['TotpToken']['serial']);

    // error code 601 indicates the token was not found in the Privacy Idea database. We want to continue on and delete it in Registry, however.
    if(isset($return_response->result->error->code) && $return_response->result->error->code == 601) {
      $this->Flash->set(_txt('pl.privacyideaauthenticator.token.deletednoprivacyidea'), array('key' => 'information'));
    }
    
    return true;
  }
  
  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v4.0.0
   * @param  String Controller action causing the change
   * @param  Array Data provided as part of the action (for add/edit)
   * @param  Array Previous data (for delete/edit)
   * @return boolean Whether the function completed successfully (which does not necessarily imply history was recorded)
   */

  public function generateHistory($action, $newdata, $olddata) {
    // Build a change string
    $cstr = "";
    $cop = null;
    $act = null;
    
    // XXX Hard coded for TotpToken
    
    switch($action) {
      case 'add':
        $cstr = _txt('rs.added-a2', array(_txt('ct.totp_tokens.1'), $newdata['TotpToken']['serial']));
        $cop = $newdata['TotpToken']['co_person_id'];
        $act = PrivacyIDEActionEnum::TokenAdded;
        break;
      case 'confirm':
        $cstr = _txt('rs.confirmed-a2', array(_txt('ct.totp_tokens.1'), $newdata['TotpToken']['serial']));
        $cop = $newdata['TotpToken']['co_person_id'];
        $act = PrivacyIDEActionEnum::TokenConfirmed;
        break;
      case 'delete':
        $cstr = _txt('rs.deleted-a2', array(_txt('ct.totp_tokens.1'), $olddata['TotpToken']['serial']));
        $cop = $olddata['TotpToken']['co_person_id'];
        $act = PrivacyIDEActionEnum::TokenDeleted;
        break;
      case 'edit':
        $cstr = _txt('rs.edited-a2', array(_txt('ct.totp_tokens.1'), $newdata['TotpToken']['serial']));
        $cop = $newdata['TotpToken']['co_person_id'];
        $act = PrivacyIDEActionEnum::TokenEdited;
        break;
    }
  
    $this->Co->CoPerson->HistoryRecord->record($cop,
                                               null,
                                               null,
                                               $this->Session->read('Auth.User.co_person_id'),
                                               $act,
                                               $cstr);

    return true;
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
    
    // Merge in the permissions calculated by our parent
    $p = array_merge($p, $this->calculateParentPermissions(true));
    
    // Tokens can't be edited, only deleted
    $p['edit'] = false;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
