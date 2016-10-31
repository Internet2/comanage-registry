<?php
/**
 * COmanage Registry LDAP Source Plugin Language File
 *
 * Copyright (C) 2016 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2016 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_ldap_source_texts['en_US'] = array(
  // Titles, per-controller
  'ct.ldap_sources.1'  => 'LDAP Organizational Identity Source',
  'ct.ldap_sources.pl' => 'LDAP Organizational Identity Sources',
  
  // Error messages
  'er.ldapsource.basedn'         => 'Base DN not found',
  'er.ldapsource.connect'        => 'Failed to connect to LDAP server',
  
  // Plugin texts
  'pl.ldapsource.basedn'         => 'Base DN',
  'pl.ldapsource.basedn.desc'    => 'Base DN to use when searching',
  'pl.ldapsource.binddn'         => 'Bind DN',
  'pl.ldapsource.binddn.desc'    => 'DN to authenticate as to manage entries',
  'pl.ldapsource.info'           => 'The LDAP server must be available and the specified credentials (if provided) must be valid before this configuration can be saved.',
  'pl.ldapsource.key_attribute'  => 'Key Attribute',
  'pl.ldapsource.key_attribute.desc' => 'Attribute to use to uniquely and persistently identify an individual, even if the DN changes (eg: employeeNumber)',
  'pl.ldapsource.password'       => 'Password',
  'pl.ldapsource.password.desc'  => 'Password to use for authentication',
  'pl.ldapsource.search_filter'  => 'Search Filter',
  'pl.ldapsource.search_filter.desc' => 'Search filter to apply to all queries',
  'pl.ldapsource.serverurl'      => 'Server URL',
  'pl.ldapsource.serverurl.desc' => 'URL to connect to (<font style="font-family:monospace">ldap[s]://hostname[:port]</font>)',
  'pl.ldapsource.uidattr'        => 'UID Attribute',
  'pl.ldapsource.uidattr.desc'   => 'Attribute to map to identifier of type UID',
);
