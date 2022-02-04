<?php
/**
 * COmanage Registry Service Eligibility Enroller Plugin Language File
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

$cm_service_eligibility_enroller_texts['en_US'] = array(
  // Titles, per-controller
  'ct.service_eligibilities.1'          => 'Service Eligibility',
  'ct.service_eligibilities.pl'         => 'Service Eligibilities',
  'ct.service_eligibility_enrollers.1'  => 'Service Eligibility Enroller',
  'ct.service_eligibility_enrollers.pl' => 'Service Eligibility Enrollers',
  'ct.service_eligibility_settings.1'   => 'Service Eligibility Setting',
  'ct.service_eligibility_settings.pl'  => 'Service Eligibility Settings',
  
  // Error messages
  'er.serviceeligibilityenroller.exists' => 'The requested service is already enabled for the requested role',
  'er.serviceeligibilityenroller.none'   => 'The requested service was not enabled for the requested role',
  
  // Plugin texts
  'pl.serviceeligibilityenroller.added'             => 'Added',
  'pl.serviceeligibilityenroller.allow_multiple'    => 'Allow Multiple Services',
  'pl.serviceeligibilityenroller.info'              => 'Please select from the available services',
  'pl.serviceeligibilityenroller.history.add'       => 'Service Eligibility "%1$s" added',
  'pl.serviceeligibilityenroller.history.remove'    => 'Service Eligibility "%1$s" removed',
  'pl.serviceeligibilityenroller.require_selection' => 'Require Selection at Enrollment'
);
