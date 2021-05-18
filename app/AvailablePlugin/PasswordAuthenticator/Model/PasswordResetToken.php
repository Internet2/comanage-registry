<?php
/**
 * COmanage Registry Password Reset Token Model
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

class PasswordResetToken extends AppModel {
	// Define class name for cake
  public $name = "PasswordResetToken";
	
  // Current schema version for API
  public $version = "1.0";
  
	// Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5));
	
	// Association rules from this model to other models
	public $belongsTo = array(
    "PasswordAuthenticator.PasswordAuthenticator",
    "CoPerson"
  );
	
  // Default display field for cake generated views
  public $displayField = "co_person_id";

  // Validation rules for table elements
  public $validate = array(
    'password_authenticator_id' => array(
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
   * Generate a Password Reset Token.
   *
   * @since  COmanage Registry v4.0.0
   * @param  int    $passwordAuthenticatorId Password Authenticator ID
   * @param  int    $coPersonId              CO Person ID
   * @return string                          Service Token
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */

  protected function generate($passwordAuthenticatorId, $coPersonId) {
    // Toss any previous reset tokens. We need to fire callbacks for ChangelogBehavior.
    $args = array(
      'PasswordResetToken.password_authenticator_id' => $passwordAuthenticatorId,
      'PasswordResetToken.co_person_id' => $coPersonId
    );
    
    $this->deleteAll($args, true, true);

    // We need the token validity configuration
    $tokenValidity = $this->PasswordAuthenticator->field('ssr_validity', array('PasswordAuthenticator.id' => $passwordAuthenticatorId));
    
    if(!$tokenValidity) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.password_authenticators.1'), $passwordAuthenticatorId)));
    }
    
    $token = generateRandomToken();
    
    $data = array(
      'PasswordResetToken' => array(
        'password_authenticator_id' => $passwordAuthenticatorId,
        'co_person_id'              => $coPersonId,
        'token'                     => $token,
        'expires'                   => date('Y-m-d H:i:s', strtotime('+' . $tokenValidity . ' minutes'))
      )
    );
    
    $this->clear();
    
    if(!$this->save($data)) {
      throw new RuntimeException(_txt('er.db.save-a', array('PasswordResetToken::generate')));
    }
    
    return $token;
  }
  
  /**
   * Attempt to generate (and send) a Possword Reset Token request.
   *
   * @since  COmanage Registry v4.0.0
   * @param  int    $authenticatorId Authenticator ID
   * @param  string $q               Search query (email or identifier)
   * @return bool                    True on success
   * @throws InvalidArgumentException
   */
  
  public function generateRequest($authenticatorId, $q) {
    // First, search for a CO Person record that matches $q. Note that both
    // EmailAddress and Identifier implement exact searching only, so we don't
    // need to handle that specially here. We do need to know the CO to search
    // within, though.
    
    $coId = $this->PasswordAuthenticator->Authenticator->field('co_id', array('Authenticator.id' => $authenticatorId));
    
    if(!$coId) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.authenticators.1'), $authenticatorId)));
    }
    
    // Next, try to find a CO Person ID. We need to find exactly one, but we'll
    // run both searches regardless in case we somehow have an ambiguous string.
    
    $coPersonId = null;
    
    foreach(array('EmailAddress', 'Identifier') as $model) {
      // Note this search will match _unverified_ email addresses, but we only
      // want to match verified email addresses. We'll filter those below.
      $matches = $this->CoPerson->$model->search($coId, $q);
      
      if(!empty($matches)) {
        foreach($matches as $m) {
          // If this is an EmailAddress, make sure it is verified
          if(isset($m['EmailAddress']['verified']) && !$m['EmailAddress']['verified']) {
            continue;
          }
          
          if(!$coPersonId) {
            $coPersonId = $m['CoPerson']['id'];
          } elseif($coPersonId != $m['CoPerson']['id']) {
            // We found at least two different CO People, so throw an error
            throw new InvalidArgumentException(_txt('er.passwordauthenticator.ssr.multiple', $q));
          }
        }
      }
    }
    
    if(!$coPersonId) {
      throw new InvalidArgumentException(_txt('er.passwordauthenticator.ssr.notfound', array($q)));
    }
    
    // Take the CO Person and look for associated verified email addresses.
    // This could match the search query, but we'll walk the path to make sure.
    
    $args = array();
    $args['conditions']['CoPerson.id'] = $coPersonId;
    $args['contain'] = array('EmailAddress');

    $coPerson = $this->CoPerson->find('first', $args);
    
    // We could try prioritizing on type or something, but instead we'll just
    // send the message to however many verified addresses we find.
    $verifiedEmails = array();
    
    if(!empty($coPerson['EmailAddress'])) {
      foreach($coPerson['EmailAddress'] as $ea) {
        if($ea['verified']) {
          $verifiedEmails[] = $ea['mail'];
        }
      }
    }
    
    if(empty($verifiedEmails)) {
      throw new InvalidArgumentException(_txt('er.passwordauthenticator.ssr.notfound', array($q)));
    }
    
    // Map the Authenticator ID to a Password Authenticator ID
    $args = array();
    $args['conditions']['PasswordAuthenticator.authenticator_id'] = $authenticatorId;
    $args['contain'] = false;
    
    $passwordAuthenticator = $this->PasswordAuthenticator->find('first', $args);
    
    if(!$passwordAuthenticator) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.authenticators.1'), $authenticatorId)));
    }
    
    // Next, generate a token in order to send it
    $token = $this->generate($passwordAuthenticator['PasswordAuthenticator']['id'], $coPersonId);
    
    // Finally, send the message
    $this->send($passwordAuthenticator, $coPersonId, $token, $verifiedEmails, $coPersonId);
    
    return true;
  }
  
  /**
   * Send a Password Reset Token.
   *
   * @since  COmanage Registry v4.0.0
   * @param  PasswordAuthenticator $passwordAuthenticator Password Authenticator configuration
   * @param  int                   $coPersonId            CO Person ID
   * @param  string                $token                 Password Reset Token
   * @param  array                 $recipients            Array of email addresses to send token to
   * @param  int                   $actorCoPersonId       Actor CO Person ID
   * @throws InvalidArgumentException
   */
  
  protected function send($passwordAuthenticator, $coPersonId, $token, $recipients, $actorCoPersonId) {
    // Pull the message template
    $mt = null;
    
    if(!empty($passwordAuthenticator['PasswordAuthenticator']['co_message_template_id'])) {
      $args = array();
      $args['conditions']['CoMessageTemplate.id'] = $passwordAuthenticator['PasswordAuthenticator']['co_message_template_id'];
      $args['conditions']['CoMessageTemplate.status'] = SuspendableStatusEnum::Active;
      $args['contain'] = false;
      
      $mt = $this->PasswordAuthenticator->CoMessageTemplate->find('first', $args);
    }
    
    if(empty($mt)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_message_templates.1'), $passwordAuthenticator['PasswordAuthenticator']['co_message_template_id'])));
    }
    
    // And the expiration time
    $expiry = $this->field('expires', array('PasswordResetToken.token' => $token));
    
    // Create the message subject and body based on the templates.

    $rurl = array(
      'plugin'      => 'password_authenticator',
      'controller'  => 'passwords',
      'action'      => 'ssr',
      'token'       => $token
    );

    $substitutions = array(
      'RESET_URL'         => Router::url($rurl, true),
      'LINK_EXPIRY'       => $expiry
    );

    // Construct subject and body

    $msgSubject = processTemplate($mt['CoMessageTemplate']['message_subject'], $substitutions);
    $format = $mt['CoMessageTemplate']['format'];
    
    // We don't try/catch, but instead let any exceptions bubble up.
    $email = new CakeEmail('default');

      // If a from address was provided, use it
/*
      if($fromAddress) {
        $email->from($fromAddress);
      }*/

    // Add cc and bcc if specified
    if($mt['CoMessageTemplate']['cc']) {
      $email->cc(explode(',', $mt['CoMessageTemplate']['cc']));
    }

    if($mt['CoMessageTemplate']['bcc']) {
      $email->bcc(explode(',', $mt['CoMessageTemplate']['bcc']));
    }
    
    $msgBody = array();
    
    if($format != MessageFormatEnum::Plaintext
       && !empty($mt['CoMessageTemplate']['message_body_html'])) {
      $msgBody[MessageFormatEnum::HTML] = processTemplate($mt['CoMessageTemplate']['message_body_html'], $substitutions);
    }
    if($format != MessageFormatEnum::HTML
       && !empty($mt['CoMessageTemplate']['message_body'])) {
      $msgBody[MessageFormatEnum::Plaintext] = processTemplate($mt['CoMessageTemplate']['message_body'], $substitutions);
    }
    if(empty($msgBody[MessageFormatEnum::Plaintext])) {
      $msgBody[MessageFormatEnum::Plaintext] = "unknown message";
    }
    
    $email->template('custom', 'basic')
      ->emailFormat($format)
      ->to($recipients)
      ->viewVars($msgBody)
      ->subject($msgSubject);
    $email->send();
    
    // Record a HistoryRecord
    $this->CoPerson->HistoryRecord->record($coPersonId,
                                           null,
                                           null,
                                           $actorCoPersonId,
                                           ActionEnum::AuthenticatorEdited,
                                           _txt('pl.passwordauthenticator.ssr.hr.sent', array(implode(",", $recipients))));
    
    // Also store the recipient list in the token
    $this->clear();
    
    $this->updateAll(
      array('PasswordResetToken.recipients' => "'" . substr(implode(',', $recipients), 0, 256) . "'"),
      array('PasswordResetToken.token' => "'" . $token . "'")
    );
  }
  
  /**
   * Validate a Password Reset Token.
   *
   * @since  COmanage Registry v4.0.0
   * @param  string  $token      Password Reset Token
   * @param  boolean $invalidate If true, invalidate the token (otherwise just test it)
   * @return int                 CO Person ID
   * @throws InvalidArgumentException
   */
  
  public function validateToken($token, $invalidate=true) {
    if(!$token) {
      throw new InvalidArgumentException(_txt('er.passwordauthenticator.token.notfound'));
    }
    
    $args = array();
    $args['conditions']['PasswordResetToken.token'] = $token;
    $args['contain'] = array('CoPerson', 'PasswordAuthenticator');
    
    $token = $this->find('first', $args);
    
    if(empty($token) || empty($token['PasswordResetToken']['co_person_id'])) {
      throw new InvalidArgumentException(_txt('er.passwordauthenticator.token.notfound'));
    }
    
    if(time() > strtotime($token['PasswordResetToken']['expires'])) {
      throw new InvalidArgumentException(_txt('er.passwordauthenticator.token.expired'));
    }
    
    // We only accept validation requests for Active or Grace Period CO People.
    if(!in_array($token['CoPerson']['status'], array(StatusEnum::Active, StatusEnum::GracePeriod))) {
      throw new InvalidArgumentException(_txt('er.passwordauthenticator.ssr.inactive'));
    }
    
    // We won't validate locked tokens, so check the Authenticator Status
    $args = array();
    $args['conditions']['AuthenticatorStatus.co_person_id'] = $token['PasswordResetToken']['co_person_id'];
    $args['conditions']['AuthenticatorStatus.authenticator_id'] = $token['PasswordAuthenticator']['authenticator_id'];
    $args['contain'] = false;
    
    $locked = $this->CoPerson->AuthenticatorStatus->field('locked', $args['conditions']);
    
    if($locked) {
      throw new InvalidArgumentException(_txt('er.passwordauthenticator.ssr.locked'));
    }
    
    if($invalidate) {
      // We could also delete the token if it was expired, but that might cause
      // user confusion when their error changes from "expired" to "notfound",
      // and deleting the token doesn't actually remove the row from the table.
      
      $this->delete($token['PasswordResetToken']['id']);
    }
    
    return $token['PasswordResetToken']['co_person_id'];
  }
}
