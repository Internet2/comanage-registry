<?php
/**
 * COmanage Registry Email Address Widget Model
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

App::uses("EmailAddress", "Model");
App::uses("CoMessageTemplate", "Model");

class EmailAddressWidgetVerification extends AppModel {
  // Cache the record to verify
  private $_recordToVerify = null;

  // Define class name for cake
  public $name = "EmailAddressWidgetVerification";

  // Add behaviors
  public $actsAs = array('Containable');

  // Association rules from this model to other models
  public $belongsTo = array(
    "CoEmailAddressWidget" => array(
      'foreignKey' => 'co_email_address_widget_id'
    )
  );

  // Validation rules for table elements
  public $validate = array(
    'email' => array(
      'content' => array(
        'rule' => array('email'),
        'required' => false,
        'allowEmpty' => false,
        'message' => 'Please enter a valid email address'
      ),
      'filter' => array(
        'rule' => array('validateInput',
          array('filter' => FILTER_SANITIZE_EMAIL))
      )
    ),
    'token' => array(
      'rule' => '/^[a-zA-Z0-9\-]+$/',
      'required' => true
    ),
    'co_email_address_widget_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_person_id' => array(
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
        'required' => true,
        'allowEmpty' => false
      )
    ),
  );

  /**
   * Create the email and history recordw
   *
   * @since  COmanage Registry v4.1.0
   * @param  string $token     Token used for verification
   * @return integer           ID of the new EmailAddress Record
   * @throws RuntimeException
   */

  public function addEmailToPerson($token, $actorCoPersonId) {
    // Retrieve the Verification record and the configuration
    $rec = $this->getRecordToVerify($token);

    // Create the new CO Person Email record
    $emailAttrs = array(
      'mail' => $rec['EmailAddressWidgetVerification']['email'],
      'type' => $rec['EmailAddressWidgetVerification']['type'],
      'verified' => true,
      'co_person_id' => $rec['EmailAddressWidgetVerification']['co_person_id']
    );

    try {
      $EmailAddress = ClassRegistry::init('EmailAddress');
      if(!$EmailAddress->save($emailAttrs, array("provision" => true,
                                                 "trustVerified" => true))) {
        throw new RuntimeException(_txt('er.db.save'));
      }

      $CoPerson = ClassRegistry::init('CoPerson');
      // History record for the new record
      $CoPerson->HistoryRecord->record($rec['EmailAddressWidgetVerification']['co_person_id'],
                                       null,
                                       null,
                                       $actorCoPersonId,
                                       ActionEnum::CoPersonEditedManual,
                                       _txt('pl.emailaddresswidget.rs.added-a', array($rec['EmailAddressWidgetVerification']['email'])));

      // History Record for the verification
      $CoPerson->HistoryRecord->record($rec['EmailAddressWidgetVerification']['co_person_id'],
                                       null,
                                       null,
                                       $actorCoPersonId,
                                       ActionEnum::EmailAddressVerified,
                                       _txt('pl.emailaddresswidget.rs.mail.verified', array($rec['EmailAddressWidgetVerification']['email'])));

      $this->delete($rec['EmailAddressWidgetVerification']['id']);
      return $EmailAddress->id;
      // Delete the Verification Request table record and return
    } catch(Exception $e) {
      throw new RuntimeException($e->getMessage());
    }
  }

  /**
   * Get Verification Record Singleton
   *
   * @since  COmanage Registry v4.1.0
   * @param  string $token     Token used for verification
   * @return EmailAddressWidgetVerification Verification Record
   */

  public function getRecordToVerify($token) {
    if(empty($this->_recordToVerify)) {
      $args = array();
      $args['conditions']['token'] = $token;
      $args['contain'] = array('CoEmailAddressWidget' => array('CoDashboardWidget' => array('CoDashboard')));
      $this->_recordToVerify = $this->find('first',$args);
    }
    return $this->_recordToVerify;
  }

  /**
   * Check whether the token is still valid
   *
   * @since  COmanage Registry v4.1.0
   * @param  string $token     Token used for verification
   * @return boolean outcome of verification
   * @throws InvalidArgumentException
   */

  public function checkValidity($token) {
    if(empty($token)) {
      throw new InvalidArgumentException(_txt('er.unknown', array($token)));
    }

    // Retrieve the Verification record and the configuration
    $rec = $this->getRecordToVerify($token);

    // Check if the verification token is still valid or expired
    $timeElapsed = time() - strtotime($rec['EmailAddressWidgetVerification']['created']);
    $timeWindow = (int)$rec["CoEmailAddressWidget"]["verification_validity"] * 60;

    if($timeElapsed > $timeWindow) {
      // Delete the record and return
      $this->delete($rec['EmailAddressWidgetVerification']['id']);
      return false;
    }

    return true;
  }
}
