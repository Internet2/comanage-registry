<?php
/**
 * COmanage Directory Grouper Plugin
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       directory
 * @since         COmanage Directory v0.7
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

// Generate a database view suitable for Grouper to use as
// a JDBC subject source in the Grouper sources.xml configuration.
Configure::write('Grouper.useCOmanageSubjectSource', false);

// Use a Grouper deployment as the COmanage data store for
// group information.
Configure::write('Grouper.COmanage.useGrouperDataSource', false);

// Signal that the data source for CoGroup and CoGroupMembers is not a relational database.
Configure::write('COmanage.groupSqlDataSource', true);

Configure::write('Grouper.scheme', 'https');
Configure::write('Grouper.host', '127.0.0.1');
Configure::write('Grouper.port', 443);
Configure::write('Grouper.user', 'GrouperSystem');
Configure::write('Grouper.pass', 'XXXXXXXX');
Configure::write('Grouper.basePath', 'grouper-system/servicesRest/v2_1_000/');

// Only set to false if necessary for debugging and development.
Configure::write('Grouper.sslVerifyPeer', true);

Configure::write('Grouper.COmanage.baseStem', 'Reference:COmanageDataSource');
Configure::write('Grouper.COmanage.grouperStemDelineator', ':');
Configure::write('Grouper.COmanage.grouperStemDelineatorReplacement', '_');
Configure::write('Grouper.COmanage.ownerRoleDescriptionSuffix', ' - Owner');
Configure::write('Grouper.COmanage.ownerRoleDisplayNameSuffix', ' - Owner');
Configure::write('Grouper.COmanage.ownerRoleDisplayExtensionSuffix', ' - Owner');
Configure::write('Grouper.COmanage.ownerRoleExtensionSuffix', '_Owner');
Configure::write('Grouper.COmanage.ownerRoleNameSuffix', '_Owner');

Configure::write('Grouper.COmanage.groupIdAttributeName', 'Reference:COmanageDataSource:cm_co_groups_id');
Configure::write('Grouper.COmanage.groupCoIdAttributeName', 'Reference:COmanageDataSource:cm_co_groups_co_id');
Configure::write('Grouper.COmanage.groupStatusAttributeName', 'Reference:COmanageDataSource:cm_co_groups_status');

Configure::write('Grouper.COmanage.groupMembersIdAttributeName', 'Reference:COmanageDataSource:cm_co_group_members_id');
Configure::write('Grouper.COmanage.groupMembersCoGroupIdAttributeName', 'Reference:COmanageDataSource:cm_co_group_members_co_group_id');
Configure::write('Grouper.COmanage.groupMembersCoPersonIdAttributeName', 'Reference:COmanageDataSource:cm_co_group_members_co_person_id');
