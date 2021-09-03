<?php
/**
 * COmanage Registry Jira Provisioner Plugin Language File
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
 * @since         COmanage Registry v3.3.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_jira_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_jira_provisioner_targets.1'  => 'Jira Provisioner Target',
  'ct.co_jira_provisioner_targets.pl' => 'Jira Provisioner Targets',
  
  // Error messages
  'er.jiraprovisioner.id.none'        => 'No identifier of type %1$s found for CO Person',
  'er.jiraprovisioner.mail.none'      => 'No email address found for CO Person',
  
  // Plugin texts
  'pl.jiraprovisioner.pref_username'           => 'Username Identifier Type',
  'pl.jiraprovisioner.pref_username.desc'      => 'Identifier type used as the Jira user name',
  'pl.jiraprovisioner.query_by_username'       => 'Also Query By Username',
  'pl.jiraprovisioner.query_by_username.desc'  => 'Query for user by key and username',
  'pl.jiraprovisioner.provisioning_group'      => 'Provisioning Group',
  'pl.jiraprovisioner.provisioning_group.desc' => 'If selected, only members of this group will be provisioned',
  'pl.jiraprovisioner.deactivate'              => 'Delete Is Deactivate',
  'pl.jiraprovisioner.deactivate.desc'         => 'Do not delete, instead set active to false',
  'pl.jiraprovisioner.group_type'              => 'Group Identifier Type',
  'pl.jiraprovisioner.group_type.desc'         => 'If selected, only groups with this identifier type will be provisioned',
  'pl.jiraprovisioner.group_name'              => 'Group Name From Identifier',
  'pl.jiraprovisioner.group_name.desc'         => 'Use the value of the identifier as the name of group in Jira',
  'pl.jiraprovisioner.remove_unknown'          => 'Remove Unknown Users',
  'pl.jiraprovisioner.remove_unknown.desc'     => 'Remove unknown users from Jira groups',
);
