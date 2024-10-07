<?php
/**
 * COmanage Registry CO Email Address Widget Model
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoDashboardWidgetBackend", "Model");
App::uses("CakeEmail", "Network/Email");

class CoEmailAddressWidget extends CoDashboardWidgetBackend {
  // Define class name for cake
  public $name = "CoEmailAddressWidget";

  // Add behaviors
  public $actsAs = array('Containable');

  // Association rules from this model to other models
  public $belongsTo = array(
    "CoDashboardWidget"
  );

  public $hasMany = array(
    "EmailAddressWidgetVerification" => array(
      'dependent' => true,
      'foreignKey' => 'co_email_address_widget_id'
    )
  );

  // Validation rules for table elements
  public $validate = array(
    'co_dashboard_widget_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
          array('attribute' => 'EmailAddress.type',
                'default' => array(EmailAddressEnum::Delivery,
                  EmailAddressEnum::Forwarding,
                  EmailAddressEnum::MailingList,
                  EmailAddressEnum::Official,
                  EmailAddressEnum::Personal,
                  EmailAddressEnum::Preferred,
                  EmailAddressEnum::Recovery))),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_message_template_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );

  /**
   * Generate a token for email verification and save it in a new record
   * along with the email address and email type to be  added.
   * This is step one of a two-step email verification process.
   *
   * @since COmanage Registry v4.1.0
   * @param string  $emailAddress Email address to be added
   * @param string  $emailId      Email address ID (for update/replace)
   * @param string  $emailType    Type of email address to be added
   * @param integer $copersonid   The CO Person to be added the email
   * @return integer id of the new row
   */
  public function generateToken($emailAddress, $emailId, $emailType, $copersonid) {
    $token = generateRandomToken(8);

    $fields = array(
      'email' => $emailAddress,
      'email_id' => $emailId,
      'type' => $emailType,
      'token' => $token,
      'co_email_address_widget_id' => $this->id,
      'co_person_id' => $copersonid
    );

    $this->EmailAddressWidgetVerification->clear();
    if(!$this->EmailAddressWidgetVerification->save($fields)) {
      throw new RuntimeException($this->validationErrors);
    }

    // Return the token to pass along via email
    return array(
      'token' => $token
    );
  }

  /**
   * Send the token to the new email address for round-trip verification.
   * This is loosely modeled on AvailablePlugin/PasswordAuthenticator/Model/PasswordResetToken.php
   *
   * @since  COmanage Registry v4.1.0
   * @param  string  $emailAddress  Email address being verified
   * @param  string  $token         Token used for verification
   * @param  integer $template_id   Email template
   */
  public function send($emailAddress, $token, $template_id) {
    // Get an email object
    $email = new CakeEmail('default');

    $substitutions = array(
      'TOKEN'         => $token
    );

    if(!empty($template_id)) {
      $this->CoMessageTemplate = ClassRegistry::init('CoMessageTemplate');
      $this->CoMessageTemplate->templateSend(
        $template_id,
        $emailAddress,
        $substitutions
      );
    } else {
      // Send the default message
      $msgSubject = _txt('pl.emailaddresswidget.email.subject');
      $format = MessageFormatEnum::Plaintext;
      $msgBody[MessageFormatEnum::Plaintext] = _txt('pl.emailaddresswidget.email.body') . PHP_EOL . PHP_EOL . $token;

      $email->template('custom', 'basic')
        ->emailFormat($format)
        ->to($emailAddress)
        ->viewVars($msgBody)
        ->subject($msgSubject);
      $email->send();
    }

  }
}