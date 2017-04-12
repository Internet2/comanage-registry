<?php
/**
 * COmanage Registry netFORUM Source Plugin Language File
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

$cm_net_forum_source_texts['en_US'] = array(
  // Titles, per-controller
  'ct.net_forum_sources.1'  => 'NetFORUM Organizational Identity Source',
  'ct.net_forum_sources.pl' => 'NetFORUM Organizational Identity Sources',
  
  // Plugin texts
  'pl.netforumsource.info'           => 'The netFORUM server must be available and the specified credentials must be valid before this configuration can be saved.',
  'pl.netforumsource.name.sort'      => 'Sort Name (Family Given)',
  'pl.netforumsource.password'       => 'Password',
  'pl.netforumsource.password.desc'  => 'NetFORUM XML Password',
  'pl.netforumsource.queryevents'    => 'Query Events For Group Mappings',
  'pl.netforumsource.queryevents.desc' => 'If true, query the events dataset to make available for Group Mappings',
  'pl.netforumsource.serverurl'      => 'Server URL',
  'pl.netforumsource.serverurl.desc' => 'URL prefix for WSDLs, including schema and host but no path components (eg: https://uat.netforumpro.com)',
  'pl.netforumsource.username'       => 'Username',
  'pl.netforumsource.username.desc'  => 'NetFORUM XML Username',
);
