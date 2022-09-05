<?php
/**
 * COmanage Registry Elector Data Filter Plugin Language File
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_elector_data_filter_texts['en_US'] = array(
  'en.elector_data_filter.tie.break.mode' => array(
    TieBreakReplacementModeEnum::Newest           => 'Newest',
    TieBreakReplacementModeEnum::Oldest           => 'Oldest'
  ),

  'en.elector_data_filter.replacement_mode' => array(
    ReplacementModeEnum::Insert        => 'Insert',
    ReplacementModeEnum::Replace       => 'Replace'
  ),


  // Titles, per-controller
  'ct.elector_data_filters.1'  => 'Elector Data Filter',
  'ct.elector_data_filters.pl' => 'Elector Data Filters',

  'ct.elector_data_filter_precedences.1'  => 'Elector Data Precedence Rule',
  'ct.elector_data_filter_precedences.pl' => 'Elector Data Precedence Rules',

  // Error messages
  'er.elector_data_filter.electfilterid.specify'    => 'Named parameter electfilterid was not found',
  'er.elector_data_filter.id.specify'               => 'Named parameter Id was not found',
  'er.elector_data_filter.cfg'                      => 'Configuration parameters are missing',

  // Plugin texts
  'pl.elector_data_filter.attribute_name'                          => "Subject Attribute",
  'pl.elector_data_filter.attribute_name.desc'                     => "The Subject Attribute to elect a value for",
  'pl.elector_data_filter.outbound_attribute_type'                 => "Elected Attribute Type",
  'pl.elector_data_filter.outbound_attribute_type.desc'            => "The Extended Type that will be assigned to the Elected Attribute Value",
  'pl.elector_data_filter.tie_break_mode'                          => "Tie Break Mode",
  'pl.elector_data_filter.tie_break_mode.desc'                     => "Choose what will happen when more than one attribute matches a preference.",
  'pl.elector_data_filter.replacement_mode'                        => "Election Mode",
  'pl.elector_data_filter.replacement_mode.desc'                   => "In insert mode, the elected value is added to the existing set of values (result set = n+1). In replace mode, only the elected value is provided (result set = 1)",
  'pl.elector_data_filter_precedence.precedence'                   => '%1$s Precedence',
  'pl.elector_data_filter_precedence.order'                        => 'Order #%1$s',
  'pl.elector_data_filter_precedence.order.desc'                   => 'The order in which this Precedence Rule will be processed',
  'pl.elector_data_filter_precedence.plugin.desc'                  => 'If set, this Rule only applies to Attribute Values that originated from this Organizational Identity Source',
  'pl.elector_data_filter_precedence.inbound_attribute_type'       => "Source Attribute Type",
  'pl.elector_data_filter_precedence.inbound_attribute_type.desc'  => "The Extended Type this Precedence Rule applies to",
);
