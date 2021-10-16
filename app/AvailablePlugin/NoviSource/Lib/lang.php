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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_novi_source_texts['en_US'] = array(
  // Titles, per-controller
  'ct.novi_sources.1'  => 'NoviAMS Organizational Identity Source',
  'ct.novi_sources.pl' => 'NoviAMS Organizational Identity Sources',
  
  // Error messages
  'er.novisource.deleted'  => 'Record is flagged as inactive/deleted',
  'er.novisource.inactive' => 'Member Status is not active (%1$s)',
  
  // Plugin texts
  'pl.novisource.group'    => 'Group'
);
