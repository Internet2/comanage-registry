<?php
/**
 * COmanage Registry API Source API Controller
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class ApiController extends Controller {
  // Class name, used by Cake
  public $name = "Api";
  
  // Since we don't extend AppController we need to enumerate the components
  // we want to use.
  public $components = array('Api',
                             'Auth',
                             'RequestHandler');  // For REST
  
  public $uses = array(
// Uncommenting this throws an error about ApiSource, so we use loadModel() below
//    ApiUser
  );
  
  // The API Source record for the current request
  protected $cur_api_src = null;
  
  /**
   * Generate an error result.
   *
   * @since  COmanage Registry v3.3.0
   */
  
  public function abort() {
    $this->set('results', array('error' => 'Invalid request'));
    $this->Api->restResultHeader(400);
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v3.3.0
   */   
    
  public function beforeFilter() {
    // We need to do this manually since we don't call AppController::beforeFilter
    _bootstrap_plugin_txt();
    
    // Need to explicitly load the model, using $uses confuses things
    $this->loadModel('ApiSource.ApiSource');
    $this->loadModel('OrgIdentitySource');
    
    // We want json views in responses
    $this->RequestHandler->renderAs($this, 'json');
    $this->layout = 'ApiSource.json';
    
    // Check authentication
    
    // We need to map the request to an ApiSource configuration so we know which
    // API User to accept. We'll handle both authn and authz here, though
    // plausibly authz could be handled by isAuthorized instead.
    
    $authok = false;
    
    if(!empty($_SERVER['PHP_AUTH_USER'])
       && !empty($_SERVER['PHP_AUTH_PW'])
       && !empty($this->request->params['coid'])
       && !empty($this->request->params['sor'])) {
      // Cake is doing some unexpected auto-joining which breaks changelog
      // on the related models, so make sure we account for ChangelogBehavior
      
      $args = array();
      $args['conditions']['ApiSource.sor_label'] = $this->request->params['sor'];
      $args['joins'][0]['table'] = 'cos';
      $args['joins'][0]['alias'] = 'Co';
      $args['joins'][0]['type'] = 'INNER';
      $args['joins'][0]['conditions'][0] = 'OrgIdentitySource.co_id=Co.id';
      $args['conditions']['Co.id'] = $this->request->params['coid'];
      $args['conditions']['OrgIdentitySource.deleted'] = false;
      $args['conditions']['OrgIdentitySource.org_identity_source_id'] = null;
      $args['contain'] = array('ApiUser', 'OrgIdentitySource');
      
      $this->cur_api_src = $this->ApiSource->find('first', $args);
      
      // If no API User is set, then Push Mode is disabled
      
      if(!empty($this->cur_api_src['ApiUser']['username'])) {
        if(strcmp($_SERVER['PHP_AUTH_USER'], $this->cur_api_src['ApiUser']['username'])==0) {
          // This is similar to the configuration in AppController for general API Auth
          $this->Auth->authenticate = array(
            'Basic' => array(
              'userModel' => 'ApiUser',
              'scope' => array(
                // Only look at active users
                'ApiUser.status' => SuspendableStatusEnum::Active,
                // That don't have validity dates or where the dates are in effect
                'AND' => array(
                  0 => array(
                    'OR' => array(
                      'ApiUser.valid_from IS NULL',
                      'ApiUser.valid_from < ' => date('Y-m-d H:i:s', time())
                    )
                  ),
                  1 => array(
                    'OR' => array(
                      'ApiUser.valid_through IS NULL',
                      'ApiUser.valid_through > ' => date('Y-m-d H:i:s', time())
                    )
                  )
                )
                // We also want to check REMOTE_IP, but there's not a good SQL way
                // to do a regular expression comparison, so we'll do that separately.
                // XXX When migrating to PE, we should do all these checks separately
                // so we can log what failed more clearly.
              ),
              'contain' => false
            )
          );
          
          // XXX It's unclear why, as of Cake 2.3, we need to manually initialize AuthComponent
          $this->Auth->initialize($this);
          
          if($this->Auth->login()) {
            // The authenticated API user must match the sor label
            $username = $this->Auth->user('username');
            
            if(!empty($username)
               && $username == $this->cur_api_src['ApiUser']['username']
               // Maybe check remote IP, if configured
               && (empty($this->cur_api_src['ApiUser']['remote_ip'])
                   || preg_match($this->cur_api_src['ApiUser']['remote_ip'], $_SERVER['REMOTE_ADDR']))) {
              $authok = true;
            }
          }
        }
      }
    }
    
    if(!$authok) {
      $this->Api->restResultHeader(401);
      $this->response->send();
      exit;
    }
  }
  
  /**
   * Handle an SOR Person Role Deleted request.
   *
   * @since  COmanage Registry v3.3.0
   */   
  
  public function delete() {
    // On a delete, we drop the record from our cache, then we trigger a resync.
    
    try {
      // First make sure we have a record
      $this->loadModel('ApiSource.ApiSourceRecord');
      $this->loadModel('OrgIdentitySource');
      
      // We shouldn't get here if params['sorid'] is null
      $args = array();
      $args['conditions']['ApiSourceRecord.api_source_id'] = $this->cur_api_src['ApiSource']['id'];
      $args['conditions']['ApiSourceRecord.sorid'] = $this->request->params['sorid'];
      $args['contain'] = false;
      
      $currec = $this->ApiSourceRecord->find('first', $args);
      
      if(empty($currec)) {
        throw new InvalidArgumentException(_txt('er.apisource.sorid.notfound'));
      }
      
      // Delete the cache record
      $this->ApiSourceRecord->delete($currec['ApiSourceRecord']['id']);
      
      // Trigger the resync
      $this->OrgIdentitySource->syncOrgIdentity($this->cur_api_src['OrgIdentitySource']['id'],
                                                $this->request->params['sorid']);
      
      // Done, return success
      $this->Api->restResultHeader(200);
    }
    catch(InvalidArgumentException $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(404);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(500);
    }
  }
  
  /**
   * Handle a Get SOR Person Role request.
   *
   * @since  COmanage Registry v3.3.0
   */   
  
  public function get() {
    // We basically just pull the current record and return it.
    // We could inject some metadata (modified time, etc) but currently we don't.
    
    try {
      $this->loadModel('ApiSource.ApiSourceRecord');
      
      // We shouldn't get here if params['sorid'] is null
      $args = array();
      $args['conditions']['ApiSourceRecord.api_source_id'] = $this->cur_api_src['ApiSource']['id'];
      $args['conditions']['ApiSourceRecord.sorid'] = $this->request->params['sorid'];
      $args['contain'] = false;
      
      $currec = $this->ApiSourceRecord->find('first', $args);
      
      if(empty($currec)) {
        throw new InvalidArgumentException(_txt('er.apisource.sorid.notfound'));
      }
      
      // Done, return success
      $this->set('results', json_decode($currec['ApiSourceRecord']['source_record']));
      $this->Api->restResultHeader(200);
    }
    catch(InvalidArgumentException $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(404);
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(500);
    }
  }
  
  /**
   * Handle an SOR Person Role Added or Updated request.
   *
   * @since  COmanage Registry v3.3.0
   */   
  
  public function upsert() {
    try {
      // Do we already have an ApiSourceRecord for this SOR Key?
      $this->loadModel('ApiSource.ApiSourceRecord');
      
      // Read the JSON document, Based on code in ApiComponent
      $fh = fopen('php://input', 'r');
      $doc = stream_get_contents($fh);
      fclose($fh);

      $json = json_decode($doc, true);
      
      // We accept a "returnUrl" option for use when sync on add triggers an
      // enrollment flow, however we do not want it to become part of the
      // source_record, since it won't be provided on future updates.
      
      $returnUrl = (!empty($json['returnUrl']) ? $json['returnUrl'] : null);
      
      if($returnUrl) {
        // We have to remove it from $json and re-encode doc
        
        unset($json['returnUrl']);
      }
      
      // We shouldn't get here if params['sorid'] is null
      $args = array();
      $args['conditions']['ApiSourceRecord.api_source_id'] = $this->cur_api_src['ApiSource']['id'];
      $args['conditions']['ApiSourceRecord.sorid'] = $this->request->params['sorid'];
      $args['contain'] = false;
      
      $currec = $this->ApiSourceRecord->find('first', $args);
      
      $rec = array(
        'api_source_id' => $this->cur_api_src['ApiSource']['id'],
        'sorid' => $this->request->params['sorid'],
        // Since we may have mucked with $json for returnUrl above, we don't know
        // if the original document was "pretty" or not. For consistency, we'll
        // always make the source_record pretty (which should also make it slightly)
        // easier for an admin to look at it.
        'source_record' => json_encode($json, JSON_PRETTY_PRINT)
      );
      
      $this->ApiSourceRecord->clear();
      
      if(!empty($currec)) {
        // Update, though it's possible an update for us still needs to trigger a
        // createOrgIdentity() call...
        
        $rec['id'] = $currec['ApiSourceRecord']['id'];
      } else {
        // Insert
      }
      
      // Save the new or updated record
      // We currently don't bother diff'ing the record to see if it changed, but we could
      $this->ApiSourceRecord->save($rec);

      // Do we already have an OIS Record for this sorid?
      $args = array();
      $args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $this->cur_api_src['OrgIdentitySource']['id'];
      $args['conditions']['OrgIdentitySourceRecord.sorid'] = $this->request->params['sorid'];
      $args['contain'] = false;
      
      $this->loadModel('OrgIdentitySource');
      
      $curoisrec = $this->OrgIdentitySource->OrgIdentitySourceRecord->find('first', $args);
      
      $orgId = null;
      
      if(!empty($curoisrec)) {
        // Update
        
        $info = $this->OrgIdentitySource->syncOrgIdentity($this->cur_api_src['OrgIdentitySource']['id'],
                                                          $this->request->params['sorid']);
        
        $orgId = $info['id'];
        
        $this->Api->restResultHeader(200);
      } else {
        // Create
        
        $orgId = $this->OrgIdentitySource->createOrgIdentity($this->cur_api_src['OrgIdentitySource']['id'],
                                                             $this->request->params['sorid'],
                                                             null,
                                                             $this->cur_api_src['OrgIdentitySource']['co_id']);
        
        if($returnUrl) {
          $this->loadModel('CoPetition');
          
          // Try to find the CO Petition ID associated with this Org Identity.
          // If found, insert the return URL.
          $coPetitionId = $this->CoPetition->field('id', array('CoPetition.enrollee_org_identity_id' => $orgId));
          
          if($coPetitionId) {
            $this->CoPetition->clear();
            $this->CoPetition->id = $coPetitionId;
            $this->CoPetition->saveField('return_url', $returnUrl);
          }
          // XXX else?
        }
        
        // Note we return 201 based on whether or not an org identity was created,
        // not based on whether or not we saved the SOR attributes. While a bit of
        // an edge case, it might be more correct to tie it to $rec['id'] already
        // being set.
        $this->Api->restResultHeader(201);
      }
      
      if($orgId) {
        // We always return identifiers, since on a 200 we might have generated new ones
        $this->loadModel('Identifier');
        $results = array();
        
        $args = array();
        $args['joins'][0]['table'] = 'co_people';
        $args['joins'][0]['alias'] = 'CoPerson';
        $args['joins'][0]['type'] = 'INNER';
        $args['joins'][0]['conditions'][0] = 'Identifier.co_person_id=CoPerson.id';
        $args['joins'][1]['table'] = 'co_org_identity_links';
        $args['joins'][1]['alias'] = 'CoOrgIdentityLink';
        $args['joins'][1]['type'] = 'INNER';
        $args['joins'][1]['conditions'][0] = 'CoPerson.id=CoOrgIdentityLink.co_person_id';
        $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $orgId;
        $args['conditions']['Identifier.status'] = StatusEnum::Active;
        $args['contain'] = false;
        
        $ids = $this->Identifier->find('all', $args);
        
        foreach($ids as $id) {
          $results['identifiers'][] = array(
            'identifier' => $id['Identifier']['identifier'],
            'type' => $id['Identifier']['type']
          );
        }
        
        $this->set('results', $results);
      }
    }
    catch(Exception $e) {
      $this->set('results', array('error' => $e->getMessage()));
      $this->Api->restResultHeader(500);
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    // Currently we do all meaningful checks in beforeFilter().
    
    return true;
  }
}
