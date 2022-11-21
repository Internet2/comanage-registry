<?php
/**
 * COmanage Registry Authenticator Reset Token Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class AuthenticatorResetToken extends AppModel {
  // Define class name for cake
  public $name = "AuthenticatorResetToken";

  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');

  // Association rules from this model to other models
  public $belongsTo = array(
    "CoRecoveryWidget",
    "CoPerson"
  );

  // Default display field for cake generated views
  public $displayField = "co_person_id";

  // Validation rules for table elements
  public $validate = array(
    'co_recovery_widget_id' => array(
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
      'required' => true,
      'allowEmpty' => false
    ),
    'expires' => array(
      'rule' => '/.*/',  // The 'date' rule is too constraining
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Generate an Authenticator Reset Token.
   *
   * @since  COmanage Registry v4.1.0
   * @param  int    $coRecoveryWidgetId   CO Dashboard Widget ID
   * @param  int    $coPersonId           CO Person ID
   * @param  int    $tokenValidity        Reset Token validity, in minutes
   * @return string                       Reset Token
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  public function generate($coRecoveryWidgetId, $coPersonId, $tokenValidity) {
    // Toss any previous reset tokens. We need to fire callbacks for ChangelogBehavior.
    $args = array(
      'AuthenticatorResetToken.co_recovery_widget_id'   => $coRecoveryWidgetId,
      'AuthenticatorResetToken.co_person_id'            => $coPersonId
    );
    
    $this->deleteAll($args, true, true);

    $token = generateRandomToken();
    
    $data = array(
      'AuthenticatorResetToken' => array(
        'co_recovery_widget_id'     => $coRecoveryWidgetId,
        'co_person_id'              => $coPersonId,
        'token'                     => $token,
        'expires'                   => date('Y-m-d H:i:s', strtotime('+' . $tokenValidity . ' minutes'))
      )
    );
    
    $this->clear();
    
    if(!$this->save($data)) {
      throw new RuntimeException(_txt('er.db.save-a', array('AuthenticatorResetToken::generate')));
    }
    
    return $token;
  }

  /**
   * Determine the CO associated with a Reset Token.
   * 
   * @since  COmanage Registry v4.1.0
   * @param  string $token  Reset Token
   * @return int            CO ID
   * @throws InvalidArgumentException
   */

  public function getCoIdForToken($token) {
    $args = array();
    $args['conditions']['AuthenticatorResetToken.token'] = $token;
    $args['contain'] = array('CoPerson');
    
    $prt = $this->find('first', $args);
    
    if(!empty($prt['CoPerson']['co_id'])) {
      return $prt['CoPerson']['co_id'];
    }

    throw new InvalidArgumentException(_txt('er.notfound'));
  }
  
  /**
   * Validate an Authenticator Reset Token.
   *
   * @since  COmanage Registry v4.1.0
   * @param  string  $token      Authenticator Reset Token
   * @param  boolean $invalidate If true, invalidate the token (otherwise just test it)
   * @return array               Array of CO Person ID and Redirect URL (if set)
   * @throws InvalidArgumentException
   */
  
  public function validateToken($token, $invalidate=true) {
    if(!$token) {
      throw new InvalidArgumentException(_txt('er.recoverywidget.token.notfound'));
    }
    
    $args = array();
    $args['conditions']['AuthenticatorResetToken.token'] = $token;
    $args['contain'] = array('CoPerson', 'CoRecoveryWidget');
    
    $token = $this->find('first', $args);
    
    if(empty($token) || empty($token['AuthenticatorResetToken']['co_person_id'])) {
      throw new InvalidArgumentException(_txt('er.recoverywidget.token.notfound'));
    }
    
    if(time() > strtotime($token['AuthenticatorResetToken']['expires'])) {
      throw new InvalidArgumentException(_txt('er.recoverywidget.token.expired'));
    }
    
    // We only accept validation requests for Active or Grace Period CO People.
    if(!in_array($token['CoPerson']['status'], array(StatusEnum::Active, StatusEnum::GracePeriod))) {
      throw new InvalidArgumentException(_txt('er.recoverywidget.ssr.inactive'));
    }
    
    // We won't validate locked tokens, so check the Authenticator Status
    $args = array();
    $args['conditions']['AuthenticatorStatus.co_person_id'] = $token['AuthenticatorResetToken']['co_person_id'];
    $args['conditions']['AuthenticatorStatus.authenticator_id'] = $token['CoRecoveryWidget']['authenticator_id'];
    $args['contain'] = false;
    
    $locked = $this->CoPerson->AuthenticatorStatus->field('locked', $args['conditions']);
    
    if($locked) {
      throw new InvalidArgumentException(_txt('er.recoverywidget.ssr.locked'));
    }
    
    if($invalidate) {
      // We could also delete the token if it was expired, but that might cause
      // user confusion when their error changes from "expired" to "notfound",
      // and deleting the token doesn't actually remove the row from the table.
      
      $this->delete($token['AuthenticatorResetToken']['id']);
    }
    
    return array(
      'co_person_id'  => $token['AuthenticatorResetToken']['co_person_id'],
      'redirect_url'  => $token['CoRecoveryWidget']['authenticator_success_redirect']
    );
  }
}
