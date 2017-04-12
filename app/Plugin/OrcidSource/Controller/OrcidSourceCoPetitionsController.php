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
    // First pull our ORCID configuration
    
    $args = array();
    $args['conditions']['OrcidSource.id'] = $oiscfg['OrgIdentitySource']['id'];
    $args['contain'] = false;
    
    $cfg = $this->OrcidSource->find('first', array('Orcid'));
    
    if(empty($cfg)) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array(_txt('ct.orcid_sources.1'),
                                                    $oiscfg['OrgIdentitySource']['id'])));
    }
    
    // Construct the callback URL, needed for both the initial query and
    // exchanging the code for a response
    
    $callback = $this->OrcidSourceBackend->callbackUrl();
    
    // Append the petition ID to the callback
    $callback[] = $id;
    $callback['oisid'] = $oiscfg['OrgIdentitySource']['id'];
    $redirectUri = Router::url($callback, array('full' => true));
  
    if(empty($this->request->query['code'])) {
      // First time through, redirect to the authorize URL
      
      $url = $this->OrcidSourceBackend->orcidUrl('auth') . "/oauth/authorize?";
      $url .= "client_id=" . $cfg['OrcidSource']['clientid'];
      $url .= "&response_type=code&scope=/authenticate";
      $url .= "&redirect_uri=" . urlencode($redirectUri);
   
      $this->redirect($url);
    }

    // Else we're back from an OAuth request, exchange the code for an access token and ORCID

    try {
      // Exchange the code for an access token and ORCID ID
      
      $response = $this->OrcidSourceBackend->exchangeCode($redirectUri,
                                                          $cfg['OrcidSource']['clientid'],
                                                          $cfg['OrcidSource']['client_secret'],
                                                          $this->request->query['code']);
      
    
      // There's lots of data in here we're ignoring at the moment:
      // access_token, token_type, refresh_token, expires_in, scope, name
      // It looks like the access_token could be stored and used to refresh the user's data,
      // though atm we just do that with our OrcidSource level access token.
      
      $orcid = $response->orcid;
      
      // Now that we have the ORCID, create an Org Identity to store it.
          
      // selectEnrollee hasn't run yet so we can't pull the target CO Person from the
      // petition, but for OISAuthenticate, it's the current user (ie: $actorCoPersonId)
      // that we always want to link to.
      
      $newOrgId = $this->OrgIdentitySource->createOrgIdentity($oiscfg['OrgIdentitySource']['id'],
                                                              $orcid,
                                                              $actorCoPersonId,
                                                              $this->cur_co['Co']['id'],
                                                              $actorCoPersonId);
      
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