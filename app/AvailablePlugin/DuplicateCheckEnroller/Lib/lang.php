<?php
/**
 * COmanage Registry Duplicate Check Enroller Plugin Language File
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_duplicate_check_enroller_texts['en_US'] = array(
  // Titles, per-controller
  'ct.duplicate_check_enrollers.1'               => 'Duplicate Account Enroller',
  'ct.duplicate_check_enrollers.pl'              => 'Duplicate Account Enrollers',

  // Error texts
  'er.duplicate_check_enrollers.wedgeid.specify'      => 'Named parameter wedgeid was not found',
  'er.duplicate_check_enrollers.remote_user.notfound' => 'Remote User could not be retrieved from env',
  'er.duplicate_check_enrollers.cfg.notfound'         => 'Configuration could not be retrieved',


  // Fields
  'fd.duplicate_check_enrollers.env_remote_user'          => 'Enviromental Variable',
  'fd.duplicate_check_enrollers.env_remote_user.desc'     => 'Enviromental Variable used to save the REMOTE USER, defaults to ePPN type',
  'fd.duplicate_check_enrollers.identifier_type'          => 'Identifier Type',
  'fd.duplicate_check_enrollers.identifier_type.desc'     => 'Identifier Type to query for (e.g. ePPN)',
  'fd.duplicate_check_enrollers.redirect_url'             => 'Redirect URL',
  'fd.duplicate_check_enrollers.redirect_url.desc'        => 'Where to redirect after a duplicate account is confirmed. If left empty it will redirect to CO Person Canvas.',
);
