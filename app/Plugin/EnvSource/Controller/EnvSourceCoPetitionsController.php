<?php
/**
 * COmanage Registry Env Source Co Petitions Controller
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoPetitionsController', 'Controller');

class EnvSourceCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "EnvSourceCoPetitions";

  public $uses = array("CoPetition",
                       "OrgIdentitySource",
                       "EnvSource.EnvSource",
                       "EnvSource.EnvSourceBackend");
  
  public $in_reauth = false;

  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: If invalid enrollment flow provided, session flash message set
   *
   * @since  COmanage Registry v3.1.0
   */
  
  function beforeFilter() {
    // We need some special authorization logic here, depending on the type of flow.
    // This is loosely based on parent::beforeFilter().
    $noAuth = false;
    
    $steps = null;

    if($this->enrollmentFlowID() > -1) {
      $steps = $this->CoPetition->CoEnrollmentFlow->configuredSteps($this->enrollmentFlowID());
    }

    // For self signup, we simply require a token (and for the token to match).
    $petitionerToken = $this->CoPetition->field('petitioner_token', array('CoPetition.id' => $this->parseCoPetitionId()));
    $enrolleeToken = $this->CoPetition->field('enrollee_token', array('CoPetition.id' => $this->parseCoPetitionId()));
    $passedToken = $this->parseToken();
    $enrolleePhase = !empty($steps) && isset($steps[$this->action]) && $steps[$this->action]['role'] == EnrollmentRole::Enrollee;

    if(!(empty($petitionerToken) && empty($enrolleeToken)) && !empty($passedToken)) {
      if(   ($enrolleePhase && $enrolleeToken == $passedToken) 
         || (!$enrolleePhase && $petitionerToken == $passedToken)) {
        // If we were passed a reauth flag, we require authentication even though
        // the token matched. This enables account linking.
        if(!isset($this->request->params['named']['reauth'])
           || $this->request->params['named']['reauth'] != 1) {
          $noAuth = true;
        } else {
          // Store a hint for isAuthorized that we matched the token and are reauthenticating,
          // so we can authorize the transaction.
          
          $this->in_reauth = true;
        }
        
        // Dump the token into a viewvar in case needed
        $this->set('vv_petition_token', $passedToken);
      } else {
        $this->Flash->set(_txt('er.token'), array('key' => 'error'));
        $this->redirect("/");
      }
    }
        
    if($noAuth) {
      $this->Auth->allow($this->action);
      
      if(!$this->Session->check('Auth.User.name')) {
        // If authentication is not required, and we're not authenticated as
        // a valid user, hide the login/logout button to minimize confusion
        
        $this->set('noLoginLogout', true);
      }
    }
    
    // We want our grandparent's beforeFilter to run, but not our parent's,
    // as that will clobber the special authz logic we just implemented.
    StandardController::beforeFilter();
  }
  
  /**
   * Enrollment Flow collectIdentifierIdentify (identify mode)
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $id CO Petition ID
   * @param  Array $oiscfg Array of configuration data for this plugin
   * @param  Array $onFinish URL, in Cake format
   * @param  Integer $actorCoPersonId CO Person ID of actor
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function execute_plugin_collectIdentifierIdentify($id, $oiscfg, $onFinish, $actorCoPersonId) {
    // We'll typically get here in an invitation flow. (Self signup and account
    // linking would get handled by selectOrgIdentityAuthenticate.) So we don't
    // need to run through a logout exercise. We simply try to create a new org
    // identity.
    
    // retrieve() will parse the env variables and create an org identity out
    // of it. However, we need to pass the SORID/$sourceKey to confirm to that
    // backend that we really are trying to create a record.
    
    // First pull our configuration.
    
    $args = array();
    $args['conditions']['EnvSource.org_identity_source_id'] = $oiscfg['OrgIdentitySource']['id'];
    $args['contain'] = false;
    
    $cfg = $this->EnvSource->find('first', $args);
    
    if(empty($cfg)) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array(_txt('ct.env_sources.1'),
                                                    $oiscfg['OrgIdentitySource']['id'])));
    }
    
    if(empty($cfg['EnvSource']['env_identifier_sorid'])) {
      throw new RuntimeException(_txt('er.envsource.sorid.cfg'));
    }
    
    $sorid = getenv($cfg['EnvSource']['env_identifier_sorid']);
    
    if(!$sorid) {
      throw new RuntimeException(_txt('er.envsource.sorid', array($cfg['EnvSource']['env_identifier_sorid'])));
    }
    
    // Pull the target CO Person from the petition so the new Org Identity can be linked
    
    $coPersonId = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));
    
    $newOrgId = $this->OrgIdentitySource->createOrgIdentity($oiscfg['OrgIdentitySource']['id'],
                                                            $sorid,
                                                            $actorCoPersonId,
                                                            $this->cur_co['Co']['id'],
                                                            $coPersonId,
                                                            false,
                                                            $id);
    
    // The step is done

    $this->redirect($onFinish);
  }

  /**
   * Enrollment Flow selectOrgIdentity (authenticate mode)
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $id CO Petition ID
   * @param  Array $oiscfg Array of configuration data for this plugin
   * @param  Array $onFinish URL, in Cake format
   * @param  Integer $actorCoPersonId CO Person ID of actor
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  protected function execute_plugin_selectOrgIdentityAuthenticate($id, $oiscfg, $onFinish, $actorCoPersonId) {
    // See if there is a petitioner_token attached to the petition.
    // If not, the petitioner is logged in and we need to log them
    // out, but first we create a token for re-entry.
    
    $token = $this->CoPetition->field('petitioner_token', array('CoPetition.id' => $id));
    
    // We distinguish self-signup enrollment from account linking by the presence
    // of $token, which will be populated at the start of a self-signup enrollment
    // (since the user will not be registered) and but not at the start of account
    // linking (since the user is already registered).
    if(!$token) {
      // Generate a token and issue a reauthentication redirect
      
      $token = Security::generateAuthKey();
      
      $this->CoPetition->id = $id;
      $this->CoPetition->saveField('petitioner_token', $token);
      
      // This redirect will pass through logout (to terminate the current session)
      // then send us back here (following a re-authentication). While we embed a
      // token into the URL, CoPetitionsController::beforeFilter will ignore it
      // because the settings and step configuration won't permit it. ie: Authentication
      // will be triggered, the token will not be sufficient to bypass it.
      $redirect = array(
        'plugin'     => 'env_source',
        'controller' => 'env_source_co_petitions',
        'action'     => 'selectOrgIdentityAuthenticate',
        $id,
        'oisid'      => $oiscfg['OrgIdentitySource']['id'],
        'token'      => $token,
        // Hint that we're performing account linking
        'reauth'     => '1'
      );
      
      $this->Session->write('Logout.redirect', $redirect);
      
      $this->redirect('/auth/logout');
    }
    
    // Before passing through to a view, we need to clear a confusing Cake generated
    // error message. See UsersController::login() for more information.
    CakeSession::delete('Message.error');
    
    // We simply try to create a new org identity. retrieve() will parse the
    // env variables and create an org identity out of it. However, we need to
    // pass the SORID/$sourceKey to confirm to that backend that we really are
    // trying to create a record.
    
    // First pull our configuration.
    
    $args = array();
    $args['conditions']['EnvSource.org_identity_source_id'] = $oiscfg['OrgIdentitySource']['id'];
    $args['contain'] = false;
    
    $cfg = $this->EnvSource->find('first', $args);
    
    if(empty($cfg)) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array(_txt('ct.env_sources.1'),
                                                    $oiscfg['OrgIdentitySource']['id'])));
    }
    
    if(empty($cfg['EnvSource']['env_identifier_sorid'])) {
      throw new RuntimeException(_txt('er.envsource.sorid.cfg'));
    }
    
    $sorid = getenv($cfg['EnvSource']['env_identifier_sorid']);
    
    if(!$sorid) {
      throw new RuntimeException(_txt('er.envsource.sorid', array($cfg['EnvSource']['env_identifier_sorid'])));
    }
    
    // Do we have a CO Person already associated with this petition?
    // We need this for Pipelines to link to the correct CO Person.
    $petitionCoPersonId = $this->CoPetition->field('enrollee_co_person_id', array('CoPetition.id' => $id));
    
    $newOrgId = $this->OrgIdentitySource->createOrgIdentity($oiscfg['OrgIdentitySource']['id'],
                                                            $sorid,
                                                            $actorCoPersonId,
                                                            $this->cur_co['Co']['id'],
                                                            $petitionCoPersonId,
                                                            false,
                                                            $id);
    
    // The step is done

    $this->redirect($onFinish);
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    $petitionId = $this->parseCoPetitionId();
    $curToken = null;
    
    if($petitionId) {
      $curToken = $this->CoPetition->field('enrollee_token', array('CoPetition.id' => $petitionId));
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Invitation based collection, we need the user in the petition.
    // Note we can't invalidate this token because for the duration of the enrollment
    // $REMOTE_USER may or may not match a valid login identifier (though probably it should).
    $p['collectIdentifierIdentify'] = ($curToken == $this->parseToken());
    
    // Probably an account linking being initiated, so we need a valid user
    $p['selectOrgIdentityAuthenticate'] = $roles['copersonid'] || $this->in_reauth;
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v3.1.0
   */

  function performRedirect() {
    // We don't want our parent's behavior, so always redirect to /.
    $this->redirect('/');
  }
}
