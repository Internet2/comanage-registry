<?php
/**
 * COmanage Registry ORCID Source Plugin Language File
 *
 * Copyright (C) 2016 SCG
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
 * @copyright     Copyright (C) 2016 SCG
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

$cm_orcid_source_texts['en_US'] = array(
  // Titles, per-controller
  'ct.orcid_sources.1'  => 'ORCID Organizational Identity Source',
  'ct.orcid_sources.pl' => 'ORCID Organizational Identity Sources',
  
  // Error messages
  'er.orcidsource.code'       => 'Error exchanging code for ORCID and access token: %1$s',
  'er.orcidsource.search'     => 'Search request returned %1$s',
  'er.orcidsource.token.api'  => 'Access token not found in API response',
  'er.orcidsource.token.none' => 'Access token not configured (try resaving configuration)',
  
  // Plugin texts
  'pl.orcidsource.clientid'       => 'Client ID',
  'pl.orcidsource.clientid.desc'  => 'Client ID obtained from registering with the ORCID Public ID',
  'pl.orcidsource.description'    => 'Description',
  'pl.orcidsource.linked'         => 'Obtained ORCID "%1$s" via authenticated OAuth flow',
  'pl.orcidsource.redirect_url'   => 'ORCID Redirect URI',
  'pl.orcidsource.secret'         => 'Client Secret',
  'pl.orcidsource.secret.desc'    => 'Client Secret obtained from registering with the ORCID Public ID'
);
