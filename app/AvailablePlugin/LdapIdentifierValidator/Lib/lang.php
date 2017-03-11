<?php
/**
 * COmanage Registry LDAP Identifier Validator Plugin Language File
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_ldap_identifier_validator_texts['en_US'] = array(
  // Titles, per-controller
  'ct.ldap_identifier_validators.1'  => 'LDAP Identifier Validator',
  'ct.ldap_identifier_validators.pl' => 'LDAP Identifier Validators',
  
  // Error messages
  'er.ldapidentifier.basedn'         => 'Base DN not found',
  'er.ldapidentifier.connect'        => 'Failed to connect to LDAP server',
  'er.ldapidentifier.nocount'        => 'Received unexpected result (no count)',
  
  // Plugin texts
  'pl.ldapidentifier.basedn'         => 'Base DN',
  'pl.ldapidentifier.basedn.desc'    => 'Base DN to use when searching',
  'pl.ldapidentifier.binddn'         => 'Bind DN',
  'pl.ldapidentifier.binddn.desc'    => 'DN to authenticate as to manage entries',
  'pl.ldapidentifier.filter'         => 'Search Filter',
  'pl.ldapidentifier.filter.desc'    => 'Search filter to use to check for availability. Use %s as placeholder for new identifier. (eg: uid=%s)',
  'pl.ldapidentifier.info'           => 'The LDAP server must be available and the specified credentials (if provided) must be valid before this configuration can be saved.',
  'pl.ldapidentifier.password'       => 'Password',
  'pl.ldapidentifier.password.desc'  => 'Password to use for authentication',
  'pl.ldapidentifier.serverurl'      => 'Server URL',
  'pl.ldapidentifier.serverurl.desc' => 'URL to connect to (<font style="font-family:monospace">ldap[s]://hostname[:port]</font>)',
);
