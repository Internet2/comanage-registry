<?php
/**
 * COmanage Registry Unix Cluster Plugin Language File
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

$cm_unix_cluster_texts['en_US'] = array(
  // Titles, per-controller
  'ct.unix_cluster_accounts.1'  => 'Unix Cluster Account',
  'ct.unix_cluster_accounts.pl' => 'Unix Cluster Accounts',
  'ct.unix_cluster_groups.1'  => 'Unix Cluster Group',
  'ct.unix_cluster_groups.pl' => 'Unix Cluster Groups',
  'ct.unix_clusters.1'  => 'Unix Cluster',
  'ct.unix_clusters.pl' => 'Unix Clusters',
  
  // Enumeration language texts
  'pl.unixcluster.en.sync' => array(
    UnixClusterSyncEnum::Full   => 'Track CO Person Attributes',
    UnixClusterSyncEnum::Manual => 'Manual'
  ),
  
  // Error messages
  'er.unixcluster.group.already' => 'The specified group is already attached to this Unix Cluster',
  'er.unixcluster.gecos.pname' => 'Primary Name not found for GECOS field',
  'er.unixcluster.ud.already' => 'The %1$s "%2$s" is already in use in this Unix Cluster',
  
  // Plugin texts
  'pl.unixcluster.accounts' => '%1$s Accounts',
  'pl.unixcluster.accounts.registered' => '%1$s Account(s) Registered',
  'pl.unixcluster.fd.default_co_group_id' => 'Default Group',
  'pl.unixcluster.fd.co_group_id.new.name' => '%1$s UnixCluster Group',
  'pl.unixcluster.fd.co_group_id.new.desc' => 'Group created for %1$s automatically by UnixCluster',
  'pl.unixcluster.fd.default_co_group_id.create' => 'Create a New Group For Each Account',
  'pl.unixcluster.fd.default_co_group_id.desc' => 'Unless overridden, Unix Accounts will be given this default group. See the <a href="https://spaces.at.internet2.edu/display/COmanage/Unix+Cluster+Plugin#UnixClusterPlugin-SettingaDefaultGroupForEachUnixAccount">documentation</a> for more information.',
  'pl.unixcluster.fd.default_shell' => 'Default Login Shell',
  'pl.unixcluster.fd.gecos' => 'GECOS',
  'pl.unixcluster.fd.gid_type' => 'GID Type',
  'pl.unixcluster.fd.gid_type.desc' => 'CO Group Identifier type used to populate Group IDs',
  'pl.unixcluster.fd.groupname_type' => 'Group Name Type',
  'pl.unixcluster.fd.groupname_type.desc' => 'CO Group Identifier type used to populate Group Names',
  'pl.unixcluster.fd.home_directory' => 'Home Directory',
  'pl.unixcluster.fd.homedir_prefix' => 'Home Directory Prefix',
  'pl.unixcluster.fd.homedir_prefix.desc' => 'Home Directories generated for Unix Accounts in this Cluster will be prefixed with this string, eg "/home"',
  'pl.unixcluster.fd.homedir_subdivisions' => 'Home Directory Subdivisions',
  'pl.unixcluster.fd.homedir_subdivisions.desc' => 'When automatically generating Home Directories, subdivide the directory tree by this many levels (eg: at level 2, generate "/home/a/b/abc123")',
  'pl.unixcluster.fd.login_shell' => 'Login Shell',
  'pl.unixcluster.fd.primary_co_group_id' => 'Primary Group',
  'pl.unixcluster.fd.sync_mode' => 'Sync Mode',
  'pl.unixcluster.fd.sync_mode.desc' => 'Default method for how to handle updates to CO Person attributes used to generate Unix Cluster attributes',
  'pl.unixcluster.fd.sync_mode.acct.desc' => 'Method for how to handle updates to CO Person attributes used to generate Unix Cluster attributes',
  'pl.unixcluster.fd.uid' => 'User ID (UID) Number',
  'pl.unixcluster.fd.uid_type' => 'UID Type',
  'pl.unixcluster.fd.uid_type.desc' => 'CO Person Identifier type used to autopopulate (numeric) UID',
  'pl.unixcluster.fd.username' => 'Username',
  'pl.unixcluster.fd.username_type' => 'Username Type',
  'pl.unixcluster.fd.username_type.desc' => 'CO Person Identifier type used to autopopulate Unix Username',
  'pl.unixcluster.rs.added' => 'Added Unix Cluster Account (unix cluster id %1$s, username %2$s, uid %3$s)',
  'pl.unixcluster.rs.deleted' => 'Deleted Unix Cluster Account (unix cluster id %1$s, username %2$s, uid %3$s)',
  'pl.unixcluster.rs.edited' => 'Edited Unix Cluster Account (from unix cluster id %1$s, username %2$s, uid %3$s to unix cluster id %4$s, username %5$s, uid %6$s)'
);
