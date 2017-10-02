<?php
/**
 * COmanage Registry Email Address Model
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class EmailAddress extends AppModel {
  // Define class name for cake
  public $name = "EmailAddress";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Normalization' => array('priority' => 4),
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // An email address may be attached to a CO Department
    "CoDepartment",
    // An email address may be attached to a CO Person
    "CoPerson",
    // An email address may be attached to an Org Identity
    "OrgIdentity",
    // An email address created from a Pipeline has a Source Email Address
    "SourceEmailAddress" => array(
      'className' => 'EmailAddress',
      'foreignKey' => 'source_email_address_id'
    )
  );
  
  public $hasOne = array(
    "CoInvite"
  );
  
  // Default display field for cake generated views
  public $displayField = "EmailAddress.mail";
  
  // Default ordering for find operations
//  public $order = array("mail");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    // Don't require mail or type since $belongsTo saves won't validate if they're empty
    'mail' => array(
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
    'description' => array(
      'content' => array(
        'rule' => array('validateInput'),
        'required' => false,
        'allowEmpty' => true
      )
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
        'allowEmpty' => false
      )
    ),
    'verified' => array(
      'content' => array(
        'rule' => array('boolean')
      )
    ),
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'org_identity_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'co_department_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'source_email_address_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  /**
   * Actions to take after a save operation is executed.
   *
   * @since  COmanage Registry v2.0.0
   * @param  boolean $created True if a new record was created (rather than update)
   * @param  array   $options As passed into Model::save()
   */

  public function afterSave($created, $options = array()) {
    $this->_commit();
  }
  
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
   * Actions to take before a save operation is executed.
   *
   * As of v2.0.0, a 'trustVerified' option is supported to prevent beforeSave
   * from resetting the verified attribute.
   *
   * @since  COmanage Registry v0.8.4
   */
  
  public function beforeSave($options = array()) {
    // Make sure verified is set appropriately. As of v2.0.0, Org Identity Sources
    // can assert verified status (CO-1331), so we can't just always reset verified
    // to false.
    
    $trustVerified = (isset($options['trustVerified']) && $options['trustVerified']);
    
    if(!empty($this->data['EmailAddress']['id'])) {
      // We have an existing record. Pull the current values.
      
      $args = array();
      $args['conditions']['EmailAddress.id'] = $this->data['EmailAddress']['id'];
      $args['contain'] = false;
      
      $curdata = $this->find('first', $args);
      
      if(!$trustVerified) {
        if(!empty($curdata['EmailAddress']['mail'])
           && !empty($this->data['EmailAddress']['mail'])
           && $curdata['EmailAddress']['mail'] != $this->data['EmailAddress']['mail']) {
          // Email address was changed, flag as unverified
          $this->data['EmailAddress']['verified'] = false;
        } else {
          // Use prior setting
          $this->data['EmailAddress']['verified'] = $curdata['EmailAddress']['verified'];
        }
      }
      
      // Also check if we're changing anything. If not, no need to check availability.
      
      if(!empty($curdata['EmailAddress']['mail'])
        && $curdata['EmailAddress']['mail'] == $this->data['EmailAddress']['mail']
        && !empty($curdata['EmailAddress']['type'])
        && $curdata['EmailAddress']['type'] == $this->data['EmailAddress']['type']) {
        return true;
      }
    } else {
      if(!$trustVerified) {
        // Adding a new address should default to not verified
        
        $this->data['EmailAddress']['verified'] = false;
      }
    }
    
    // Start a transaction and check availability. This is similar to Identifier::beforeSave.
    // We'll commit in afterSave. Currently, we only work with CO Person records.
    
    if(!empty($this->data['EmailAddress']['co_person_id'])) {
      $this->_begin();
      
      // If availability checks were already run (ie: by CoIdentifierAssignment::assign)
      // we can skip the checks here. However, we allow the begin() so that we have the
      // correct number of nested begin/commit calls when afterSave() fires.
      
      if(!isset($options['skipAvailability']) || !$options['skipAvailability']) {
        $coId = $this->CoPerson->field('co_id', array('CoPerson.id' => $this->data['EmailAddress']['co_person_id']));
        
        // Run the internal availability check. This will remain consistent until
        // afterSave, though we can't assert the same for any external services
        // the plugins check.
        
        try {
          $this->checkAvailability($this->data['EmailAddress']['mail'],
                                   $this->data['EmailAddress']['type'],
                                   $coId);
        }
        catch(Exception $e) {
          // Roll back the transaction and re-throw the exception
          $this->_rollback();
          
          $eclass = get_class($e);
          throw new $eclass($e->getMessage());
        }
      }
    }
    
    return true;
  }
  
  /**
   * Perform a keyword search.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Integer $coId CO ID to constrain search to
   * @param  String  $q    String to search for
   * @return Array Array of search results, as from find('all)
   */
  
  public function search($coId, $q) {
    $args = array();
    $args['joins'][0]['table'] = 'co_people';
    $args['joins'][0]['alias'] = 'CoPerson';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.id=EmailAddress.co_person_id';
    $args['conditions']['LOWER(EmailAddress.mail)'] = $q;
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['order'] = array('EmailAddress.mail');
    $args['contain'] = false;
    
    return $this->find('all', $args);
  }
  
  /**
   * Mark an address as verified.
   *
   * @since  COmanage Registry v0.7
   * @param  Integer Org Identity ID address is associated with
   * @param  Integer CO Person ID address is associated with
   * @param  String Email address to mark verified
   * @param  Integer CO Person ID of verifier
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function verify($orgIdentityId, $coPersonId, $address, $verifierCoPersonId) {
    // First find the record
    
    $args = array();
    if($orgIdentityId) {
      $args['conditions']['EmailAddress.org_identity_id'] = $orgIdentityId;
    }
    if($coPersonId) {
      $args['conditions']['EmailAddress.co_person_id'] = $coPersonId;
    }
    $args['conditions']['EmailAddress.mail'] = $address;
    $args['contain'] = false;
    
    $mail = $this->find('first', $args);
    
    if(empty($mail)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.email_addresses.1'), $address)));
    }
    
    // And then update it
    $this->id = $mail['EmailAddress']['id'];
    
    // Make sure to disable callbacks since beforeSave will try to update this field, too
    if(!$this->saveField('verified', true, array('callbacks' => false))) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    // Finally, create a history record
    
    try {
      $this->CoPerson->HistoryRecord->record($coPersonId,
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
