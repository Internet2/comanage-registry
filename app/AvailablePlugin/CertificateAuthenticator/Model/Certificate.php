<?php
/**
 * COmanage Registry (X.509) Certificate Model
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class Certificate extends AppModel {
  // Define class name for cake
  public $name = "Certificate";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Document foreign keys
  public $cmPluginHasMany = array(
// XXX unclear that we're using this correctly here or elsewhere, review other (newer) plugins
//              "CoPerson" => array("Certificate")
  );
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CertificateAuthenticator.CertificateAuthenticator",
    "CoPerson"
  );
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'certificate_authenticator_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'description' => array(
      'content' => array(
        'rule' => array('maxLength', 256),
        'required' => true,
        'allowEmpty' => false
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'subject_dn' => array(
      'content' => array(
        'rule' => array('maxLength', 256),
        'required' => true,
        'allowEmpty' => false
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'issuer_dn' => array(
      'content' => array(
        'rule' => array('maxLength', 256),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    // XXX how does this relate to timezones? see eg Model/CoPersonRole.php if we need to adjust
    'valid_from' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'valid_through' => array(
      'content' => array(
        'rule' => array('validateTimestamp'),
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
}
