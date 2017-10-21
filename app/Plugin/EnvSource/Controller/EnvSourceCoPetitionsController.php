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
        'token'      => $token
      );
      
      $this->Session->write('Logout.redirect', $redirect);
      
      $this->redirect('/auth/logout');
    }
    
    // Verify the token
    if(empty($this->request->params['named']['token'])
       || $token != $this->request->params['named']['token']) {
      throw new InvalidArgumentException(_txt('er.envsource.token'));
    }
    
    // Clear the token
    $this->CoPetition->id = $id;
    $this->CoPetition->saveField('petitioner_token', null);

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
    
    // selectEnrollee hasn't run yet so we can't pull the target CO Person from the
    // petition, but for OISAuthenticate, it's the current user (ie: $actorCoPersonId)
    // that we always want to link to.
      
    $newOrgId = $this->OrgIdentitySource->createOrgIdentity($oiscfg['OrgIdentitySource']['id'],
                                                            $sorid,
                                                            $actorCoPersonId,
                                                            $this->cur_co['Co']['id'],
                                                            $actorCoPersonId);
    
    // The step is done

    $this->redirect($onFinish);
  }
}