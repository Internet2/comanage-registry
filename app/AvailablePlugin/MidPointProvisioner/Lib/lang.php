<?php
/**
 * COmanage Registry MidPoint Provisioner Plugin Language File
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

$cm_mid_point_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_mid_point_provisioner_targets.1'             => 'MidPoint Provisioner Target',
  'ct.co_mid_point_provisioner_targets.pl'            => 'MidPoint Provisioner Targets',

  'pl.midpointprovisioner.info'                       => 'The MidPoint server must be available and the specified credentials must be valid before this configuration can be saved.',
  'pl.midpointprovisioner.serverurl'                  => 'MidPoint REST API base URL',
  'pl.midpointprovisioner.serverurl.desc'             => 'Do not include API version, eg: https://localhost:8443/midpoint',
  'pl.midpointprovisioner.username'                   => 'MidPoint REST API username',
  'pl.midpointprovisioner.password'                   => 'MidPoint REST API password',

  'pl.midpointprovisioner.ssl_allow_self_signed'      => 'ssl_allow_self_signed',
  'pl.midpointprovisioner.ssl_allow_self_signed.desc' => 'Allow self-signed certificates.',
  'pl.midpointprovisioner.ssl_verify_host'            => 'ssl_verify_host',
  'pl.midpointprovisioner.ssl_verify_host.desc'       => 'Require verification of hostname.',
  'pl.midpointprovisioner.ssl_verify_peer'            => 'ssl_verify_peer',
  'pl.midpointprovisioner.ssl_verify_peer.desc'       => 'Require verification of SSL certificate.',
  'pl.midpointprovisioner.ssl_verify_peer_name'       => 'ssl_verify_peer_name',
  'pl.midpointprovisioner.ssl_verify_peer_name.desc'  => 'Require verification of peer name.',


  'pl.midpointprovisioner.user_name_identifier'       => 'MidPoint user name identifier',
  'pl.midpointprovisioner.user_name_identifier.desc'  => 'CO person identifier used by MidPoint as the user name',

);