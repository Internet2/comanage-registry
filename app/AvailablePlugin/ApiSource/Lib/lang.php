<?php
/**
 * COmanage Registry API Source Plugin Language File
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

$cm_api_source_texts['en_US'] = array(
  // Titles, per-controller
  'ct.api_sources.1'  => 'API Organizational Identity Source',
  'ct.api_sources.pl' => 'API Organizational Identity Sources',
  
  // Enumeration language texts
  'pl.apisource.en.mode.poll' => array(
    ApiSourcePollModeEnum::Kafka  => 'Apache Kafka',
  ),
  
  // Error messages
  'er.apisource.kafka.meta' =>     'Invalid value for metadata attribute %1$s at offset %2$s (found "%3$s", expecting "%4$s")',
  'er.apisource.kafka.json' =>     'Invalid JSON at offset %1$s',
  'er.apisource.kafka.sorid' =>    'No SORID in message at offset %1$s',
  'er.apisource.label.inuse' =>    'The SOR Label "%1$s" is already in use',
  'er.apisource.sorid.notfound' => 'No record found for specified SORID',
  
  // Plugin texts
  'pl.apisource.api_user.desc'    => 'The API User authorized to make requests to this endpoint, leave blank to disable Push Mode',
  'pl.apisource.info'             => 'The API endpoint for using this plugin in Push Mode is %1$s',
  'pl.apisource.job'              => 'Run API Source Polling',
  'pl.apisource.job.poll.eof'     => 'No further messages available to be processed',
  'pl.apisource.job.poll.finish'  => 'API Source Poll Job completed, processed %1$s messages (%2$s success, %3$s error)',
  'pl.apisource.job.poll.id'      => 'API Source ID to poll',
  'pl.apisource.job.poll.max'     => 'Maximum number of records to process (default is 10)',
  'pl.apisource.job.poll.msg'     => 'Processed message at offset %1$s',
  'pl.apisource.job.poll.start'   => 'Polling for new records (mode %1$s)',
  'pl.apisource.kafka.groupid'    => 'Kafka Consumer Group ID',
  'pl.apisource.kafka.topic'      => 'Kafka Topic',
  'pl.apisource.mode.poll'        => 'Poll Mode',
  'pl.apisource.mode.poll.desc'   => 'The messaging technology to use for polling, leave blank to disable Poll Mode',
  'pl.apisource.mode.push'        => 'Push Mode',
  'pl.apisource.servers.none'     => 'There are no defined Kafka servers to use for this provisioner.',
  'pl.apisource.sor'              => 'SOR Label',
  'pl.apisource.sor.desc'         => 'Alphanumeric label for the API Client/System of Record (will become part of the URL or message metadata)',
);
