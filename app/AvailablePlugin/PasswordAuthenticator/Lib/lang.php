<?php
/**
 * COmanage Registry Password Authenticator Plugin Language File
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_password_authenticator_texts['en_US'] = array(
  // Titles, per-controller
  'ct.password_authenticators.1'  => 'Password Authenticator',
  'ct.password_authenticators.pl' => 'Password Authenticators',
  'ct.passwords.1'                => 'Password',
  'ct.passwords.pl'               => 'Passwords',
  
  // Enumerations
  'en.passwordauthenticator.password_source' => array(
    PasswordAuthPasswordSourceEnum::AutoGenerate => 'Autogenerate',
    PasswordAuthPasswordSourceEnum::External     => 'External',
    PasswordAuthPasswordSourceEnum::SelfSelect   => 'Self Select'
  ),
  
  // Error messages
  'er.passwordauthenticator.current'   => 'Incorrect current password',
  'er.passwordauthenticator.match'     => 'New passwords do not match',
  'er.passwordauthenticator.len.max'   => 'Password cannot be more than %1$s characters',
  'er.passwordauthenticator.len.min'   => 'Password must be at least %1$s characters',
  'er.passwordauthenticator.source'    => 'Password Authenticator is not configured for source type %1$s',
  'er.passwordauthenticator.ssr.cfg'   => 'Configuration not supported for Self Service Reset',
  'er.passwordauthenticator.token.notfound' => 'Reset token not found',
  
  // Plugin texts
  'pl.passwordauthenticator.hash.crypt'     => 'Store as Crypt',
  'pl.passwordauthenticator.hash.crypt.desc' => 'The password will be stored in Crypt format (required for Self Select)',
  'pl.passwordauthenticator.hash.plain'     => 'Store as Plain Text',
  'pl.passwordauthenticator.hash.plain.desc' => 'If enabled, the password will be stored unhashed in the database',
  'pl.passwordauthenticator.hash.ssha'      => 'Store as Salted SHA 1',
  'pl.passwordauthenticator.hash.ssha.desc' => 'If enabled, the password will be stored in Salted SHA 1 format',
  'pl.passwordauthenticator.generate'       => 'To generate a new token, click the <b>Generate</b> button below. This will replace the existing token, if one was already set.',
  'pl.passwordauthenticator.info'           => 'Your new password must be between %1$s and %2$s characters in length.',
  'pl.passwordauthenticator.maxlen'         => 'Maximum Password Length',
  'pl.passwordauthenticator.maxlen.desc'    => 'Must be between 8 and 64 characters (inclusive), default is 64 for Self Select and 16 for Autogenerate',
  'pl.passwordauthenticator.minlen'         => 'Minimum Password Length',
  'pl.passwordauthenticator.minlen.desc'    => 'Must be between 8 and 64 characters (inclusive), default is 8',
  'pl.passwordauthenticator.mod'            => 'Last changed %1$s UTC',
  'pl.passwordauthenticator.noedit'         => 'This password cannot be edited via this interface.',
  'pl.passwordauthenticator.password.again' => 'New Password Again',
  'pl.passwordauthenticator.password.current' => 'Current Password',
  'pl.passwordauthenticator.password.info'  => 'This newly generated password cannot be recovered. If it is lost a new password must be generated. ',
  'pl.passwordauthenticator.password.new'   => 'New Password',
  'pl.passwordauthenticator.password_source' => 'Password Source',
  'pl.passwordauthenticator.reset'          => 'Password "%1$s" Reset',
  'pl.passwordauthenticator.saved'          => 'Password "%1$s" Set',
  'pl.passwordauthenticator.ssr.for'        => 'Select a new password for %1$s.',
  'pl.passwordauthenticator.token.ssr'      => 'Self Service Password Reset'
);
