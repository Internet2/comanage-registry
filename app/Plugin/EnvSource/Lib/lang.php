<?php
/**
 * COmanage Registry Env Source Plugin Language File
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

$cm_env_source_texts['en_US'] = array(
  // Titles, per-controller
  'ct.env_sources.1'  => 'Env Organizational Identity Source',
  'ct.env_sources.pl' => 'Env Organizational Identity Sources',
  
  // Enumeration language texts
  'pl.envsource.en.mode.dupe' => array(
    EnvSourceDuplicateModeEnum::SORIdentifier    => 'SOR Identifier Match',
    EnvSourceDuplicateModeEnum::AnyIdentifier    => 'Any Identifier Match',
    EnvSourceDuplicateModeEnum::LoginIdentifier  => 'Login Identifier Match',
  ),

  'pl.envsource.en.auth.provider' => array(
    AuthProviderEnum::Shibboleth    => 'Shibboleth SP',
    AuthProviderEnum::Simplesamlphp => 'SimpleSamlPHP SP',
    AuthProviderEnum::Other         => 'Other',
  ),

  // Error messages
  'er.envsource.dupe'           => 'Identifier "%1$s" is already registered',
  'er.envsource.sorid'          => 'Identifier (SORID) variable "%1$s" not set',
  'er.envsource.sorid.cfg'      => 'Identifier (SORID) mapping not defined',
  'er.envsource.sorid.dupe'     => 'SORID "%1$s" is already associated with %2$s',
  'er.envsource.sorid.mismatch' => 'Requested ID does not match %1$s; EnvSource does not support general retrieve operations',
  'er.envsource.token'          => 'Token error',
  
  // Plugin texts
  'pl.envsource.affiliation.def'         => 'Default Affiliation Value',
  'pl.envsource.mode.dupe'               => 'Duplicate Handling Mode',
  'pl.envsource.name.unknown'            => 'Unknownname',
  'pl.envsource.redirect.dupe'           => 'Redirect on Duplicate',
  'pl.envsource.sorid.desc'              => 'This must be set to an environment variable holding a unique identifier for any authenticated user.',
  'pl.envsource.sp.type'                 => 'Web Server SP Provider'
);
