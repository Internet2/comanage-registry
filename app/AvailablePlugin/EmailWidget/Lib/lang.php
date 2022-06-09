<?php
/**
 * COmanage Registry Email Widget Plugin Language File
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_email_widget_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_email_widgets.1'  => 'Self Service Email Widget',
  'ct.co_email_widgets.pl' => 'Self Service Email Widgets',
  
  // Plugin texts
  'pl.email_widget.add' => 'Add an email address',
  'pl.email_widget.email.subject' => 'Your email verification code',
  'pl.email_widget.email.body' => 'Please copy the following code into the Email Address panel in COmanage Registry to verify your email address.',
  'pl.email_widget.error.status' => 'HTTP Status: ',
  'pl.email_widget.error.message' => 'Message: ',
  'pl.email_widget.error.401' => 'Your session has timed out. Please log in.',
  'pl.email_widget.error.500' => 'There was an unknown error.',
  'pl.email_widget.error.invalid' => 'Please enter a valid email address.',
  'pl.email_widget.fd.body' => 'Body',
  'pl.email_widget.fd.email' => 'Add Email Address',
  'pl.email_widget.fd.message.template' => 'Message template',
  'pl.email_widget.fd.message.template.desc' => 'The email message template to be used. If empty, the following default message will be sent:',
  'pl.email_widget.fd.type' => 'Type',
  'pl.email_widget.fd.type.default' => 'Default Email Type',
  'pl.email_widget.fd.type.default.desc' => 'The default type assigned to email addresses',
  'pl.email_widget.fd.type.hide' => 'Hide Email Type',
  'pl.email_widget.fd.type.hide.desc' => 'Don\'t display the email type field in the Add Email form.',
  'pl.email_widget.fd.token'   => 'Verification Token',
  'pl.email_widget.fd.token.error' => 'The token you have entered does not match. Please try again or start over.',
  'pl.email_widget.fd.token.msg'   => 'Please check your email at the address you just submitted. Enter the email verification token in the field below and click \"Verify\" to finish adding your new email address.',
  'pl.email_widget.modal.body.add.fail' => 'Adding email address failed.',
  'pl.email_widget.modal.body.add.none' => 'An email address may not be blank.',
  'pl.email_widget.modal.body.add.success' => 'The email address was added.',
  'pl.email_widget.modal.body.common.fail' => 'There was an error.',
  'pl.email_widget.modal.body.delete' => 'Are you sure you want to delete this email address?',
  'pl.email_widget.modal.body.delete.fail' => 'Deleting email address failed.',
  'pl.email_widget.modal.body.delete.success' => 'The email address was deleted.',
  'pl.email_widget.modal.body.refresh.fail' => 'Refreshing your data from the server failed. Please reload this page to try again.',
  'pl.email_widget.modal.body.update.fail' => 'There was an error. The email addresses did not save.',
  'pl.email_widget.modal.body.update.nochange' => 'There are no changes to save.',
  'pl.email_widget.modal.body.update.success' => 'The email addresses were updated.',
  'pl.email_widget.modal.title.add.fail' => 'Add failed',
  'pl.email_widget.modal.title.add.none' => 'Missing address',
  'pl.email_widget.modal.title.add.success' => 'Address added',
  'pl.email_widget.modal.title.common.fail' => 'Request failed',
  'pl.email_widget.modal.title.update.fail' => 'Update failed',
  'pl.email_widget.modal.title.update.nochange' => 'Email addresses unchanged',
  'pl.email_widget.modal.title.update.success' => 'Update successful',
  'pl.email_widget.modal.title.delete' => 'Delete email address?',
  'pl.email_widget.modal.title.delete.fail' => 'Delete failed',
  'pl.email_widget.modal.title.delete.success' => 'Address deleted',
  'pl.email_widget.modal.title.refresh.fail' => 'Refresh failed',
  'pl.email_widget.make.primary'   => 'Make Primary',
  'pl.email_widget.noconfig' => 'This widget requires no configuration.',
  'pl.email_widget.none'     => 'No email addresses',
  'pl.email_widget.primary'  => 'Primary',
  'pl.email_widget.return'   => 'Return',
  'pl.email_widget.timeout'  => 'The token has expired. Please start over.'
);
