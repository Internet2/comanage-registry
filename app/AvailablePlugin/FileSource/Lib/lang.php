<?php
/**
 * COmanage Registry File Source Plugin Language File
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

$cm_file_source_texts['en_US'] = array(
  // Titles, per-controller
  'ct.file_sources.1'  => 'File Organizational Identity Source',
  'ct.file_sources.pl' => 'File Organizational Identity Sources',
  
  // Enumeration language texts
  'pl.filesource.en.format' => array(
    FileSourceFormat::CSV1  => "CSV v1 (legacy)",
    FileSourceFormat::CSV2  => "CSV v2",
  ),

  // Error messages
  'er.filesource.copy'          => 'Failed to copy "%1$s" to "%2$s"',
  'er.filesource.coid'          => 'CO Id unknown',
  'er.filesource.header'        => 'Could not parse configuration header',
  'er.filesource.invalid.label' => 'Header label "%1$s" format is invalid',
  'er.filesource.invalid.column' => 'Column "%1$s" does not belong to "%2$s"',
  'er.filesource.invalid.type' => 'Type "%1$s" of "%2$s" is invalid',
  'er.filesource.read'          => 'Cannot open source file "%1$s" for reading',
  'er.filesource.threshold'     => '%1$s of %2$s records changed (%3$s %%, including new records), exceeding threshold of %4$s %% - processing canceled',
  'er.filesource.threshold.cfg' => 'Warning Threshold requires Archive Directory',
  
  // Plugin texts
  'pl.filesource.archivedir'       => 'Archive Directory',
  'pl.filesource.archivedir.desc'  => 'If specified, a limited number of prior copies of the source file will be stored here.',
  'pl.filesource.filepath'         => 'File Path',
  'pl.filesource.format'           => 'File Format',
  'pl.filesource.info'             => 'The specified file must be readable before this configuration can be saved.',
  'pl.filesource.threshold_warn'          => 'Warning Threshold',
  'pl.filesource.threshold_warn.desc'     => 'If the number of changed records exceeds the specified percentage, a warning will be generated and processing will stop (requires <i>Archive Directory</i>)',
  'pl.filesource.threshold_override'      => 'Warning Threshold Override',
  'pl.filesource.threshold_override.desc' => 'If set, the next Full sync will ignore the Abort Threshold'
);
