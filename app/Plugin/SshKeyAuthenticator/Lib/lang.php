<?php
/**
 * COmanage Registry SSH Key Authenticator Plugin Language File
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_ssh_key_authenticator_texts['en_US'] = array(
  // Titles, per-controller
  'ct.ssh_key_authenticators.1'  => 'SSH Key Authenticator',
  'ct.ssh_key_authenticators.pl' => 'SSH Key Authenticators',
  'ct.ssh_keys.1' =>            'SSH Key',
  'ct.ssh_keys.pl' =>           'SSH Keys',
  
  // Enumeration language texts
  'pl.sshkeyauthenticator.en.action' => array(
    SshKeyActionEnum::SshKeyAdded                 => 'SSH Key Added',
    SshKeyActionEnum::SshKeyDeleted               => 'SSH Key Deleted',
    SshKeyActionEnum::SshKeyEdited                => 'SSH Key Edited',
    SshKeyActionEnum::SshKeyUploaded              => 'SSH Key Uploaded'
  ),
  
  'pl.sshkeyauthenticator.en.sshkey.type' => array(
    SshKeyTypeEnum::DSA      => 'DSA',
    SshKeyTypeEnum::ECDSA    => 'ECDSA',
    SshKeyTypeEnum::ECDSA384 => 'ECDSA384',
    SshKeyTypeEnum::ECDSA521 => 'ECDSA521',
    SshKeyTypeEnum::ED25519  => 'ed25519',
    SshKeyTypeEnum::RSA      => 'RSA',
    SshKeyTypeEnum::RSA1     => 'RSA1'
  ),
  
  // Error messages
  //'er.certificateauthenticator.current'   => 'Current password is required',
  
  // Plugin texts
  'pl.sshkeyauthenticator.fd.comment' => 'Comment',
  'pl.sshkeyauthenticator.fd.skey' => 'Key',
  'pl.sshkeyauthenticator.fd.type' => 'Key Type',
  'pl.sshkeyauthenticator.format' =>  'File does not appear to be a valid ssh public key',
  'pl.sshkeyauthenticator.private' => 'Uploaded file appears to be a private key',
  'pl.sshkeyauthenticator.registered' => '%1$s SSH Key(s) registered',
  'pl.sshkeyauthenticator.rfc4716' => 'RFC4716 format public keys are not currently supported',
  'pl.sshkeyauthenticator.type' =>    'Unknown SSH key type "%1$s"'
);
