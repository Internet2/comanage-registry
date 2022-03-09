<?php
/**
 * COmanage Registry Self-Service Email Widget Plugin Language File
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

$cm_self_service_email_widget_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_self_email_widgets.1'  => 'Self Service Email Widget',
  'ct.co_self_email_widgets.pl' => 'Self Service Email Widgets',
  
  // Plugin texts
  'pl.self_email_widget.add' => 'Add an email address',
  'pl.self_email_widget.error.status' => 'HTTP Status: ',
  'pl.self_email_widget.error.message' => 'Message: ',
  'pl.self_email_widget.error.401' => '<h6>Your session has timed out.</h6><p>Please reload this page to reauthenticate and try again.</p>',
  'pl.self_email_widget.error.500' => 'There was an unknown error.',
  'pl.self_email_widget.fd.email' => 'Email Address',
  'pl.self_email_widget.fd.type' => 'Type',
  'pl.self_email_widget.modal.body.add.fail' => 'Adding email address failed.',
  'pl.self_email_widget.modal.body.add.none' => 'An email address may not be blank.',
  'pl.self_email_widget.modal.body.add.success' => 'The email address was added.',
  'pl.self_email_widget.modal.body.common.fail' => 'There was an error.',
  'pl.self_email_widget.modal.body.delete' => 'Are you sure you want to delete this email address?',
  'pl.self_email_widget.modal.body.delete.fail' => 'Deleting email address failed.',
  'pl.self_email_widget.modal.body.delete.success' => 'The email address was deleted.',
  'pl.self_email_widget.modal.body.refresh.fail' => 'Refreshing your data from the server failed. Please reload this page to try again.',
  'pl.self_email_widget.modal.body.update.fail' => 'There was an error. The email addresses did not save.',
  'pl.self_email_widget.modal.body.update.nochange' => 'There are no changes to save.',
  'pl.self_email_widget.modal.body.update.success' => 'The email addresses were updated.',
  'pl.self_email_widget.modal.title.add.fail' => 'Add failed',
  'pl.self_email_widget.modal.title.add.none' => 'Missing address',
  'pl.self_email_widget.modal.title.add.success' => 'Address added',
  'pl.self_email_widget.modal.title.common.fail' => 'Request failed',
  'pl.self_email_widget.modal.title.update.fail' => 'Update failed',
  'pl.self_email_widget.modal.title.update.nochange' => 'Email addresses unchanged',
  'pl.self_email_widget.modal.title.update.success' => 'Update successful',
  'pl.self_email_widget.modal.title.delete' => 'Delete email address?',
  'pl.self_email_widget.modal.title.delete.fail' => 'Delete failed',
  'pl.self_email_widget.modal.title.delete.success' => 'Address deleted',
  'pl.self_email_widget.modal.title.refresh.fail' => 'Refresh failed',
  'pl.self_email_widget.make.primary'   => 'Make Primary',
  'pl.self_email_widget.noconfig' => 'This widget requires no configuration.',
  'pl.self_email_widget.none'     => 'No email addresses',
  'pl.self_email_widget.primary'   => 'Primary',
  'pl.self_email_widget.return'   => 'Return'
);
