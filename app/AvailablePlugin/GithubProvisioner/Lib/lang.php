<?php
/**
 * COmanage Registry Github Provisioner Plugin Language File
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
 * @since         COmanage Registry v0.9.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_github_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_github_provisioner_targets.1'  => 'Github Provisioner Target',
  'ct.co_github_provisioner_targets.pl' => 'Github Provisioner Targets',
  
  // Error messages
  'er.githubprovisioner.access_token'   => 'Access token not received',
  'er.githubprovisioner.github_id'      => 'No GitHub identifier found',
  'er.githubprovisioner.orgs.none'      => 'There are no owned Organizations available to be managed',
  'er.githubprovisioner.team.notfound'  => 'No corresponding GitHub team found for this group',
  'er.githubprovisioner.state'          => 'State token mismatch',
  
  // Plugin texts
  'pl.githubprovisioner.added'          => 'Added to GitHub Team "%1$s"',
  'pl.githubprovisioner.callback_url'   => 'GitHub Callback URL',
  'pl.githubprovisioner.client_id'      => 'GitHub Client ID',
  'pl.githubprovisioner.client_id.desc' => 'The Client ID provided by GitHub after registering this application.',
  'pl.githubprovisioner.client_secret'  => 'GitHub Client Secret',
  'pl.githubprovisioner.client_secret.desc' => 'The Client Secret provided by GitHub after registering this application.',
  'pl.githubprovisioner.github_org'    => 'GitHub Organization',
  'pl.githubprovisioner.github_org.desc' => 'The GitHub Organization to be managed by this provisioner.',
  'pl.githubprovisioner.github_user'    => 'GitHub Username',
  'pl.githubprovisioner.github_user.desc' => 'The GitHub Username to be used by this provisioner. The GitHub user must have sufficient privileges for the operations enabled.',
  'pl.githubprovisioner.oauth'          => 'After clicking <i>Save</i>, you may be asked by GitHub to authenticate and/or authorize COmanage in order to continue. You should also review <a href="https://github.com/site/terms">GitHub\'s Terms and Conditions</a> before proceeding.',
  'pl.githubprovisioner.org.select'     => 'Please select an Organization to manage.',
  'pl.githubprovisioner.provision_group_members' => 'Provision Group Memberships to GitHub',
  'pl.githubprovisioner.provision_group_members.desc' => 'If enabled, active COmanage users with a "GitHub" Identifier will be provisioned into GitHub Teams whose names match COmanage groups.',
  'pl.githubprovisioner.provision_ssh_keys' => 'Provision SSH Keys to GitHub',
  'pl.githubprovisioner.provision_ssh_keys.desc' => 'If enabled, COmanage users with a "GitHub" Identifier and with associated SSH Keys will have their keys provisioned to their GitHub accounts.',
  'pl.githubprovisioner.register'       => 'First, <a href="https://github.com/settings/applications/new">register COmanage as an application with GitHub</a>.<br />
                                            Set the <i>Authorization callback URL</i> to be <pre>%1$s</pre><br />
                                            After registering, copy the Client ID and Client Secret values assigned by GitHub here.',
  'pl.githubprovisioner.remove_unknown_members' => 'Remove Unknown Members from GitHub Teams',
  'pl.githubprovisioner.remove_unknown_members.desc' => 'If enabled, members of GitHub teams who do not correspond to a COmanage user with a "GitHub" Identifier will be removed.',
  'pl.githubprovisioner.removed'        => 'Removed from GitHub Team "%1$s"',
  'pl.githubprovisioner.token.none'     => 'No access token has been received, and so provisioning cannot be completed. To obtain a token, please click "Save".',
  'pl.githubprovisioner.token.ok'       => 'Access token verified and configuration updated',
  'pl.githubprovisioner.type'           => 'The "GitHub" (case sensitive) Extended Type should be created before using this provisioner. <a href="%1$s">Click here</a> to add it.'
);
