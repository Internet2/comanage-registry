<?php
/**
 * COmanage Registry SSH Key Model
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
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class SshKey extends AppModel {
  // Define class name for cake
  public $name = "SshKey";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoPerson"
  );
  
  // Default display field for cake generated views
  public $displayField = "comment";
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => false
      )
    ),
    'comment' => array(
      'content' => array(
        'rule' => array('maxLength', 256),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    ),
    'type' => array(
      'content' => array(
        'rule' => array('inList', array(SshKeyTypeEnum::DSA,
                                        SshKeyTypeEnum::ECDSA,
                                        SshKeyTypeEnum::ED25519,
                                        SshKeyTypeEnum::RSA,
                                        SshKeyTypeEnum::RSA1)),
        'required' => true,
        'allowEmpty' => false
      )
    ),
    'skey' => array(
      'content' => array(
        'rule' => array('maxLength', 4000),
        'required' => true,
        'allowEmpty' => false
      ),
      'filter' => array(
        'rule' => array('validateInput')
      )
    )
  );
  
  /**
   * Parse a file for an SSH key and attach the key to the specified CO Person.
   *
   * @since  COmanage Registry v0.9
   * @param  string  Name of file holding SSH key (presumably from PHP upload parsing)
   * @param  integer Identifier of CO Person
   * @return Array   SshKey object, as parsed from the key file
   * @throws InvalidArgumentException
   */
  
  public function addFromKeyFile($keyfile, $coPersonId) {
    // First read the contents of the keyfile
    $key = rtrim(file_get_contents($keyfile));
    
    if(!$key) {
      throw new InvalidArgumentException(_txt('er.file.read', array($keyfile)));
    }
    
    // We currently only support OpenSSH format, which is a triple of type/key/comment.
    
    $bits = explode(' ', $key, 3);
    
    // Convert the key type into an enum
    $keyType = null;
    
    switch($bits[0]) {
      case 'ecdsa-sha2-nistp256':
        $keyType = SshKeyTypeEnum::ECDSA;
        break;
      case 'ssh-dss':
        $keyType = SshKeyTypeEnum::DSA;
        break;
      case 'ssh-ed25519':
        $keyType = SshKeyTypeEnum::ED25519;
        break;
      case 'ssh-rsa':
        $keyType = SshKeyTypeEnum::RSA;
        break;
      case 'ssh-rsa1':
        $keyType = SshKeyTypeEnum::RSA1;
        break;
      case '-----BEGIN':
        if(strncmp($bits[2], 'PRIVATE', 7)==0) {
          // This is the private key, not the public key
          throw new InvalidArgumentException(_txt('er.ssh.private'));
        }
        // else unknown format, fall through for error
        break;
      case '----':
        if($bits[1] == 'BEGIN' && strncmp($bits[2], 'SSH2', 4)==0) {
          // This is an RFC 4716 key format, which is not currently supported (CO-859)
          throw new InvalidArgumentException(_txt('er.ssh.rfc4716'));
        }
        // else unknown format, fall through for error
        break;
    }
    
    if(!$keyType) {
      throw new InvalidArgumentException(_txt('er.ssh.type', array(filter_var($bits[0],
        FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_HIGH |
        FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK)))); /* was Cake's Sanitize::paranoid */
    }
    
    $key = $bits[1];
    $comment = $bits[2];
    
    if(!$key) {
      throw new InvalidArgumentException(_txt('er.ssh.format'));
    }
    
    $sk = array();
    $sk['co_person_id'] = $coPersonId;
    $sk['comment'] = $comment;
    $sk['type'] = $keyType;
    $sk['skey'] = $key;
    
    if(!$this->save($sk)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return $sk;
  }
}
