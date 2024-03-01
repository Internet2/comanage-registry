<?php
/**
 * COmanage Registry Password Widget Plugin Language File
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_password_widget_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_password_widgets.1'                       => 'Password Widget',
  'ct.co_password_widgets.pl'                      => 'Password Widgets',

  // Error
  'er.passwordwidget.add'                           => 'The password could not be added.',
  'er.passwordwidget.req.params'                    => 'Incorrect request parameters.',
  'er.passwordwidget.currentpassword'               => 'Current password is required.',
  'er.passwordwidget.no.passwordauthenticators'     => 'There are no password authenticators. You must first create a PasswordAuthenticator before this plugin can be configured.',
  'er.passwordwidget.match'                         => 'The passwords do not match.',
  'er.passwordwidget.maxlength'                     => 'The password may not exceed %1$s characters.',
  'er.passwordwidget.minlength'                     => 'The password must be at least %1$s characters.',

  // Actions
  'pl.passwordwidget.add'                           => 'Add Password',
  'pl.passwordwidget.change'                        => 'Change Password',
  'pl.passwordwidget.edit'                          => 'Edit', // this allows localization
  
  // Fields
  'pl.passwordwidget.fd.authenticator'              => 'Password Authenticator',
  'pl.passwordwidget.fd.authenticator.desc'         => 'Select the Password Authenticator to be used with this widget. Only self-select passwords may be used with this widget.',
  'pl.passwordwidget.fd.password.confirm'           => 'Confirm password',
  'pl.passwordwidget.fd.password.current'           => 'Current password',
  'pl.passwordwidget.fd.password.new'               => 'New password',
  
  //  Information
  'pl.passwordwidget.info.add.success'        => 'The password was added.',
  'pl.passwordwidget.info.change.success'     => 'The password was changed.',
  'pl.passwordwidget.info.dependency'         => 'This widget is dependent on the PasswordAuthenticator plugin.',
  'pl.passwordwidget.info.password.set'       => 'Your password is set.',
  'pl.passwordwidget.info.password.not.set'   => 'No password is set.'
);
