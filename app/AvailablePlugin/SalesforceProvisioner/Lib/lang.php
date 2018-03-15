<?php
/**
 * COmanage Registry Salesforce Provisioner Plugin Language File
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_salesforce_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_salesforce_provisioner_targets.1'  => 'Salesforce Provisioner Target',
  'ct.co_salesforce_provisioner_targets.pl' => 'Salesforce Provisioner Targets',
  
  // Error messages
  'er.salesforceprovisioner.instanceurl' => 'Cannot determine Instance URL',
  
  // Plugin texts
  'pl.salesforceprovisioner.account'      => 'Default Account',
  'pl.salesforceprovisioner.account.desc' => 'If specified, provisioned records are attached to the specified account, which must currently be specified as a Salesforce Object ID',
  'pl.salesforceprovisioner.coperson'     => 'Enable CoPerson Custom Object Support',
  'pl.salesforceprovisioner.coperson.desc' => 'See <a href="https://spaces.internet2.edu/display/COmanage/Salesforce+Provisioning+Plugin#SalesforceProvisioningPlugin-CoPersonCustomObject">the documentation</a> for more information',
  'pl.salesforceprovisioner.coperson.appid' => 'CoPerson Application ID Identifier Type',
  'pl.salesforceprovisioner.coperson.platformid' => 'CoPerson Platform ID Identifier Type',
  'pl.salesforceprovisioner.email.type'    => 'Email Address Type',
  'pl.salesforceprovisioner.email.type.desc' => 'Provision Email Address of this type, otherwise first address found',
  'pl.salesforceprovisioner.instanceurl'   => 'Instance URL',
  'pl.salesforceprovisioner.instanceurl.desc' => 'The Instance URL will be automatically determined, but can also be manually set here',
  'pl.salesforceprovisioner.middlename'    => 'Provision Middle Name',
  'pl.salesforceprovisioner.middlename.desc' => 'When populated, include middle name when constructing the name to send to Salesforce. Requires Middle Name field to be enabled in Salesforce.',
);
