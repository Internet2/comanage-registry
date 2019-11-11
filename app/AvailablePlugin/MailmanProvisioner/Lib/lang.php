<?php
/**
 * COmanage Registry Mailman3 Provisioner Plugin Language File
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

$cm_mailman_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_mailman_provisioner_targets.1'  => 'Mailman Provisioner Target',
  'ct.co_mailman_provisioner_targets.pl' => 'Mailman Provisioner Targets',

  // Enumerations
  'en.mailmanprovisioner.unmanagedemail' => array(
    MailmanProvUnmanEmailEnum::Ignore => 'Ignore',
    MailmanProvUnmanEmailEnum::Remove => 'Remove'
  ),
  
  // Error messages
  'er.mailmanprovisioner.listid.none'   => 'Mailman List ID for CO Email List %1$s not found',
  'er.mailmanprovisioner.pref.none'     => 'Unable to find a preferred email address',
  //'er.mailmanprovisioner.list'          => 'Failed to create or update list: %1$s',
  
  // Plugin texts
  'pl.mailmanprovisioner.adminuser'     => 'Mailman3 Admin Username',
  'pl.mailmanprovisioner.desc.default'  => 'Managed by COmanage',
  'pl.mailmanprovisioner.domain'        => 'Mailman3 List Domain',
  'pl.mailmanprovisioner.domain.desc'   => 'Mailing lists managed by this provisioner will be created under this domain (eg: lists.mydomain.org).',
  'pl.mailmanprovisioner.info'          => 'The Mailman server must be available and the specified credentials must be valid before this configuration can be saved.',
  'pl.mailmanprovisioner.info2'         => 'If the specified domain is not already configured on the Mailman server, it will be created.',
  'pl.mailmanprovisioner.password'      => 'Mailman3 Admin Password',
  'pl.mailmanprovisioner.pref_email'    => 'Preferred Email Type',
  'pl.mailmanprovisioner.pref_email.desc' => 'If set, email addresses of this type will be used (when available) as the preferred Mailman delivery address.',
  'pl.mailmanprovisioner.rs.list'       => 'Mailman list created (id: %1$s)',
  'pl.mailmanprovisioner.rs.list.del'   => 'Mailman list deleted (id: %1$s)',
  'pl.mailmanprovisioner.rs.pref'       => 'Set Mailman preferred email address to %1$s',
  'pl.mailmanprovisioner.rs.sub'        => 'Subscribed to Mailman list (%1$s, role=%2$s)',
  'pl.mailmanprovisioner.rs.unsub'      => 'Unsubscribed from Mailman list (%1$s, role=%2$s)',
  'pl.mailmanprovisioner.serverurl'     => 'Mailman3 Admin Server Base URL',
  'pl.mailmanprovisioner.serverurl.desc' => 'Do not include API version, eg: http://myhost.org:8001',
  'pl.mailmanprovisioner.uem'            => 'Unmanaged Email Mode',
  'pl.mailmanprovisioner.uem.desc'       => 'How to handle <a href="https://spaces.at.internet2.edu/x/sQvABg">unmanaged email addresses</a>'
);
