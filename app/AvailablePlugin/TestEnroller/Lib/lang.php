<?php
/**
 * COmanage Registry Test Enroller Plugin Language File
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

$cm_test_enroller_texts['en_US'] = array(
  // Titles, per-controller
  'ct.test_enrollers.1'  => 'Test Enroller',
  'ct.test_enrollers.pl' => 'Test Enrollers',
  
  // Error messages
//  'er.filesource.copy' => 'Failed to copy "%1$s" to "%2$s"',
  
  // Plugin texts
  'pl.testenroller.test_start'                 => 'Test Start',
  'pl.testenroller.test_petitioner_attributes' => 'Test Petitoner Attributes'
);
