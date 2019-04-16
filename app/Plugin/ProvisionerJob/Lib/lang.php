<?php
/**
 * COmanage Registry Provisioner Job Plugin Language File
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

$cm_provisioner_job_texts['en_US'] = array(
  // Titles, per-controller
//  'ct.identifier_enroller.1'  => 'Identifier Enroller',
//  'ct.identifier_enroller.pl' => 'Identifier Enrollers',
  
  // Error messages
//  'er.identifierenroller.read' => 'Cannot open source file "%1$s" for reading',
  
  // Plugin texts
  'pl.provisionerjob.arg.co_provisioning_target_id' => 'CO Provisioning Target ID',
  'pl.provisionerjob.arg.record_type' => 'Type of record to reprovision',
  'pl.provisionerjob.arg.record_id' => 'Record ID to reprovision, omit to reprovision all records of the specified type',
  'pl.provisionerjob.count'  => 'Reprovisioning %1$s record(s)',
  'pl.provisionerjob.finish' => 'Processed %1$s record(s) (%2$s success, %3$s error)',
  'pl.provisionerjob.start'  => 'Reprovisioning target id %1$s for record type %2$s',
);
