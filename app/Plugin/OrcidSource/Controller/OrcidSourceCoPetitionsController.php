<?php
/**
 * COmanage Registry ORCID Source Co Petitions Controller
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoPetitionsController', 'Controller');

class OrcidSourceCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "OrcidSourceCoPetitions";

  public $uses = array("CoPetition",
                       "Oauth2Server",
                       "OrgIdentitySource",
                       "OrcidSource",
                       "OrcidSource.OrcidSourceBackend");

  /**
   * Enrollment Flow selectOrgIdentity (authenticate mode)
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer $id CO Petition ID
   * @param  Array $oiscfg Array of configuration data for this plugin
   * @param  Array $onFinish URL, in Cake format
   * @param  Integer $actorCoPersonId CO Person ID of actor
   */
  
  protected function execute_plugin_selectOrgIdentityAuthenticate($id, $oiscfg, $onFinish, $actorCoPersonId) {
    // First pull our Oauth2Server configuration
    
    $args = array();
    $args['joins'][0]['table'] = 'servers';
    $args['joins'][0]['alias'] = 'Server';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Oauth2Server.server_id=Server.id';
    $args['joins'][1]['table'] = 'orcid_sources';
    $args['joins'][1]['alias'] = 'OrcidSource';
    $args['joins'][1]['type'] = 'INNER';
    $args['joins'][1]['conditions'][0] = 'OrcidSource.server_id=Server.id';
    $args['conditions']['OrcidSource.org_identity_source_id'] = $oiscfg['OrgIdentitySource']['id'];
    $args['contain'] = false;
    
    $cfg = $this->Oauth2Server->find('first', $args);
    
    if(empty($cfg)) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array(_txt('ct.orcid_sources.1'),
                                                    $oiscfg['OrgIdentitySource']['id'])));
    }
    
    // We need a different callback URL than what the Oauth2Server config will
    // use, since we're basically creating a runtime Authorization Code flow
    // (while the main config uses a Client Credentials flow).
    
    $callback = array(
      'plugin'     => 'orcid_source',
      'controller' => 'orcid_source_co_petitions',
      'action'     => 'selectOrgIdentityAuthenticate',
      $id,
      'oisid'      => $oiscfg['OrgIdentitySource']['id']
    );
    
    // Build the redirect URI
    $redirectUri = Router::url($callback, array('full' => true));
    
    if(empty($this->request->query['code'])) {
      // First time through, redirect to the authorize URL
      
      $url = $cfg['Oauth2Server']['serverurl'] . "/authorize?";
      $url .= "client_id=" . $cfg['Oauth2Server']['clientid'];
      $url .= "&response_type=code&scope=/authenticate";
      $url .= "&redirect_uri=" . urlencode($redirectUri);
      
      $this->redirect($url);
    }

    // Else we're back from an OAuth request, exchange the code for an access token and ORCID

    try {
      // Exchange the code for an access token and ORCID ID
      
      $response = $this->Oauth2Server->exchangeCode($cfg['Oauth2Server']['id'],
                                                    $this->request->query['code'],
                                                    $redirectUri,
                                                    false);
    
      // There's lots of data in here we're ignoring at the moment:
      // access_token, token_type, refresh_token, expires_in, scope, name
      // It looks like the access_token could be stored and used to refresh the user's data,
      // though atm we just do that with our Oauth2Server level access token.
      
      $orcid = $response->orcid;
      
      // Now that we have the ORCID, create an Org Identity to store it.
          
      // selectEnrollee hasn't run yet so we can't pull the target CO Person from the
      // petition, but for OISAuthenticate, it's the current user (ie: $actorCoPersonId)
      // that we always want to link to.
      
      $newOrgId = $this->OrgIdentitySource->createOrgIdentity($oiscfg['OrgIdentitySource']['id'],
                                                              $orcid,
                                                              $actorCoPersonId,
                                                              $this->cur_co['Co']['id'],
                                                              $actorCoPersonId,
                                                              true,
                                                              $id);
      
      // Record the ORCID into History and Petition History
      $this->CoPetition->EnrolleeOrgIdentity->HistoryRecord->record($actorCoPersonId,
                                                                    null,
                                                                    $newOrgId,
                                                                    $actorCoPersonId,
                                                                    ActionEnum::CoPersonOrgIdLinked,
                                                                    _txt('pl.orcidsource.linked', array($orcid)));
      
      $this->CoPetition->CoPetitionHistoryRecord->record($id,
                                                         $actorCoPersonId,
                                                         PetitionActionEnum::IdentityLinked,
                                                         _txt('pl.orcidsource.linked', array($orcid)));
    }
    catch(Exception $e) {
      // This might happen if (eg) the ORCID is already in use
      throw new RuntimeException($e->getMessage());
    }

    // The step is done

    $this->redirect($onFinish);
  }
}