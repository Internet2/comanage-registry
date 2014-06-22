<?php
/**
 * COmanage Registry LDAP Provisioner Plugin Language File
 *
 * Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_ldap_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_ldap_provisioner_targets.1'  => 'LDAP Provisioner Target',
  'ct.co_ldap_provisioner_targets.pl' => 'LDAP Provisioner Targets',
  
  // Error messages
  'er.ldapprovisioner.basedn'         => 'Base DN not found',
  'er.ldapprovisioner.basedn.gr.none' => 'When the <font style="font-family:monospace">groupOfNames</font> object class is enabled, the Group Base DN must be defined.',
  'er.ldapprovisioner.connect'        => 'Failed to connect to LDAP server',
  'er.ldapprovisioner.dn.component'   => 'DN component %1$s not available',
  'er.ldapprovisioner.dn.config'      => 'DN configuration invalid',
  'er.ldapprovisioner.dn.noattr'      => 'DN attributes not found for CO Person %1$s',
  'er.ldapprovisioner.dn.none'        => 'DN not found for CO Person %1$s',
  
  // Plugin texts
  'pl.ldapprovisioner.attr.hasmember.desc'  => 'Applies to Group',
  'pl.ldapprovisioner.attr.ismemberof.desc' => 'Applies to Person',
  'pl.ldapprovisioner.attrs'          => 'Attributes',
  'pl.ldapprovisioner.attrs.desc'     => 'Attributes to export to this LDAP server',
  'pl.ldapprovisioner.basedn'         => 'People Base DN',
  'pl.ldapprovisioner.basedn.desc'    => 'Base DN to provision People entries under',
  'pl.ldapprovisioner.basedn.gr'      => 'Group Base DN',
  'pl.ldapprovisioner.basedn.gr.desc' => 'Base DN to provision Group entries under (requires <font style="font-family:monospace">groupOfNames</font> objectclass)',
  'pl.ldapprovisioner.binddn'         => 'Bind DN',
  'pl.ldapprovisioner.binddn.desc'    => 'DN to authenticate as to manage entries',
  'pl.ldapprovisioner.dnattr'         => 'People DN Attribute Name',
  'pl.ldapprovisioner.dnattr.desc'    => 'When constructing People DNs, use this attribute name for the unique component',
  'pl.ldapprovisioner.dntype'         => 'People DN Identifier Type',
  'pl.ldapprovisioner.dntype.desc'    => 'When constructing People DNs, use the value associated with this identifier type as the value for the unique component<br />(If multiple values are available for the attribute, the value selected is non-deterministic)',
  'pl.ldapprovisioner.fd.useorgval'   => 'Use value from Organizational Identity',
  'pl.ldapprovisioner.info'           => 'The LDAP server must be available and the specified credentials must be valid before this configuration can be saved.',
  'pl.ldapprovisioner.password'       => 'Password',
  'pl.ldapprovisioner.password.desc'  => 'Password to use for authentication',
  'pl.ldapprovisioner.oc.enable'      => 'Enable <font style="font-family:monospace">%1$s</font> objectclass',
  'pl.ldapprovisioner.opts'           => 'Attribute Options',
  'pl.ldapprovisioner.opts.desc'      => 'XXX link to documentation',
  'pl.ldapprovisioner.opt.lang'       => 'Enable attribute options for languages',
  'pl.ldapprovisioner.opt.role'       => 'Enable attribute options for roles',
  'pl.ldapprovisioner.serverurl'      => 'Server URL',
  'pl.ldapprovisioner.serverurl.desc' => 'URL to connect to (<font style="font-family:monospace">ldap[s]://hostname[:port]</font>)',
  'pl.ldapprovisioner.types.all'      => 'All Types'
);
