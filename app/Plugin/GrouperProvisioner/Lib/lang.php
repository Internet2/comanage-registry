<?php
/**
 * COmanage Registry Grouper Provisioner Plugin Language File
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
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('GrouperCouProvisioningStyle', 'GrouperProvisioner.Lib');
  
global $cm_lang, $cm_grouper_provisioner_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_grouper_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_grouper_provisioner_targets.1'  => 'Grouper Provisioner Target',
  'ct.co_grouper_provisioner_targets.pl' => 'Grouper Provisioner Targets',
  
  // Error messages
  'er.grouperprovisioner.connect'        => 'Failed to connect to Grouper web services server',
  'er.grouperprovisioner.subject'        => 'Could not determine Grouper subject identifier',
  
  // Plugin texts
  'pl.grouperprovisioner.info'             => 'The Grouper web services server must be available and the specified credentials must be valid before this configuration can be saved.',
  'pl.grouperprovisioner.serverurl'        => 'Base server URL',
  'pl.grouperprovisioner.serverurl.desc' => 'URL for host (<font style="font-family:monospace">https://hostname[:port]</font>)',
  'pl.grouperprovisioner.contextpath'    => 'Context path',
  'pl.grouperprovisioner.contextpath.desc' => 'Context path for Grouper web services (/grouper-ws)',
  'pl.grouperprovisioner.login'          => 'Login',
  'pl.grouperprovisioner.login.desc'     => 'Login or username for Grouper web services user',
  'pl.grouperprovisioner.password'       => 'Password',
  'pl.grouperprovisioner.password.desc'  => 'Password to use for authentication for Grouper web services user',
  'pl.grouperprovisioner.stem'           => 'Stem or folder',
  'pl.grouperprovisioner.stem.desc'      => 'Full Grouper stem name under which to provision groups for this CO',
  'pl.grouperprovisioner.legacy'         => 'Legacy subject',
  'pl.grouperprovisioner.legacy.desc'    => 'Use the legacy internal COmanage subject for Grouper (deprecated)',
  'pl.grouperprovisioner.loginidentifier'=> 'Grouper subject UI login identifier',
  'pl.grouperprovisioner.loginidentifier.desc' => 'CO person identifier used by Grouper for subject login to the Grouper UI',
  'pl.grouperprovisioner.emailidentifier'=> 'Grouper subject email',
  'pl.grouperprovisioner.emailidentifier.desc' => 'CO person email address used by Grouper for subject email address',
  'pl.grouperprovisioner.subjectidentifier'=> 'Grouper subject identifier',
  'pl.grouperprovisioner.subjectidentifier.desc' => 'CO person identifier used by Grouper as the unique subject',
  'pl.grouperprovisioner.subjectview'      => 'Subject source view',
  'pl.grouperprovisioner.subjectview.desc' => 'Name for the Grouper subject source view in database',

  // Shell texts
  'sh.grouperprovisioner.ug.110.gp' =>       'Migrating GrouperProvisioner configurations',
);
