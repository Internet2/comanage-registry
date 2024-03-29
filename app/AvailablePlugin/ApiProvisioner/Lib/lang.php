<?php
/**
 * COmanage Registry API Provisioner Plugin Language File
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

$cm_api_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_api_provisioner_targets.1'  => 'API Provisioner Target',
  'ct.co_api_provisioner_targets.pl' => 'API Provisioner Targets',
  
  // Enumeration language texts
  'pl.apiprovisioner.en.sync' => array(
    ApiProvisionerModeEnum::POST => 'HTTP POST',
    ApiProvisionerModeEnum::PUT  => 'HTTP PUT'
  ),
  
  // Error messages
  'er.apiprovisioner.id.none'        => 'No identifier of type %1$s found for CO Person',
  
  // Plugin texts
  'pl.apiprovisioner.identifier_type'           => 'Identifier Type',
  'pl.apiprovisioner.identifier_type.desc'      => 'If specified, the CO Person Identifier of this type will be appended to the request URL',
  'pl.apiprovisioner.include_attributes'        => 'Include Attributes',
  'pl.apiprovisioner.include_attributes.desc'   => 'If true, all attributes will be included in the message, otherwise only a URL to the subject is included',
  'pl.apiprovisioner.mode'                      => 'Protocol Mode'
);
