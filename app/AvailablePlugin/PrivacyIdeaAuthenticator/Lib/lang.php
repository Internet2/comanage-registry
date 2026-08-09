<?php
/**
 * COmanage Registry PrivacyIDEA Authenticator Plugin Language File
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_privacy_idea_authenticator_texts['en_US'] = array(
  // Titles, per-controller
  'ct.privacy_idea_authenticators.1'  => 'PrivacyIDEA Authenticator',
  'ct.privacy_idea_authenticators.pl' => 'PrivacyIDEA Authenticators',
  'ct.privacy_ideas.1'                => 'PrivacyIDEA Token',
  'ct.privacy_ideas.pl'               => 'PrivacyIDEA Tokens',
  'ct.totp_tokens.1'                  => 'TOTP Token',
  'ct.totp_tokens.pl'                 => 'TOTP Tokens',
  'ct.paper_tokens.1'		      => 'Backup Codes Token',
  'ct.paper_tokens.pl'                => 'Backup Codes Tokens',
  
  // Enumerations
  'pl.privacyideaauthenticator.en.action' => array(
    PrivacyIDEActionEnum::TokenAdded     => 'PrivacyIDEA Token Added',
    PrivacyIDEActionEnum::TokenConfirmed => 'PrivacyIDEA Token Confirmed',
    PrivacyIDEActionEnum::TokenDeleted   => 'PrivacyIDEA Token Deleted',
    PrivacyIDEActionEnum::TokenEdited    => 'PrivacyIDEA Token Edited'
  ),
  
  'en.privacyideaauthenticator.token_type' => array(
    PrivacyIDEATokenTypeEnum::TOTP => 'Time-Based OTP (TOTP)',
    PrivacyIDEATokenTypeEnum::Paper => 'Backup Codes'
  ),
  
  // Error messages
  'er.privacyideaauthenticator.code'       => 'Invalid code, please try again',
  'er.privacyideaauthenticator.identifier' => 'No Identifier of type "%1$s" found for CO Person',
  'er.privacyideaauthenticator.singular'   => 'A token already exists; please delete that one before generating a new one',
  
  // Plugin texts
  'pl.privacyideaauthenticator.alt.google'           => 'QR Code for Google Authenticator',
  'pl.privacyideaauthenticator.alt.paper'            => 'Paper token list',
  'pl.privacyideaauthenticator.fd.identifier_type'   => 'Identifier Type',
  'pl.privacyideaauthenticator.fd.realm'             => 'PrivacyIDEA Realm',
  'pl.privacyideaauthenticator.fd.serial'            => 'Serial',
  'pl.privacyideaauthenticator.fd.token_type'        => 'Token Type',
  'pl.privacyideaauthenticator.fd.validation_server' => 'Validation API Server',
  'pl.privacyideaauthenticator.totpstatus'           => '%1$s token(s) registered, %2$s confirmed',
  'pl.privacyideaauthenticator.paperstatus'          => '%1$s token(s) registered',
  'pl.privacyideaauthenticator.token.confirmed'      => 'Token Confirmed',
  'pl.privacyideaauthenticator.token.deletednoprivacyidea' => 'Token deleted in Registry, but was not found in the Privacy Idea database',
  'pl.privacyideaauthenticator.totp.step1'           => 'First, scan the QR Code to add this token to Google Authenticator',
  'pl.privacyideaauthenticator.totp.step2'           => 'Then, enter the current code from the Google Authenticator app to confirm',
  'pl.privacyideaauthenticator.paper.intro'          => 'Use the backup codes in order, one after the other. Mark off used values.',
  'pl.privacyideaauthenticator.paper.caution'        => 'Backup codes are a weak second factor. Please assure no one has access to these values.  Store them in a safe location',
  'pl.privacyideaauthenticator.paper.warning'        => 'Before you leave this page, please confirm that you have copied your backup codes. YOU WILL NOT SEE THEM AGAIN.',
  'pl.privacyideaauthenticator.paper.dialog'         => 'Before you leave this page you must save your backup codes by copying or printing.',
  'pl.privacyideaauthenticator.paper.dialog.btn'     => 'I understand',
  'pl.privacyideaauthenticator.paper.continue'       => 'Once you have copied your backup codes, you must continue to the next step', 
);
