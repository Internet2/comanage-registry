<?php
/**
 * COmanage Registry Recovery Widget Plugin Language File
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_recovery_widget_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_recovery_widgets.1'  => 'Recovery Widget',
  'ct.co_recovery_widgets.pl' => 'Recovery Widgets',
  
  // Error messages
  'er.recoverywidget.disabled'        => 'Requested task is not enabled',
  'er.recoverywidget.lookup.notfound' => 'No matching CO Person was found for "%1$s"',
  'er.recoverywidget.ssr.inactive'    => 'CO Person is not active and Authenticator cannot be reset',
  'er.recoverywidget.ssr.locked'      => 'Authenticator is locked and cannot be reset',
  'er.recoverywidget.ssr.multiple'    => 'Could not resolve a single CO Person for "%1$s"',
  'er.recoverywidget.token.expired'   => 'Reset token expired',
  'er.recoverywidget.token.notfound'  => 'Reset token not found',
  
  // Plugin texts
  'pl.recoverywidget.authenticator'                   => 'Self Service Reset Authenticator',
  'pl.recoverywidget.authenticator_reset_template'    => 'Message Template for Self Service Reset',
  'pl.recoverywidget.enable_confirmation_resend'      => 'Enable Confirmation Resend',
  'pl.recoverywidget.enable_reenter_flow'             => 'Enable Re-enter Enrollment Flow',
  'pl.recoverywidget.history.authenticator_reset'     => 'Authenticator reset token sent to %1$s',
  'pl.recoverywidget.history.confirmation_resend'     => 'Resend of confirmation requested via RecoveryWidget',
  'pl.recoverywidget.history.identifier_lookup'       => 'Identifier lookup sent to %1$s',
  'pl.recoverywidget.identifier_template'             => 'Message Template for Identifier Lookup',
  'pl.recoverywidget.op.authenticator_change'         => 'I know my password and want to change it',
  'pl.recoverywidget.op.authenticator_reset'          => 'I know my username but I forgot my password',
  'pl.recoverywidget.op.confirmation_resend'          => 'I was trying to signup but didn’t receive (or lost) the confirmation email',
  'pl.recoverywidget.op.identifier_lookup'            => 'I already signed up but forgot my username',
  'pl.recoverywidget.op.reenter_flow'                 => 'I was in the middle of signing up but I closed my browser or did not finish',
  'pl.recoverywidget.task.authenticator_reset'        => 'Request Authenticator Reset',
  'pl.recoverywidget.task.confirmation_resend'        => 'Request Confirmation Resend',
  'pl.recoverywidget.task.identifier_lookup'          => 'Request Identifier',
  'pl.recoverywidget.task.reenter_flow'               => 'Restart Enrollment Flow',
  'pl.recoverywidget.lookup.authenticator_reset.info' => 'Enter a verified email address or registered identifier to proceed.</p>If you still know your password, click <a href="%1$s">here</a> to directly select a new password.',
  'pl.recoverywidget.lookup.confirmation_resend.info' => 'Enter the email address or authenticated identifier associated with the enrollment.',
  'pl.recoverywidget.lookup.identifier_lookup.info'   => 'Enter a verified email address or a different registered identifier to proceed.',
  'pl.recoverywidget.lookup.reenter_flow.info'        => 'Enter the email address or authenticated identifier associated with the enrollment.',
  'pl.recoverywidget.lookup.q'                        => 'Email Address or Identifier',
  'pl.recoverywidget.sent'                            => 'If a valid record matching the Email Address or Identifier was found, an email has been sent with further information.',
  'pl.recoverywidget.ssr.redirect'                    => 'Redirect on Self Service Reset',
  'pl.recoverywidget.ssr.redirect.desc'               => 'URL to redirect to on successful self service reset',
  'pl.recoverywidget.ssr.validity'                    => 'Self Service Reset Token Validity',
  'pl.recoverywidget.ssr.validity.desc'               => 'Time in minutes the reset token is valid for'
);
