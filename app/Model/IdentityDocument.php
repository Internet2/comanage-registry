<?php
/**
 * COmanage Registry Identity Document Model
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
  
class IdentityDocument extends AppModel {
  // Define class name for cake
  public $name = "IdentityDocument";
  
  // Current schema version for API
  public $version = "1.0";
  
  public $actsAs = array('Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array("CoPerson");
  
  // Default display field for cake generated views
  public $displayField = "document_type";
  
  // Default ordering for find operations
//  public $order = array("IdentityDocument.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'document_type' => array(
      'content' => array(
        'rule' => array('inList', array(
          IdentityDocumentEnum::BirthCertificate,
          IdentityDocumentEnum::DriversLicense,
          IdentityDocumentEnum::Local,
          IdentityDocumentEnum::National,
          IdentityDocumentEnum::NonDriver,
          IdentityDocumentEnum::Passport,
          IdentityDocumentEnum::Regional,
          IdentityDocumentEnum::Residency,
          IdentityDocumentEnum::SelfAssertion,
          IdentityDocumentEnum::Tribal,
          IdentityDocumentEnum::Visa
        )),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'document_subtype' => array(
      'content' => array(
        'rule' => array('maxLength', 80),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'issuing_authority' => array(
      'content' => array(
        'rule' => array('maxLength', 80),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        // Note we perform additional checks in beforeSave, see that function for details
        'rule' => array('validateInput')
      )
    ),
    'subject' => array(
      'content' => array(
        'rule' => array('maxLength', 80),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'document_identifier' => array(
      'content' => array(
        'rule' => array('maxLength', 80),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'valid_from' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      ),
      'precedes' => array(
        'rule' => array('validateTimestampRange', "valid_through", "<"),
      ),
    ),
    'valid_through' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      ),
      'follows' => array(
        'rule' => array("validateTimestampRange", "valid_from", ">"),
      ),
    ),
    'verification_method' => array(
      'content' => array(
        'rule' => array('inList', array(
          IdentityVerificationMethodEnum::None,
          IdentityVerificationMethodEnum::Online,
          IdentityVerificationMethodEnum::Physical,
          IdentityVerificationMethodEnum::Remote 
        )),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'verifier_identifier' => array(
      'content' => array(
        'rule' => array('maxLength', 256),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'verifier_comment' => array(
      'content' => array(
        'rule' => array('maxLength', 256),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    )
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v4.0.0
   */

  public function beforeSave($options = array()) {
    // Verify the Attribute Enumeration values for issuing_authority, if any.
    // Because the logic is more complicated than the Cake 2 validation framework
    // can handle, we do it here where we (generally) have full access to the record.
    // Mostly this is a sanity check in case someone tries to bypass the UI, since
    // ordinarily it shouldn't be possible to send an unpermitted value.
    
    $coId = $this->CoPerson->findCoForRecord($this->data[$this->alias]['co_person_id']);
    
    $docType = $this->data[$this->alias]['document_type'];
    $issuing_authority = isset($this->data[$this->alias]['issuing_authority'])
                         ? $this->data[$this->alias]['issuing_authority']
                         : '';
    
    if($docType) {
      $this->validateEnumeration($coId,
                                 'IdentityDocument.issuing_authority.'.$docType,
                                 $issuing_authority);
    }
    
    // Possibly convert the requested timestamps to UTC from browser time.
    // Do this before the strtotime/time calls below, both of which use UTC.
    
    if($this->tz) {
      $localTZ = new DateTimeZone($this->tz);

      if(!empty($this->data[$this->alias]['valid_from'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data[$this->alias]['valid_from'], $localTZ);

        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data[$this->alias]['valid_from'] = strftime("%F %T", $offsetDT->getTimestamp());
      }

      if(!empty($this->data[$this->alias]['valid_through'])) {
        // This returns a DateTime object adjusting for localTZ
        $offsetDT = new DateTime($this->data[$this->alias]['valid_through'], $localTZ);

        // strftime converts a timestamp according to server localtime (which should be UTC)
        $this->data[$this->alias]['valid_through'] = strftime("%F %T", $offsetDT->getTimestamp());
      }
    }
    
    // We directly inject this for both add and edit. On edit, ChangelogBehavior
    // will archive the previous value.
    $this->data[$this->alias]['verifier_identifier'] = CakeSession::read('Auth.User.username');
    
    return true;
  }
}
