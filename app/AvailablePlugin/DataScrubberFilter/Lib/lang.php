<?php
/**
 * COmanage Registry Data Scrubber Filter Plugin Language File
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_data_scrubber_filter_texts['en_US'] = array(
  // Titles, per-controller
  'ct.data_scrubber_filters.1'  => 'Data Scrubber Filter',
  'ct.data_scrubber_filters.pl' => 'Data Scrubber Filters',
  'ct.data_scrubber_filter_attributes.1' => 'Data Scrubber Filter Attribute',
  'ct.data_scrubber_filter_attributes.pl' => 'Data Scrubber Filter Attributes',
  
  // Error messages
//  'er.groupfilter.cfg' => 'No Group Filter Rules found',
//  'er.groupfilter.regex' => 'Regular Expression failed (%1$d)',
  
  // Plugin texts
//  'pl.groupfilter.name'      => 'Group Name Pattern',
//  'pl.groupfilter.name.desc' => 'Regular expression describing the names of Groups this rule applies to',
);
