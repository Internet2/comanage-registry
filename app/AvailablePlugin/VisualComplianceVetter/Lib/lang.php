<?php
/**
 * COmanage Registry Visual Compliance Enroller Plugin Language File
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

$cm_visual_compliance_vetter_texts['en_US'] = array(
  // Titles, per-controller
  'ct.visual_compliance_vetters.1'  => 'Visual Compliance Vetter',
  'ct.visual_compliance_vetters.pl' => 'Visual Compliance Vetters',
  
  // Error messages
//  'er.visualcompliancevetter.foo' => 'Placeholder',
  
  // Plugin texts
  // Generally, s* fields are search request fields, the others are result fields
  'pl.visualcompliancevetter.field.alerttype' => 'Alert Type',
  'pl.visualcompliancevetter.field.category' => 'Category',
  'pl.visualcompliancevetter.field.dp_id' => 'DP ID',
  'pl.visualcompliancevetter.field.isequence' => 'Sequence',
  'pl.visualcompliancevetter.field.list' => 'List',
  'pl.visualcompliancevetter.field.name' => 'Name',
  'pl.visualcompliancevetter.field.nomatch' => 'No Match',
  'pl.visualcompliancevetter.field.notes' => 'Notes',
  'pl.visualcompliancevetter.field.riskcountry' => 'Risk Country',
  'pl.visualcompliancevetter.field.saddress1' => 'Address Line 1',
  'pl.visualcompliancevetter.field.saddress2' => 'Address Line 2',
  'pl.visualcompliancevetter.field.saddress3' => 'Address Line 3',
  'pl.visualcompliancevetter.field.scity' => 'City',
  'pl.visualcompliancevetter.field.scompany' => 'Company',
  'pl.visualcompliancevetter.field.scountry' => 'Country',
  'pl.visualcompliancevetter.field.selective1' => 'Elective 1',
  'pl.visualcompliancevetter.field.selective2' => 'Elective 2',
  'pl.visualcompliancevetter.field.selective3' => 'Elective 3',
  'pl.visualcompliancevetter.field.selective4' => 'Elective 4',
  'pl.visualcompliancevetter.field.selective5' => 'Elective 5',
  'pl.visualcompliancevetter.field.selective6' => 'Elective 6',
  'pl.visualcompliancevetter.field.selective7' => 'Elective 7',
  'pl.visualcompliancevetter.field.selective8' => 'Elective 8',
  'pl.visualcompliancevetter.field.sname' => 'Name',
  'pl.visualcompliancevetter.field.soptionalid' => 'Optional ID',
  'pl.visualcompliancevetter.field.ssearchtype' => 'Search Type',
  'pl.visualcompliancevetter.field.sstate' => 'State',
  'pl.visualcompliancevetter.field.szip' => 'ZIP Code',
  'pl.visualcompliancevetter.result.passed' => 'Passed',
  'pl.visualcompliancevetter.result._Y' => 'Yellow',
  'pl.visualcompliancevetter.result._R' => 'Red',
  'pl.visualcompliancevetter.result.DR' => 'Double Red',
  'pl.visualcompliancevetter.result.TR' => 'Triple Red',
  'pl.visualcompliancevetter.search_request' => 'Search Request',
  'pl.visualcompliancevetter.server' => 'Visual Compliance Server',
);
