<?php
/**
 * COmanage Registry Nationality Enroller Plugin Language File
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_nationality_enroller_texts['en_US'] = array(
  // Titles, per-controller
  'ct.nationality_enrollers.1'  => 'Nationality Enroller',
  'ct.nationality_enrollers.pl' => 'Nationality Enrollers',
  
  // Error messages
//  'er.identifierenroller.read' => 'Cannot open source file "%1$s" for reading',
  
  // Plugin texts
  'pl.nationalityenroller.collect_maximum'        => 'Maximum Nationalities',
  'pl.nationalityenroller.collect_maximum.desc'   => 'The maximum number of nationalities that may be self asserted',
  'pl.nationalityenroller.collect_residency'      => 'Collect Permanent Residency',
  'pl.nationalityenroller.collect_residency.desc' => 'Allow permanent residency self assertion',
  'pl.nationalityenroller.collect_residency_authority' => 'Permanent Residency Authority',
  'pl.nationalityenroller.collect_residency_authority.desc' => 'Only collect permanent residency self assertion for the specified authority',
  'pl.nationalityenroller.comment'                => 'Self asserted Identity Document collected via CO Petition %1$s',
  'pl.nationalityenroller.self.nationality'       => 'Nationality %1$s',
  'pl.nationalityenroller.self.residency'         => 'Residency',
  'pl.nationalityenroller.title'                  => 'Nationality Assertions',
  'pl.nationalityenroller.info'                   => 'Please indicate your nationality (or nationalities if more than one) and (if applicable) residency'
);
