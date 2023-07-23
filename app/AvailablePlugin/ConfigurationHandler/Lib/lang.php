<?php
/**
 * COmanage Registry Configuration Handler Plugin Language File
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_configuration_handler_texts['en_US'] = array(
  // Titles, per-controller
  'ct.configuration_handlers.1'  => 'Configuration Handler',
  'ct.configuration_handlers.pl' => 'Configuration Handlers',
  
  // Error messages
  'er.configuration_handler.decrypt.failed'            => 'Importing sensitive data failed',
  'er.configuration_handler.config_file.empty'         => 'Configuration File Name must be provided',
  'er.configuration_handler.config_file.invalid-a'     => '"%1$s" file does not exist',
  'er.configuration_handler.config_file.unreadable-a'  => '"%1$s" file is not readable',
  'er.configuration_handler.config_file.noparse-a'     => '"%1$s" file parse failed',
  'er.configuration_handler.models_list.invalid'       => 'Supported Models for export',
  'er.configuration_handler.models_list.invalid-a'     => '"%1$s" Models are not supported.',

  // Plugin texts
  'pl.configuration_handler.arg.models_list'      => 'Supported Models for export',
  'pl.configuration_handler.arg.dry_run'          => 'Run without committing the changes',
  'pl.configuration_handler.arg.config_file'      => 'Configuration File Name',
  'pl.configuration_handler.arg.salt'             => 'Encryption Salt string',
  'pl.configuration_handler.done-a'               => '%1$s Completed',
  'pl.configuration_handler.dryrun.done-a'        => '%1$s Dry Run Completed',
  'pl.configuration_handler.export.start'         => 'Construct and Export Configuration in JSON format',
  'pl.configuration_handler.export.complete'      => 'Configuration exported successfully: %1$s',
  'pl.configuration_handler.import.start'         => 'Import Configuration from JSON format',
  'pl.exportjob.job'                              => 'Run Export Configuration Task',
  'pl.import.job'                                 => 'Run Import Configuration Task',
);
