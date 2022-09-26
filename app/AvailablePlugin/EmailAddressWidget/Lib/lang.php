<?php
/**
 * COmanage Registry Email Address Widget Plugin Language File
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

$cm_email_address_widget_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_email_address_widgets.1'                       => 'Email Address Widget',
  'ct.co_email_address_widgets.pl'                      => 'Email Address Widgets',

  // Error
  'er.emailaddresswidget.fd.token'                      => 'The token you have entered does not match. Please try again or start over.',
  'er.emailaddresswidget.timeout'                       => 'The token has expired. Please start over.',
  'er.emailaddresswidget.req.params'                    => 'Incorrect request parameters.',

  // Actions
  'pl.emailaddresswidget.add'                           => 'Add an email address',
  'pl.emailaddresswidget.email.subject'                 => 'Your email verification code',
  'pl.emailaddresswidget.email.body'                    => 'Please copy the following code into the Email Address panel in COmanage Registry to verify your email address.',
  'pl.emailaddresswidget.noconfig'                      => 'This widget requires no configuration.',
  'pl.emailaddresswidget.none'                          => 'No email addresses',
  'pl.emailaddresswidget.return'                        => 'Return',
  'pl.emailaddresswidget.rs.mail.added.verified'        => 'Added Verified Email Address %1$s',
  'pl.emailaddresswidget.rs.added-a'                    => '"%1$s" Added via Email Address Widget',

  // Fields
  'pl.emailaddresswidget.fd.body'                       => 'Body',
  'pl.emailaddresswidget.fd.email'                      => 'Add Email Address',
  'pl.emailaddresswidget.fd.message.template'           => 'Message template',
  'pl.emailaddresswidget.fd.message.template.desc'      => 'The email message template to be used. If empty, the following default message will be sent:',
  'pl.emailaddresswidget.fd.type'                       => 'Type',
  'pl.emailaddresswidget.fd.type.default'               => 'Default Email Type',
  'pl.emailaddresswidget.fd.type.default.desc'          => 'The default type assigned to email addresses',
  'pl.emailaddresswidget.fd.type.hide'                  => 'Hide Email Type',
  'pl.emailaddresswidget.fd.type.hide.desc'             => 'Don\'t display the email type field in the Add Email form.',
  'pl.emailaddresswidget.fd.token'                      => 'Verification Token',
  'pl.emailaddresswidget.fd.token.msg'                  => 'Please check your email at the address you just submitted. Enter the email verification token in the field below and click \"Verify\" to finish adding your new email address.',
  'pl.emailaddresswidget.fd.verification.validity'      => 'Verification Validity (Minutes)',
  'pl.emailaddresswidget.fd.verification.validity.desc' => 'When verifying an email address, the length of time (in minutes) the verification code is valid for (default is 10 minutes)',

  //  Modal
  'pl.emailaddresswidget.modal.body.add.success'        => 'The email address was added.',
  'pl.emailaddresswidget.modal.body.delete'             => 'Are you sure you want to delete this email address?',
  'pl.emailaddresswidget.modal.body.delete.success'     => 'The email address was deleted.',
  'pl.emailaddresswidget.modal.body.update.nochange'    => 'There are no changes to save.',
  'pl.emailaddresswidget.modal.body.update.success'     => 'The email addresses were updated.',
  'pl.emailaddresswidget.modal.title.add.none'          => 'Missing address',
  'pl.emailaddresswidget.modal.title.add.success'       => 'Address added',
  'pl.emailaddresswidget.modal.title.update.nochange'   => 'Email addresses unchanged',
  'pl.emailaddresswidget.modal.title.update.success'    => 'Update successful',
  'pl.emailaddresswidget.modal.title.delete'            => 'Delete email address?',
  'pl.emailaddresswidget.modal.title.delete.success'    => 'Address deleted',
);
