<?php
/**
 * COmanage Registry Core Job Plugin Language File
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

$cm_core_job_texts['en_US'] = array(
  // Titles, per-controller
  
  // Error messages
  'er.bulkjob.arg.actionArgs' => 'Action specific argument "%1$s" not provided',
  'er.bulkjob.action.unknown' => 'Unknown action "%1$s"',
  'er.bulkjob.record.co'   => 'Record %1$s is not in CO %2$s',
  'er.bulkjob.recordType'  => 'Action "%1$s" does not support record type "%2$s"',
  'er.garbagecollectorjob.object_type.invalid' => 'No "%1$s" Object qualifies for Garbage Collection.',
  'er.garbagecollectorjob.object_type.unknown' => 'Object Type "%1$s" is not supported',


  // Plugin texts
  'pl.bulkjob.arg.action'  => 'Bulk task action',
  'pl.bulkjob.arg.actionArgs' => 'Bulk task action specific arguments',
  'pl.bulkjob.arg.recordType' => 'Record type to apply action to',
  'pl.bulkjob.arg.records' => 'Comma separated list of record IDs',
  'pl.bulkjob.done'        => 'Bulk Processing Finished',
  'pl.bulkjob.job'         => 'Run Bulk Task',
  'pl.bulkjob.updateStatus.done' => 'BulkJob updated %1$s status to %2$s',
  'pl.expirationjob.done'  => 'Expiration Finished',
  'pl.expirationjob.job'   => 'Run Expirations',
  'pl.garbagecollectorjob.arg.object_type' => 'Model Name',
  'pl.garbagecollectorjob.body'     => '(@COMMENT)

For more information, see the Job review at

(@SOURCE_URL)',
  'pl.garbagecollectorjob.done' => 'Garbage collected',
  'pl.garbagecollectorjob.job' => 'Garbage Collector',
  'pl.garbagecollectorjob.none' => 'No Garbage to Collect',
  'pl.garbagecollectorjob.start'  => 'Collecting the Garbage',
  'pl.groupvalidityjob.done' => 'Reprovisioning Finished',
  'pl.groupvalidityjob.job' => 'Process Group Validity',
  'pl.idassignerjob.arg.object_type' => 'Object Type to assign identifiers for',
  'pl.idassignerjob.arg.object_id' => 'CO Person ID to assign identifiers for',
  'pl.idassignerjob.count' => 'Assigning Identifiers for %1$s %2$s record(s)',
  'pl.idassignerjob.finish' => 'Processed %1$s total record(s)',
  'pl.idassignerjob.job'    => 'Run Identifier Assignments',
  'pl.idassignerjob.start'  => 'Assigning identifiers',
  'pl.provisionerjob.arg.co_provisioning_target_id' => 'CO Provisioning Target ID',
  'pl.provisionerjob.arg.provisioning_action' => 'ProvisioningActionEnum',
  'pl.provisionerjob.arg.record_type' => 'Type of record to reprovision',
  'pl.provisionerjob.arg.record_id' => 'Record ID to reprovision, omit to reprovision all records of the specified type',
  'pl.provisionerjob.count'  => 'Reprovisioning %1$s %2$s record(s)',
  'pl.provisionerjob.finish' => 'Processed %1$s record(s) (%2$s success, %3$s error)',
  'pl.provisionerjob.job'    => 'Run Provisioning',
  'pl.provisionerjob.start'  => 'Reprovisioning target id %1$s for record type %2$s',
  'pl.syncjob.arg.force'  => 'Force processing',
  'pl.syncjob.arg.ois_id' => 'Org Identity Source ID to process (or all, by default)',
  'pl.syncjob.done'       => 'Sync Finished',
  'pl.syncjob.job'        => 'Sync Organizational Identity Sources',
  'pl.vetjob.job'         => 'Process Vetting Requests'
);
