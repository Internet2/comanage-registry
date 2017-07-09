<?php
/**
 * COmanage Registry MediaWiki Provisioner Plugin Language File
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
 * @version       $Id$
 */

global $cm_lang, $cm_media_wiki_provisioner_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_media_wiki_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_media_wiki_provisioner_targets.1'  => 'MediaWiki Provisioner Target',
  'ct.co_media_wiki_provisioner_targets.pl' => 'MediaWiki Provisioner Targets',
  
  // Error messages
  'er.mediawikiprovisioner.connect'        => 'Failed to connect to MediaWiki API server',
  
  // Plugin texts
  'pl.mediawikiprovisioner.info'             => 'The MediaWiki API server must be available and the specified credentials must be valid before this configuration can be saved.',
  'pl.mediawikiprovisioner.api_url'        => 'API URL',
  'pl.mediawikiprovisioner.api_url.desc' => 'URL for API (<font style="font-family:monospace">https://hostname[:port]/w/api.php</font>)',
  'pl.mediawikiprovisioner.consumer_key'   => 'Consumer Key',
  'pl.mediawikiprovisioner.consumer_key.desc'     => 'OAuth consumer key used to connect to the API server',
  'pl.mediawikiprovisioner.consumer_secret'   => 'Consumer Secret',
  'pl.mediawikiprovisioner.consumer_secret.desc'     => 'OAuth consumer secret used to connect to the API server',
  'pl.mediawikiprovisioner.access_token'   => 'Access Token',
  'pl.mediawikiprovisioner.access_token.desc'     => 'Access token used to connect to the API server',
  'pl.mediawikiprovisioner.access_secret'   => 'Access Token Secret',
  'pl.mediawikiprovisioner.access_secret.desc'     => 'Access token secret used to connect to the API server',

  'pl.mediawikiprovisioner.user_name_identifier'=> 'MediaWiki username identifier',
  'pl.mediawikiprovisioner.user_name_identifier.desc' => 'CO person identifier used by MediaWiki as the username',
);
