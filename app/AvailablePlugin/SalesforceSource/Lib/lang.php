<?php
/**
 * COmanage Registry Salesforce Source Plugin Language File
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

$cm_salesforce_source_texts['en_US'] = array(
  // Titles, per-controller
  'ct.salesforce_sources.1'  => 'Salesforce Organizational Identity Source',
  'ct.salesforce_sources.pl' => 'Salesforce Organizational Identity Sources',
  
  // Error messages
  'er.salesforcesource.callback'   => 'Incorrect parameters in callback',
  'er.salesforcesource.code'       => 'Error exchanging code for access token: %1$s',
  'er.salesforcesource.token.none' => 'Access token not found (recheck configuration)',
  
  // Plugin texts
  'pl.salesforcesource.clientid'       => 'Client ID',
  'pl.salesforcesource.clientid.desc'  => 'Client ID ("Consumer Key") obtained from Salesforce',
  'pl.salesforcesource.custom'         => 'Custom Objects',
  'pl.salesforcesource.custom.desc'    => 'Comma separate list of additional objects to query for, keyed on the contact ID (Use format Object:Key, eg: Committee__c:Contact__c)',
  'pl.salesforcesource.limits'         => 'View API Limits',
  'pl.salesforcesource.limits.daily'   => 'Daily',
  'pl.salesforcesource.limits.limit'   => 'Limit',
  'pl.salesforcesource.limits.type'    => 'Limit Type',
  'pl.salesforcesource.limits.used'    => 'Used',
  'pl.salesforcesource.redirect_uri'   => 'Salesforce Redirect URI',
  'pl.salesforcesource.search.contacts' => 'Search Contacts',
  'pl.salesforcesource.search.users'   => 'Search Users',
  'pl.salesforcesource.secret'         => 'Client Secret',
  'pl.salesforcesource.secret.desc'    => 'Client Secret ("Consumer Secret") obtained from Salesforce',
  'pl.salesforcesource.serverurl' => 'Salesforce Base URL',
  'pl.salesforcesource.serverurl.desc' => 'Can be test/sandbox or production, eg: https://cs123.salesforce.com',
  'pl.salesforcesource.token'          => 'Access Token',
  'pl.salesforcesource.token.new'      => 'Obtain New Token',
  'pl.salesforcesource.token.missing'  => 'There is no current OAuth token. You must obtain a new token before the Salesforce API can be queried.',
  'pl.salesforcesource.token.ok'       => 'Access Token Obtained'
);
