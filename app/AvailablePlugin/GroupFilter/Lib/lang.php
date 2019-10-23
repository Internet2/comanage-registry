<?php
/**
 * COmanage Registry Group Data Filter Plugin Language File
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_group_filter_texts['en_US'] = array(
  // Titles, per-controller
  'ct.group_filters.1'  => 'Group Data Filter',
  'ct.group_filters.pl' => 'Group Data Filters',
  'ct.group_filter_rules.1'  => 'Group Data Filter Rule',
  'ct.group_filter_rules.pl' => 'Group Data Filter Rules',
  
  // Error messages
  'er.groupfilter.cfg' => 'No Group Filter Rules found',
  'er.groupfilter.regex' => 'Regular Expression failed (%1$d)',
  
  // Plugin texts
  'pl.groupfilter.name'      => 'Group Name Pattern',
  'pl.groupfilter.name.desc' => 'Regular expression describing the names of Groups this rule applies to',
);
