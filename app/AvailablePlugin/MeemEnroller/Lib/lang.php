<?php
/**
 * COmanage Registry Meem Enroller Plugin Language File
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

$cm_meem_enroller_texts['en_US'] = array(
  // Titles, per-controller
  'ct.meem_enrollers.1'             => 'MEEM Enroller',
  'ct.meem_enrollers.pl'            => 'MEEM Enrollers',
  
  // Error messages
  'er.meemenroller.api.coperson'    => 'Active CO Person not found',
  'er.meemenroller.disabled'        => 'Reminders are not enabled',
  'er.meemenroller.env_idp'         => 'IdP Identifier Indicator is configured, but %1$s is empty',
  
  // Plugin texts
  'pl.meemenroller.api_user.desc'                  => 'The API User authorized to query MFA state, leave blank to disable',
  'pl.meemenroller.enable_reminder_page'           => 'Enable MFA Setup Reminder Splash Page',
  'pl.meemenroller.env_idp'                        => 'IdP Identifier Indicator',
  'pl.meemenroller.env_idp.desc'                   => 'Name of the Environment Variable that indicates an identifier for the Enrollee\'s Identity Provider',
  'pl.meemenroller.env_mfa'                        => 'MFA Assertion Indicator',
  'pl.meemenroller.env_mfa.desc'                   => 'Name of the Environment Variable that indicates if MFA was asserted for the Enrollee',
  'pl.meemenroller.mfa_exempt_co_group_id'         => 'MFA Exemption CO Group',
  'pl.meemenroller.mfa_exempt_co_group_id.desc'    => 'CO Group whose members are exempt from MFA',
  'pl.meemenroller.mfa_exempt.added'               => 'Added Enrollee to MFA Exemption Group for %1$s hours (MEEM)',
  'pl.meemenroller.mfa_exempt.deleted'             => 'Removed Enrollee from MFA Exemption Group (MEEM)',
  'pl.meemenroller.mfa_initial_exemption'          => 'Initial MFA Exemption',
  'pl.meemenroller.mfa_initial_exemption.desc'     => 'Number of hours after enrollment during which the Enrollee is exempt from MFA',
  'pl.meemenroller.mfa_co_enrollment_flow_id'      => 'MFA Enrollment Flow',
  'pl.meemenroller.mfa_co_enrollment_flow_id.desc' => 'The Enrollment Flow to use to establish MFA for the Enrollee',
  'pl.meemenroller.remind.enroll'                  => 'Enroll Now',
  'pl.meemenroller.remind.later'                   => 'Later',
  'pl.meemenroller.remind.message'                 => 'You will need to enroll in MFA. Do you want to set up MFA now?',
  'pl.meemenroller.remind.message.req'             => 'You must enroll in MFA before you can access any services. Click <b>Enroll Now</b> to continue.',
  'pl.meemenroller.remind.message.soon'            => 'You will need to enroll in MFA before %1$s. Do you want to set up MFA now?',
  'pl.meemenroller.remind.title'                   => 'Enroll in MFA',
  'pl.meemenroller.return_url_allowlist'           => 'Return URL Allow List',
  'pl.meemenroller.return_url_allowlist.desc'      => 'Permitted regular expressions (one per line) for <i>return</i> parameter in Setup Reminder Splash Page'
);
