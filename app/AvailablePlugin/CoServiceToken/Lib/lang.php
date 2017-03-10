<?php
/**
 * COmanage Registry CO Service Token Language File
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

$cm_co_service_token_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_service_tokens.1'  => 'Service Token',
  'ct.co_service_tokens.pl' => 'Service Tokens',
  'ct.co_service_token_settings.1'  => 'Service Token Setting',
  'ct.co_service_token_settings.pl' => 'Service Token Settings',
  
  // Enumerations

  'en.coservicetoken.tokentype' => array(
    CoServiceTokenTypeEnum::Plain08 => 'Plain Text (8 character)',
    CoServiceTokenTypeEnum::Plain15 => 'Plain Text (15 character)'
  ),
  
  // Error messages
  'er.coservicetoken.fail'            => 'Failed to generate token',
  
  // Plugin texts
  'pl.coservicetoken.confirm'         => 'Are you sure you want to generate a new token for %1$s?',
  'pl.coservicetoken.confirm.replace' => 'Are you sure you want to generate a new token for %1$s? The existing token will be invalidated.',
  'pl.coservicetoken.enabled'         => 'Enabled',
  'pl.coservicetoken.generate'        => 'Generate Token',
  'pl.coservicetoken.history'         => 'Token assigned for service "%1$s" (type %2$s)',
  'pl.coservicetoken.token'           => 'Token',
  'pl.coservicetoken.token.info'      => 'Your token has been generated. Please save it into your client now. <ul><li>It will not be possible to view this token again once you leave this page.</li><li>You will need to generate a new token if you lose this one.</li></ul>',
  'pl.coservicetoken.token.no'        => 'Token Not Created',
  'pl.coservicetoken.token.ok'        => 'Token Established',
  'pl.coservicetoken.token.type'      => 'Token Type',
);
