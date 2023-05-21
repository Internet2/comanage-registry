<?php
/**
 * COmanage Registry Core API Plugin Language File
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

$cm_core_api_texts['en_US'] = array(
  // Titles, per-controller
  'ct.core_apis.1'  => 'Core API',
  'ct.core_apis.pl' => 'Core APIs',
  
  // Enumeration language texts
  'pl.coreapi.en.api' => array(
    CoreApiEnum::PetitionRead           => 'Petition Read API',
    CoreApiEnum::PersonRead             => 'Person Read API',
    CoreApiEnum::PersonWrite            => 'Person Write API',
    CoreApiEnum::OrganizationRead       => 'Organization Read API',
    CoreApiEnum::OrganizationWrite      => 'Organization Write API',
    CoreApiEnum::DepartmentRead         => 'Department Read API',
    CoreApiEnum::DepartmentWrite        => 'Department Write API',
    CoreApiEnum::MatchCallback          => 'Match Callback API'
  ),

  'pl.coreapi.en.response.type' => array(
    ResponseTypeEnum::Full              => 'Full Profile',
    ResponseTypeEnum::IdentifierList    => 'Identifier Only'
  ),


  // Error messages
  'er.coreapi.coperson'        => 'CoPerson object not found in inbound document',
  'er.coreapi.record'          => '%1$s object not found in inbound document',
  'er.coreapi.coperson.exists' => 'CoPerson exists',
  'er.coreapi.id.invalid'      => 'Invalid record id %1$s',
  'er.coreapi.json'            => 'No JSON document found in request, or document did not successfully parse',
  'er.coreapi.json.invalid'    => 'JSON document missing required fields',
  'er.coreapi.notfound'        => 'No record found for "%1$s"',
  'er.coreapi.sor.notfound'    => 'Organizational Identity Source for SOR Label "%1$s" not found',
  'er.coreapi.sorid.notfound'  => 'No record found for SORID "%1$s"',

  // Plugin texts
  'pl.coreapi.api'                  => 'API',
  'pl.coreapi.api_user.desc'        => 'The API User authorized to make requests to this endpoint',
  'pl.coreapi.identifier.desc'      => 'The Identifier type used to map API locate identifiers to CO Person records',
  'pl.coreapi.info'                 => 'The Core API endpoint is %1$s',
  'pl.coreapi.match.resolved'       => 'Match resolution notification received',
  'pl.coreapi.response.type'        => 'Response Type',
  'pl.coreapi.response.type.desc'   => 'Define the response content granularity',
  'pl.coreapi.rs.edited-a4'         => '%1$s Edited via Core API: %2$s',
  'pl.coreapi.rs.linked'            => 'New Org Identity Linked via Core API',
  'pl.coreapi.expunge.on.del'       => 'Person Expunge on Delete',
);
