<?php
/**
 * COmanage Registry Grouper Provisioner Plugin Language File
 *
 * Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_grouper_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_grouper_provisioner_targets.1'  => 'Grouper Provisioner Target',
  'ct.co_grouper_provisioner_targets.pl' => 'Grouper Provisioner Targets',
  
  // Error messages
  'er.grouperprovisioner.connect'        => 'Failed to connect to Grouper web services server',
  
  // Plugin texts
  'pl.grouperprovisioner.info'           => 'The Grouper web services server must be available and the specified credentials must be valid before this configuration can be saved.',
  'pl.grouperprovisioner.serverurl'      => 'Base server URL',
  'pl.grouperprovisioner.serverurl.desc' => 'URL for host (<font style="font-family:monospace">https://hostname[:port]</font>)',
  'pl.grouperprovisioner.contextpath'    => 'Context path',
  'pl.grouperprovisioner.contextpath.desc' => 'Context path for Grouper web services (/grouper-ws)',
  'pl.grouperprovisioner.login'          => 'Login',
  'pl.grouperprovisioner.login.desc'     => 'Login or username for Grouper web services user',
  'pl.grouperprovisioner.password'       => 'Password',
  'pl.grouperprovisioner.password.desc'  => 'Password to use for authentication for Grouper web services user',
  'pl.grouperprovisioner.stem'           => 'Stem or folder',
  'pl.grouperprovisioner.stem.desc'      => 'Full stem name under which to provision groups for this CO'
);
