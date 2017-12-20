<?php
/**
 * COmanage Registry Certificate Authenticator Plugin Language File
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

$cm_certificate_authenticator_texts['en_US'] = array(
  // Titles, per-controller
  'ct.certificate_authenticators.1'  => 'X.509 Certificate Authenticator',
  'ct.certificate_authenticators.pl' => 'X.509 Certificate Authenticators',
  'ct.certificates.1'                => 'X.509 Certificate',
  'ct.certificates.pl'               => 'X.509 Certificates',
  
  // Error messages
  //'er.certificateauthenticator.current'   => 'Current password is required',
  
  // Plugin texts
  'pl.certificateauthenticator.dn.issuer' => 'Issuer DN',
  'pl.certificateauthenticator.dn.subject' => 'Subject DN',
  'pl.certificateauthenticator.registered' => '%1$s certificate(s) registered',
);
