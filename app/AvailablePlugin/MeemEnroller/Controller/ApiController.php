<?php
/**
 * COmanage Registry Meem Enroller API Controller
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
  
  // The Meem Configuration for the current request
  protected $cur_meem = null;
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   *
   * @since  COmanage Registry v4.0.0
   * @todo   Merge with ApiSource into a common library
   */   
    
  public function beforeFilter() {
    // We need to do this manually since we don't call AppController::beforeFilter
    _bootstrap_plugin_txt();
    
    // Need to explicitly load the model, using $uses confuses things
    $this->loadModel('MeemEnroller.MeemEnroller');
    
    // We want json views in responses
    $this->RequestHandler->renderAs($this, 'json');
    $this->layout = 'MeemEnroller.json';
    
    // Check authentication
    
    // This is a bit simpler than ApiSource, since the requested configuration
    // is in the url. We'll handle both authn and authz here, though plausibly
    // authz could be handled by isAuthorized instead.
    
    $authok = false;
    
    if(!empty($_SERVER['PHP_AUTH_USER'])
       && !empty($_SERVER['PHP_AUTH_PW'])
       && !empty($this->request->params['meemenrollerid'])) {
      // Cake is doing some unexpected auto-joining which breaks changelog
      // on the related models, so make sure we account for ChangelogBehavior
      
      $args = array();
      $args['conditions']['MeemEnroller.id'] = $this->request->params['meemenrollerid'];
      $args['contain'] = array('ApiUser');
      
      $this->cur_meem = $this->MeemEnroller->find('first', $args);
      
      // If no API User is set, then the API is disabled
      
      if(!empty($this->cur_meem['ApiUser']['username'])) {
        if(strcmp($_SERVER['PHP_AUTH_USER'], $this->cur_meem['ApiUser']['username'])==0) {
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
               && $username == $this->cur_meem['ApiUser']['username']
               // Maybe check remote IP, if configured
               && (empty($this->cur_meem['ApiUser']['remote_ip'])
                   || preg_match($this->cur_meem['ApiUser']['remote_ip'], $_SERVER['REMOTE_ADDR']))) {
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
   * Handle a Status request.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function status() {
    // We basically just pull the current record and return it.
    // We could inject some metadata (modified time, etc) but currently we don't.
    
    try {
      $this->loadModel('MeemEnroller.MeemMfaStatus');
      $this->loadModel('CoGroupMember');
      $this->loadModel('Identifier');
      
      $ret = array();
      
      // We should have an identifier, map it to a CO Person. We can get the
      // CO ID from the ApiUser.
      
      $args = array();
      $args['conditions']['CoPerson.co_id'] = $this->cur_meem['ApiUser']['co_id'];
      $args['conditions']['CoPerson.status'] = array(StatusEnum::Active, StatusEnum::GracePeriod);
      $args['conditions']['Identifier.identifier'] = $this->request->params['identifier'];
      $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = array('CoPerson');
      
      $id = $this->Identifier->find('first', $args);
      
      if(!$id) {
        throw new InvalidArgumentException(_txt('er.meemenroller.api.coperson'));
      }
      
      // Pull the MFA Status
      
      $args = array();
      $args['conditions']['MeemMfaStatus.meem_enroller_id'] = $this->cur_meem['MeemEnroller']['id'];
      $args['conditions']['MeemMfaStatus.co_person_id'] = $id['CoPerson']['id'];
      $args['contain'] = false;
      
      $ret['mfa_status'] = $this->MeemMfaStatus->find('all', $args);
      
      // And also if the CO Person is in the exemption group
      
      if($this->cur_meem['MeemEnroller']['mfa_exempt_co_group_id']
         && $this->CoGroupMember->isMember($this->cur_meem['MeemEnroller']['mfa_exempt_co_group_id'], $id['CoPerson']['id'])) {
        $args = array();
        
        $ret['mfa_exempt'] = $this->CoGroupMember->field('valid_through',
                                                         array(
                                                           'CoGroupMember.co_group_id' => $this->cur_meem['MeemEnroller']['mfa_exempt_co_group_id'],
                                                           'CoGroupMember.co_person_id' => $id['CoPerson']['id']
                                                         ));
      } else {
        $ret['mfa_exempt'] = false;
      }
      
      // Done, return success
      $this->set('results', $ret);
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
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    // Currently we do all meaningful checks in beforeFilter().
    
    return true;
  }
}
