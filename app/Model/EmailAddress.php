<?php
/**
 * COmanage Registry Email Address Model
 *
 * Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class EmailAddress extends AppModel {
  // Define class name for cake
  public $name = "EmailAddress";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // An email address may be attached to a CO Person
    "CoPerson",
    // An email address may be attached to an Org Identity
    "OrgIdentity"
  );
  
  // Default display field for cake generated views
  public $displayField = "mail";
  
  // Default ordering for find operations
  public $order = array("mail");
  
  // Validation rules for table elements
  public $validate = array(
    // Don't require mail or type since $belongsTo saves won't validate if they're empty
    'mail' => array(
      'rule' => array('email'),
      'required' => false,
      'allowEmpty' => false,
      'message' => 'Please enter a valid email address'
    ),
    'type' => array(
      'rule' => array('inList', array(EmailAddressEnum::Delivery,
                                      EmailAddressEnum::Forwarding,
                                      EmailAddressEnum::Official,
                                      EmailAddressEnum::Personal)),
      'required' => false,
      'allowEmpty' => false
    ),
    'verified' => array(
      'rule' => array('boolean')
    ),
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'org_identity_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'type' => 'contact_t'
  );
  
  /**
   * Determine if an email address of a given type is already assigned to a CO Person.
   *
   * IMPORTANT: This function should be called within a transaction to ensure
   * actions taken based on availability are atomic.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer CO Person ID
   * @param  String Type of candidate email address
   * @return Boolean True if an email address of the specified type is already assigned, false otherwise
   */
  
  public function assigned($coPersonID, $emailType) {
    $args = array();
    $args['conditions']['EmailAddress.co_person_id'] = $coPersonID;
    $args['conditions']['EmailAddress.type'] = $emailType;
    $args['contain'] = false;
    
    $r = $this->findForUpdate($args['conditions'], array('mail'));
    
    return !empty($r);
  }
  
  /**
   * Mark an address as verified.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer Org Identity ID
   * @param  String Email address to mark verified
   * @param  Integer CO Person ID of verifier
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function verify($orgIdentityId, $address, $verifierCoPersonId) {
    // First find the record
    
    $args = array();
    $args['conditions']['EmailAddress.org_identity_id'] = $orgIdentityId;
    $args['conditions']['EmailAddress.mail'] = $address;
    $args['contain'] = false;
    
    $mail = $this->find('first', $args);
    
    if(empty($mail)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.email_addresses.1'), $address)));
    }
    
    // And then update it
    
    $this->id = $mail['EmailAddress']['id'];
    
    if(!$this->saveField('verified', true)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Finally, create a history record
    
    try {
      $this->CoPerson->HistoryRecord->record(null,
                                             null,
                                             $orgIdentityId,
                                             $verifierCoPersonId,
                                             ActionEnum::EmailAddressVerified,
                                             _txt('rs.mail.verified', array($address)));
    }
    catch(Exception $e) {
      throw new RuntimeException($e->getMessage());
    }
  }
}
