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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class SshKey extends AppModel {
  // Define class name for cake
  public $name = "SshKey";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Changelog' => array('priority' => 5),
                         'Provisioner');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "SshKeyAuthenticator.SshKeyAuthenticator",
    "CoPerson"
  );
  
  // Default display field for cake generated views
  public $displayField = "comment";
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'ssh_key_authenticator_id' => array(
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
                                        SshKeyTypeEnum::ECDSA384,
                                        SshKeyTypeEnum::ECDSA521,
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
   * @since  COmanage Registry v3.3.0
   * @param  string  Name of file holding SSH key (presumably from PHP upload parsing)
   * @param  integer Identifier of CO Person
   * @param  integer SSH Key Authenticator ID
   * @return Array   SshKey object, as parsed from the key file
   * @throws InvalidArgumentException
   */
  
  public function addFromKeyFile($keyfile, $coPersonId, $sshKeyAuthenticatorId) {
    // First read the contents of the keyfile
    $keyfileString = rtrim(file_get_contents($keyfile));
    
    if(!$keyfileString) {
      throw new InvalidArgumentException(_txt('er.file.read', array($keyfile)));
    }

    if(preg_match("/-----BEGIN.*PRIVATE.*/", $keyfileString) == 1) {
      // This is the private key, not the public key
      throw new InvalidArgumentException(_txt('pl.sshkeyauthenticator.private'));
    }
    
    // We currently only support OpenSSH format, which is a triple of type/key/comment,
    // and RFC 4716 Secure Shell (SSH) Public Key File format.

    if(preg_match("/^---- BEGIN SSH2 PUBLIC KEY ----.*/", $keyfileString) == 1) {
      list($keyType, $key, $comment) = $this->parseRfc4716($keyfileString);
    } else {
      $bits = explode(' ', $keyfileString, 3);

      $keyType = $this->convertSshKeyTypeToEnum($bits[0]);
      $key = $bits[1];
      $comment = $bits[2];
    }

    if(!$key) {
      throw new InvalidArgumentException(_txt('pl.sshkeyauthenticator.format'));
    }
    
    $sk = array();
    $sk['ssh_key_authenticator_id'] = $sshKeyAuthenticatorId;
    $sk['co_person_id'] = $coPersonId;
    $sk['comment'] = $comment;
    $sk['type'] = $keyType;
    $sk['skey'] = $key;
    
    if(!$this->save($sk)) {
      throw new RuntimeException(_txt('er.db.save'));
    }
    
    return $sk;
  }
  /**
   * Convert key type string into an enum.
   *
   * @param  string  SSH key type string parsed from file
   * @return string  SshKeyTypeEnum
   * @throws InvalidArgumentException
   *
   * @since  COmanage Registry v4.2.0
   */
  public function convertSshKeyTypeToEnum($keyTypeString) {
    $keyType = null;

    switch($keyTypeString) {
      case 'ecdsa-sha2-nistp256':
        $keyType = SshKeyTypeEnum::ECDSA;
        break;
      case 'ecdsa-sha2-nistp384':
        $keyType = SshKeyTypeEnum::ECDSA384;
        break;
      case 'ecdsa-sha2-nistp521':
        $keyType = SshKeyTypeEnum::ECDSA521;
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
    }

    if(!$keyType) {
      throw new InvalidArgumentException(_txt('pl.sshkeyauthenticator.type', array(filter_var($keyType,
        FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_HIGH |
        FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK)))); /* was Cake's Sanitize::paranoid */
    }

    return $keyType;
  }

  /**
   * Parse RFC 4716 Secure Sehll (SSH) Public Key File Format.
   * https://www.rfc-editor.org/rfc/rfc4716
   *
   * @param  string  SSH key string read from file
   * @return Array   Key type enum value, array of base64 encoded key and comment
   * @throws InvalidArgumentException
   *
   * @since  COmanage Registry v4.2.0
   */
  protected function parseRfc4716($keyfileString) {

    // RFC 4716 format is line based.
    $lines = explode("\n", $keyfileString);

    $firstLineFound = false;
    $keyFileHeaders = array();
    $lineContinuationInProgress = false;
    $base64EncodedBody = "";

    foreach ($lines as $line) {
      // A Conforming key file would begin immediately with the begin marker
      // but try to be liberal in what we accept so skip any initial lines.
      if(!$firstLineFound) {
        if(preg_match("/^---- BEGIN SSH2 PUBLIC KEY ----.*/", $line) === 1) {
          $firstLineFound = true;
        }
        continue;
      }

      // Parse key file headers with possible continuation lines
      // until the base64-encoded body begins.
      if(empty($base64EncodedBody)) {
        if(!$lineContinuationInProgress) {
          $headerLineParts = preg_split("/:/u", $line, 2);
          if(count($headerLineParts) == 1) {
            $base64EncodedBody .= $line;
          } elseif(count($headerLineParts) == 2) {
            $headerTag = $headerLineParts[0];
            if(substr($headerLineParts[1], -1) == '\\') {
              $lineContinuationInProgress = true;
              $headerValue = substr($headerLineParts[1], 0, -1);
            } else {
              $headerValue = $headerLineParts[1];
              $keyFileHeaders[$headerTag] = $headerValue;
            }
            continue;
          }
        } else {
          if(substr($line, -1) == '\\') {
            $headerValue = $headerValue . substr($line, 0, -1);
          } else {
            $headerValue = $headerValue . $line;
            $lineContinuationInProgress = false;
            $keyFileHeaders[$headerTag] = $headerValue;
          }
          continue;
        }
      } else {
        // Stop parsing when we find the end marker and so ingore any
        // non-conforming end material.
        if(preg_match("/^---- END SSH2 PUBLIC KEY ----.*/", $line) === 1) {
          break;
        }
        $base64EncodedBody .= $line;
        continue;
      }
    }

    // Base-64 decode the body. The resulting binary string has the format
    // 3 null bytes, key type string, 3 null bytes, public key. The key type
    // string needs to further be trimmed to remove non-ascii characters.
    $bodyBinaryString = base64_decode($base64EncodedBody);
    $keyTypeString = trim(explode("\x00\x00\x00", $bodyBinaryString)[1], "\x00..\x1F");

    // Convert the key type string to an enum.
    $keyTypeEnum = $this->convertSshKeyTypeToEnum($keyTypeString);

    // An empty comment is allowed.
    $comment = "";

    if(array_key_exists("Comment", $keyFileHeaders)) {
        $comment = trim($keyFileHeaders["Comment"]);
    } elseif (array_key_exists("Subject", $keyFileHeaders)) {
      $comment = trim($keyFileHeaders["Subject"]);
    }

    return array($keyTypeEnum, $base64EncodedBody, $comment);
  }

  /**
   * Perform SshKey model upgrade steps for version 3.3.0.
   * Resizes the type column.
   * This function should only be called by UpgradeVersionShell.
   *
   * @since  COmanage Registry v3.3.0
   */
  public function _ug330() {
    $dbc = $this->getDataSource();

    $db_driver = explode("/", $dbc->config['datasource'], 2);

    if ($db_driver[0] !== 'Database') {
      throw new RuntimeException("Unsupported db_method: " . $db_driver[0]);
    }

    $db_driverName = $db_driver[1];

    // Nothing to do if not Postgres
    if ($db_driverName !== 'Postgres') {
      return;
    }

    $dbprefix = $dbc->config['prefix'];

    $sql = "ALTER TABLE " . $dbprefix . "ssh_keys ALTER COLUMN type TYPE varchar(32)";

    $this->query($sql);
  }
}
