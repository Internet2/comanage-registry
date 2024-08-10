<?php
/**
 * COmanage Registry Fiddle Enroller Plugin Language File
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

$cm_fiddle_enroller_texts['en_US'] = array(
  // Titles, per-controller
  'ct.fiddle_enrollers.1'             => 'Fiddle Enroller',
  'ct.fiddle_enrollers.pl'            => 'Fiddle Enrollers',
  
  // Error messages
  'er.fiddleenroller.approver' => 'Petition %1$s has no approver, required by configuration',
  'er.fiddleenroller.role' => 'Petition %1$s has no CO Person Role, required by configuration',
  'er.fiddleenroller.target' => 'CO Person Role already has value for %1$s',
  
  // Plugin texts
  'pl.fiddleenroller.copied' => 'Copied Approver to %1$s',
  'pl.fiddleenroller.copy_approver_to_manager' => 'Copy Approver to Manager',
  'pl.fiddleenroller.copy_approver_to_sponsor' => 'Copy Approver to Sponsor'
);
