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
        'allowEmpty' => true
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
    'email_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
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
   * Create the email and history record
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
      // History Record for the new record + verification
      $CoPerson->HistoryRecord->record($rec['EmailAddressWidgetVerification']['co_person_id'],
                                       null,
                                       null,
                                       $actorCoPersonId,
                                       ActionEnum::EmailAddressVerified,
                                       _txt('pl.emailaddresswidget.rs.mail.added.verified',
                                            array($rec['EmailAddressWidgetVerification']['email'])));
      
      // Delete the Verification Request table record and return
      $this->delete($rec['EmailAddressWidgetVerification']['id']);
      return $EmailAddress->id;
    } catch(Exception $e) {
      // Attempt to delete the Verification Request table record before throwing exception
      $this->delete($rec['EmailAddressWidgetVerification']['id']);
      throw new RuntimeException($e->getMessage());
    }
  }
  
  /**
   * Replace the email and add a history record
   *
   * @since  COmanage Registry v4.1.0
   * @param  string $token     Token used for verification
   * @return integer           ID of the EmailAddress Record
   * @throws RuntimeException
   */
  
  public function replaceEmailForPerson($token, $actorCoPersonId) {
    // Retrieve the Verification record and the configuration
    $rec = $this->getRecordToVerify($token);
    
    // Create the CO Person Email record for replacement
    $emailAttrs = array(
      'id' => $rec['EmailAddressWidgetVerification']['email_id'],
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
      // History Record for the new record + verification
      $CoPerson->HistoryRecord->record($rec['EmailAddressWidgetVerification']['co_person_id'],
        null,
        null,
        $actorCoPersonId,
        ActionEnum::EmailAddressVerified,
        _txt('pl.emailaddresswidget.rs.mail.updated.verified',
          array($rec['EmailAddressWidgetVerification']['email'],
                $rec['EmailAddressWidgetVerification']['email_id'])));
      
      $this->delete($rec['EmailAddressWidgetVerification']['id']);
      return $EmailAddress->id;
      // Delete the Verification Request table record and return
    } catch(Exception $e) {
      throw new RuntimeException($e->getMessage());
    }
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

  /**
   * Create the email and history record
   *
   * @since  COmanage Registry v4.1.0
   * @param  string $token       Token used for verification
   * @param  intt   $coid        CO Id
   * @param  string $username    Username from Session
   * @return integer             ID of the new EmailAddress Record
   * @throws RuntimeException
   * @throws InvalidArgumentException
   */

  public function execute_verify($token, $coid, $username) {
    $rec = $this->getRecordToVerify($token);

    // Check if the person owns the records and can proceed with the verification
    $CoPerson = ClassRegistry::init('CoPerson');
    $actorCoPersonId = $CoPerson->idForIdentifier($coid, $username);

    if($rec['EmailAddressWidgetVerification']['co_person_id'] != $actorCoPersonId) {
      throw new InvalidArgumentException( _txt('er.cop.nf', array($username)),
                                          HttpStatusCodesEnum::HTTP_NOT_FOUND);
    }

    if(!$this->checkValidity($token)) {
      throw new RuntimeException(_txt('er.emailaddresswidget.timeout'), HttpStatusCodesEnum::HTTP_NOT_ACCEPTABLE);
    }
  
    if($rec['EmailAddressWidgetVerification']['email_id'] > 0) {
      // We need to update / replace an email address
      return $this->replaceEmailForPerson($token, $actorCoPersonId);
    } 
    
    // We need to add a new email address
    return $this->addEmailToPerson($token, $actorCoPersonId);
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
}
