<?php
/**
 * COmanage Registry Paper Token Controller
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SAMController", "Controller");

class PaperTokensController extends SAMController {
  // Class name, used by Cake
  public $name = "PaperTokens";

  public $requires_person = true;

  public $view_contains = array(
    'PrivacyIdeaAuthenticator' => array('Server')
  );

  /**
   * Add action to be used when adding a PaperToke as part of an Enrollment Flow
   *
   * @since COmanage Registry v4.4.0
   */

  public function add() {
    $this->setAction('generate');
  }

  /**
   * Generate a Paper Token (backup codes)
   *
   * @since  COmanage Registry v4.4.0
   */

  public function generate() {

    if(!$this->request->is('get')) {
      throw new MethodNotAllowedException();
    } else {
      parent::add();

      $this->set('title_for_layout', 'Generated Backup Codes');

      if(!empty($this->request->params['named']['onFinish'])) {
        $this->set('vv_on_finish_url', $this->request->params['named']['onFinish']);
      }

      try {
        $tokenInfo = $this->PrivacyIdea->createToken($this->viewVars['vv_authenticator']['PrivacyIdeaAuthenticator'],
                                                     $this->viewVars['vv_co_person']['CoPerson']['id']);

        $this->set('vv_token_info', $tokenInfo);

        $newdata = array(
          'PaperToken' => array(
            'co_person_id' => $this->viewVars['vv_co_person']['CoPerson']['id'],
            'serial' => $tokenInfo['serial']
          )
        );

        $this->generateHistory('generate', $newdata, array());

        if(!empty($tokenInfo['otps'])) {
          $this->set('vv_otps', (array)$tokenInfo['otps']);
        }
      }
      catch(Exception $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    }
  }

  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v4.4.0
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
   * @since  COmanage Registry v4.4.0
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */

  function checkDeleteDependencies($curdata) {
    // Remove the Token from the server. This should really happen in
    // PaperToken::beforeDelete(), but for some reason (probably some weird
    // namespace management issue) that callback isn't being called. It's also
    // slightly easier to make the API call from the controller (using the
    // PrivacyIdea model). Ultimately, this will need to be rewritten for
    // Cake 4.

    // We need the Serial ID, not the token ID

    if(empty($curdata['PaperToken']['serial'])) {
      throw new InvalidArgumentException(_txt('er.notprov.id', array(_txt('pl.privacyideaauthenticator.fd.serial'))));
    }

    $this->PrivacyIdea->deleteToken($this->viewVars['vv_authenticator']['PrivacyIdeaAuthenticator'],
                                    $curdata['PaperToken']['serial']);

    return true;
  }

  /**
   * Generate history records for a transaction. This method is intended to be
   * overridden by model-specific controllers, and will be called from within a
   * try{} block so that HistoryRecord->record() may be called without worrying
   * about catching exceptions.
   *
   * @since  COmanage Registry v4.4.0
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

    switch($action) {
      case 'generate':
        $cstr = _txt('rs.generated-a2', array(_txt('ct.paper_tokens.1'), $newdata['PaperToken']['serial']));
        $cop = $newdata['PaperToken']['co_person_id'];
        $act = PrivacyIDEActionEnum::TokenGenerated;
        break;
      case 'delete':
        $cstr = _txt('rs.deleted-a2', array(_txt('ct.paper_tokens.1'), $olddata['PaperToken']['serial']));
        $cop = $olddata['PaperToken']['co_person_id'];
        $act = PrivacyIDEActionEnum::TokenDeleted;
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
   * @since  COmanage Registry v4.4.0
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

    $p['generate'] = isset($p['manage']) ? $p['manage'] : false; 

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
