<?php
/**
 * COmanage Registry Recovery Widget Model
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

class RecoveryWidget extends AppModel {
  // Define class name for cake
  public $name = "RecoveryWidget";

  // Required by COmanage Plugins
  public $cmPluginType = "dashboardwidget";
	
	// Add behaviors
//  public $actsAs = array('Containable');
	
  // Document foreign keys
  public $cmPluginHasMany = array();
	
	// Association rules from this model to other models
	public $belongsTo = array(
	);
	
	public $hasMany = array(
	);
	
  // Default display field for cake generated views
//  public $displayField = "description";
	
  // Validation rules for table elements
  public $validate = array(
	);
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.2.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
	
  public function cmPluginMenus() {
  	return array();
  }

  /**
   * Lookup up a CO Person based on a query string.
   * 
   * @since  COmanage Registry v4.1.0
   * @param  int    $coId   CO ID
   * @param  string $query  Query string (email address or identifier)
   * @return array          Arroy of CO Person ID, verified Email Addresses, and Identifiers
   * @throws InvalidArgumentException
   */

  protected function lookupCoPerson($coId, $query) {
    // Search for a CO Person record that matches $query. Note that both
    // EmailAddress and Identifier implement exact searching only, so we
    // don't need to handle that specially here. We need to find exactly one
    // CO Person, but we'll run both searches regardless in case we somehow have
    // an ambiguous string.
    
    $CoPerson = ClassRegistry::init('CoPerson');

    $coPersonId = null;
    
    foreach(array('EmailAddress', 'Identifier') as $model) {
      // Note this search will match _unverified_ email addresses, but we only
      // want to match verified email addresses. We'll filter those below.
      $matches = $CoPerson->$model->search($coId, $query, 25);
      
      if(!empty($matches)) {
        foreach($matches as $m) {
          // If this is an EmailAddress, make sure it is verified
          if(isset($m['EmailAddress']['verified']) && !$m['EmailAddress']['verified']) {
            continue;
          }

          // We also require an Active/GracePeriod status on the Person record
          if(!in_array($m['CoPerson']['status'], array(StatusEnum::Active, StatusEnum::GracePeriod))) {
            continue;
          }
          
          if(!$coPersonId) {
            $coPersonId = $m['CoPerson']['id'];
          } elseif($coPersonId != $m['CoPerson']['id']) {
            // We found at least two different CO People, so throw an error
            throw new InvalidArgumentException(_txt('er.recoverywidget.ssr.multiple', $q));
          }
        }
      }
    }

    if(!$coPersonId) {
      throw new InvalidArgumentException(_txt('er.recoverywidget.lookup.notfound', array($query)));
    }

    // Take the CO Person and look for associated verified email addresses.
    // This could match the search query, but we'll walk the path to make sure.
    
    $args = array();
    $args['conditions']['CoPerson.id'] = $coPersonId;
    $args['contain'] = array('EmailAddress', 'Identifier');

    $cp = $CoPerson->find('first', $args);
    
    // We could try prioritizing on type or something, but instead we'll just
    // send the message to however many verified addresses we find.
    $verifiedEmails = array();
    
    if(!empty($cp['EmailAddress'])) {
      foreach($cp['EmailAddress'] as $ea) {
        if($ea['verified']) {
          $verifiedEmails[] = $ea['mail'];
        }
      }
    }
    
    if(empty($verifiedEmails)) {
      throw new InvalidArgumentException(_txt('er.recoverywidget.ssr.notfound', array($query)));
    }

    return array(
      'id'              => $coPersonId,
      'verifiedEmails'  => $verifiedEmails,
      'identifiers'     => $cp['Identifier']
    );
  }

  /**
   * Resend a confirmation email associated with a CO Petition.
   * 
   * @since  COmanage Registry v4.1.0
   * @param  int    $coId             CO ID
   * @param  string $query            Query string
   * @param  int    $actorCoPersonId  Actor CO Person ID
   */

  public function resendConfirmation($coId, $query, $actorCoPersonId) {
    // While authenticator reset looks for an exact match, resending confirmation will
    // work for multiple matching petitions.

    $matchedPetitions = array();

    // Use the query string to try to find a Petition in Pending Confirmation status.
    // First we'll try for matching email addresses.

    $CoPetition = ClassRegistry::init('CoPetition');

    $args = array();
    $args['conditions']['CoPetitionAttribute.attribute'] = 'mail';
    $args['conditions']['lower(CoPetitionAttribute.value)'] = $query;
    $args['conditions']['CoPetition.status'] = StatusEnum::PendingConfirmation;
    $args['conditions']['CoPetition.co_id'] = $coId;
    $args['contain'] = array('CoPetition');

    $petitions = $CoPetition->CoPetitionAttribute->find('all', $args);

    if(!empty($petitions)) {
      foreach($petitions as $pt) {
        if(!isset($matchedPetitions[ $pt['CoPetition']['id'] ])) {
          $matchedPetitions[ $pt['CoPetition']['id'] ] = true;
        }
      }
    }

    // Look for matching authenticated identifiers.

    $args = array();
    $args['conditions']['CoPetition.authenticated_identifier'] = $query;
    $args['conditions']['CoPetition.status'] = StatusEnum::PendingConfirmation;
    $args['conditions']['CoPetition.co_id'] = $coId;
    $args['contain'] = false;

    $petitions = $CoPetition->find('all', $args);

    if(!empty($petitions)) {
      foreach($petitions as $pt) {
        if(!isset($matchedPetitions[ $pt['CoPetition']['id'] ])) {
          $matchedPetitions[ $pt['CoPetition']['id'] ] = true;
        }
      }
    }

    // For each unique petition matched, resend the confirmation message

    foreach(array_keys($matchedPetitions) as $coPetitionId) {
      $CoPetition->resend($coPetitionId, $actorCoPersonId);

      // Add extra petition history
      $CoPetition->CoPetitionHistoryRecord->record($coPetitionId,
                                                   $actorCoPersonId,
                                                   PetitionActionEnum::InviteSent,
                                                   _txt('pl.recoverywidget.history.confirmation_resend'));
    }

    return !empty($matchedPetitions);
  }

  /**
   * Send a Recovery related email
   *
   * @since  COmanage Registry v4.1.0
   * @param  array  $recipients         Array of email addresses to send token to
   * @param  int    $messageTemplateId  Message Template ID
   * @param  array	$substitutions	    Substitutions for the message template
   * @param  array  $identifiers        Identifiers for message template substitution
   * @throws InvalidArgumentException
   */

  protected function send($recipients,
                          $messageTemplateId,
                          $substitutions, 
                          $identifiers) {
    // Pull the message template

    $args = array();
    $args['conditions']['CoMessageTemplate.id'] = $messageTemplateId;
    $args['conditions']['CoMessageTemplate.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;

    $CoMessageTemplate = ClassRegistry::init('CoMessageTemplate');


    $mt = $CoMessageTemplate->find('first', $args);
 
    if(empty($mt)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_message_templates.1'), $messageTemplateId)));
    }

    $msgSubject = processTemplate($mt['CoMessageTemplate']['message_subject'], $substitutions, $identifiers);
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
      $msgBody[MessageFormatEnum::HTML] = processTemplate($mt['CoMessageTemplate']['message_body_html'], $substitutions, $identifiers);
    }
    if($format != MessageFormatEnum::HTML
       && !empty($mt['CoMessageTemplate']['message_body'])) {
      $msgBody[MessageFormatEnum::Plaintext] = processTemplate($mt['CoMessageTemplate']['message_body'], $substitutions, $identifiers);
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
  }
  
  /**
   * Send an Authenticator Reset message.
   * 
   * @since  COmanage Registry v4.1.0
   * @param  int    $coId               CO ID
   * @param  array  $coRecoveryWidget   Array of CoRecoveryWidget configuration
   * @param  string $query              Query string (Email Address or Identifier)
   * @param  int    $actorCoPersonId    Actor CO Person ID
   * @return bool                       True on success
   */

  public function sendAuthenticatorReset($coId, $coRecoveryWidget, $query, $actorCoPersonId) {
    $coPersonInfo = $this->lookupCoPerson($coId, $query);
    
    $AuthenticatorResetToken = ClassRegistry::init('RecoveryWidget.AuthenticatorResetToken');

    $token = $AuthenticatorResetToken->generate($coRecoveryWidget['id'],
                                                $coPersonInfo['id'],
                                                $coRecoveryWidget['authenticator_reset_validity']);

    // Store the recipient list in the token
    $AuthenticatorResetToken->clear();
    $AuthenticatorResetToken->updateAll(
      array('AuthenticatorResetToken.recipients' => "'" . substr(implode(',', $coPersonInfo['verifiedEmails']), 0, 256) . "'"),
      array('AuthenticatorResetToken.token' => "'" . $token . "'")
    );

    // Assemble password reset substitutions
    $rurl = array(
      'plugin'      => 'password_authenticator',
      'controller'  => 'passwords',
      'action'      => 'ssr',
      'token'       => $token
    );

    // Get the expiration time
    $expiry = $AuthenticatorResetToken->field('expires', array('AuthenticatorResetToken.token' => $token));

    $substitutions = array(
      'RESET_URL'         => Router::url($rurl, true),
      'LINK_EXPIRY'       => $expiry
    );

    $this->send($coPersonInfo['verifiedEmails'], 
                $coRecoveryWidget['authenticator_reset_template_id'],
                $substitutions, 
                $coPersonInfo['identifiers']); 

    // Record a HistoryRecord
    $HistoryRecord = ClassRegistry::init('HistoryRecord');

    $HistoryRecord->record($coPersonInfo['id'],
                           null,
                           null,
                           $actorCoPersonId,
                           // XXX should we define this in an enum?
                           'pRWS',
                           _txt('pl.recoverywidget.history.authenticator_reset', array(implode(",", $coPersonInfo['verifiedEmails']))));
    
    return true;
  }

  /**
   * Send an Identifier Lookup message.
   * 
   * @since  COmanage Registry v4.1.0
   * @param  int    $coId               CO ID
   * @param  string $query              Query string (Email Address or Identifier)
   * @param  int    $messageTemplateId  CO Message Template ID
   * @param  int    $actorCoPersonId    Actor CO Person ID
   * @return bool                       True on success
   */

  public function sendIdentifier($coId, $query, $messageTemplateId, $actorCoPersonId) {
    $coPersonInfo = $this->lookupCoPerson($coId, $query);

    // Assemble identifier lookup substitutions
    // XXX We don't currently have any substitutions other than identifiers, handled separately
    $substitutions = array();

    $this->send($coPersonInfo['verifiedEmails'], 
                $messageTemplateId,
                $substitutions, 
                $coPersonInfo['identifiers']);

    // Record a HistoryRecord
    $HistoryRecord = ClassRegistry::init('HistoryRecord');

    $HistoryRecord->record($coPersonInfo['id'],
                           null,
                           null,
                           $actorCoPersonId,
                           // XXX should we define this in an enum?
                           'pRWS',
                           _txt('pl.recoverywidget.history.identifier_lookup', array(implode(",", $coPersonInfo['verifiedEmails']))));
    
    return true;
  }
}
