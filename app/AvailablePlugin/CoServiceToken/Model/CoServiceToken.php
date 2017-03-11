<?php
/**
 * COmanage Registry CO Service Token Model
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

class CoServiceToken extends AppModel {
  // Define class name for cake
  public $name = "CoServiceToken";
  
  // Required by COmanage Plugins
  // To enable this plugin (even though it doesn't do anything), change the type to 'enroller'
  public $cmPluginType = "other";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoPerson",
    "CoService"
  );
  
  public $actsAs = array('Containable',
                         'Provisioner');
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Validation rules for table elements
  public $validate = array(
    'co_service_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'token' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'token_type' => array(
      'rule' => array('inList', array(CoServiceTokenTypeEnum::Plain08,
                                      CoServiceTokenTypeEnum::Plain15)),
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @since  COmanage Registry v2.0.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array(
      "coconfig" => array(_txt('ct.co_service_token_settings.pl') =>
                          array('controller' => 'co_service_token_settings',
                                'action'     => 'configure')),
      "coperson" => array(_txt('ct.co_service_tokens.pl') =>
                          array('controller' => "co_service_tokens",
                                'action'     => 'index'))
    );
  }
  
  /**
   * Generate a CO Service Token.
   *
   * @since  COmanage Registry v2.0.0
   * @param  Integer                $coPersonId      CO Person to generate token for
   * @param  Integer                $coServiceId     CO Service to generate token for
   * @param  CoServiceTokenTypeEnum $tokenType       Type of token to generate
   * @param  Integer                $actorCoPersonId CO Person ID of actor sending the notification
   * @throws LogicException
   * @throws RuntimeException
   */
  
  public function generate($coPersonId,
                           $coServiceId,
                           $tokenType=CoServiceTokenTypeEnum::Plain15,
                           $actorCoPersonId=null) {
    $token = null;
    
    switch($tokenType) {
      case CoServiceTokenTypeEnum::Plain08:
      case CoServiceTokenTypeEnum::Plain15:
        // Note we use Security::randomBytes() rather than php random_bytes, which was not added until 7.0
        $token = substr(preg_replace("/[^a-zA-Z0-9]+/", "", base64_encode(Security::randomBytes(30))),
                        0,
                        (integer)$tokenType);
        break;
      default:
        throw new LogicException(_txt('er.notimpl'));
        break;
    }
    
    if(!$token) {
      throw new RuntimeException(_txt('er.coservicetoken.fail'));
    }
    
    // Is there already a token?
    $args = array();
    $args['conditions']['CoServiceToken.co_service_id'] = $coServiceId;
    $args['conditions']['CoServiceToken.co_person_id'] = $coPersonId;
    $args['contain'] = false;
    
    $curToken = $this->find('first', $args);
    
    $newToken = array(
      'co_service_id' => $coServiceId,
      'co_person_id'  => $coPersonId,
      'token'         => $token,
      'token_type'    => $tokenType
    );
    
    if(!empty($curToken['CoServiceToken']['id'])) {
      $newToken['id'] = $curToken['CoServiceToken']['id'];
    }
    
    $this->clear();
    
    if(!$this->save($newToken)) {
      throw new RuntimeException(_txt('er.db.save-a', array('CoServiceToken')));
    }
    
    // Pull the service name for the history record
    
    $serviceName = $this->CoService->field('name', array('CoService.id' => $coServiceId));
    
    // Cut history
    $this->CoPerson->HistoryRecord->record($coPersonId,
                                           null,
                                           null,
                                           $actorCoPersonId,
                                           // XXX If this moves to core code, assign ActionEnums
                                           'XTOK',
                                           _txt('pl.coservicetoken.history', array($serviceName,
                                                                                   _txt('en.coservicetoken.tokentype', null, $tokenType))));
    
    return $token;
  }
}
