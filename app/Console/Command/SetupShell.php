<?php
/**
 * COmanage Registry Setup Shell
 *
 * Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  App::import('Model', 'ConnectionManager');

  class SetupShell extends AppShell {
    var $uses = array('Co', 'CoGroup', 'CoGroupMember', 'CoOrgIdentityLink', 'CoPerson', 'CoPersonRole', 'Identifier', 'OrgIdentity');
    
    function main()
    {
      // Prepare a new installation. Since there's a decent chance the user will
      // ctrl-c before we get started, prompt for info first.
      
      $gn = $this->in(_txt('se.cf.admin.given'));
      $sn = $this->in(_txt('se.cf.admin.sn'));
      $user = $this->in(_txt('se.cf.admin.user'));
      $salt = $this->in(_txt('se.cf.admin.salt'));
      $seed = $this->in(_txt('se.cf.admin.seed'));

      // Since we'll be doing some direct DB manipulation, find the table prefix
      $prefix = "";
      $db =& ConnectionManager::getDataSource('default');

      if(isset($db->config['prefix']))
        $prefix = $db->config['prefix'];
      
      $this->out("- " . _txt('se.users.view'));
      $this->Identifier->query("CREATE VIEW " . $prefix . "users AS
SELECT a.username as username, a.password as password, a.id as api_user_id
FROM cm_api_users a
UNION SELECT i.identifier as username, '*' as password, null as api_user_id
FROM cm_identifiers i
WHERE i.login=true;
");

      // Determine if we should create a view for Grouper to
      // use as a JDBC source in the Grouper sources.xml configuration
      // and create the view if necessary.
      $createGrouperSourceView = Configure::read('Grouper.useCOmanageSubjectSource');
      if ($createGrouperSourceView) {
         
        // Determine which database is being used.
        $db =& ConnectionManager::getDataSource('default');
        $db_driver = split("/", $db->config['datasource'], 2);

        // The view syntax is different for each database product.
        if ($db_driver[1] == 'Mysql') {
          $this->Identifier->query("CREATE VIEW " . $prefix . "grouper_subjects AS
SELECT
    cm_co_people.id AS 'id',
    CONCAT(GROUP_CONCAT(DISTINCT cm_names.given),' ',GROUP_CONCAT(DISTINCT cm_names.family)) AS 'name',
    CONCAT(GROUP_CONCAT(DISTINCT cm_names.family),',',GROUP_CONCAT(DISTINCT cm_names.given)) AS 'lfname',
    CONCAT(GROUP_CONCAT(DISTINCT cm_names.given),' ',GROUP_CONCAT(DISTINCT cm_names.family),' (',GROUP_CONCAT(DISTINCT cm_cos.description),')') AS 'description',
    SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT cm_identifiers.identifier),',',1) AS 'loginid1',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_identifiers.identifier),','),',',2),',',-1) AS 'loginid2',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_identifiers.identifier),','),',',3),',',-1) AS 'loginid3',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_identifiers.identifier),','),',',4),',',-1) AS 'loginid4',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_identifiers.identifier),','),',',5),',',-1) AS 'loginid5',
    SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT cm_email_addresses.mail),',',1) AS 'email1',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_email_addresses.mail),','),',',2),',',-1) AS 'email2',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_email_addresses.mail),','),',',3),',',-1) AS 'email3',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_email_addresses.mail),','),',',4),',',-1) AS 'email4',
    SUBSTRING_INDEX(SUBSTRING_INDEX(CONCAT(GROUP_CONCAT(DISTINCT cm_email_addresses.mail),','),',',5),',',-1) AS 'email5'
FROM
    cm_co_people
    LEFT JOIN cm_names ON cm_co_people.id = cm_names.co_person_id
    LEFT JOIN cm_identifiers ON cm_co_people.id = cm_identifiers.co_person_id
    LEFT JOIN cm_email_addresses ON cm_co_people.id = cm_email_addresses.co_person_id
    LEFT JOIN cm_cos ON cm_co_people.co_id = cm_cos.id
GROUP BY 
    cm_co_people.id
");

        } else {
          // Only support MySQL for now so throw exception.
          throw new RuntimeException('Grouper support requires MySQL at this time');
        }
      }
      
      // We need the following:
      // - The COmanage CO
      // - An OrgIdentity representing the administrator
      // - The administrator as member of the COmanage CO
      // - A login identifier for the administrator
      // - A group called 'admin' in the COmanage CO
      // - The administrator as a member of that group

      // Start with the COmanage CO
      
      $this->out("- " . _txt('se.db.co'));

      $co = array(
        'Co' => array(
          'name'        => 'COmanage',
          'description' => _txt('co.cm.desc'),
          'status'      => StatusEnum::Active
        )
      );
      
      $this->Co->save($co);
      $co_id = $this->Co->id;

      // Create the OrgIdentity. By default, Org Identities are not pooled, so
      // we attach this org_identity to the new CO.

      $this->out("- " . _txt('se.db.op'));
      
      $op = array(
        'OrgIdentity' => array(
          'affiliation' => 'M',
          'co_id'                  => $co_id
        ),
        'PrimaryName' => array(
          'given'        => $gn,
          'family'       => $sn,
          'type'         => NameEnum::Official,
          'primary_name' => true
        )
      );
      
      $this->OrgIdentity->saveAll($op);
      $op_id = $this->OrgIdentity->id;

      // Add the OrgIdentity's identifier
      
      $id = array(
        'Identifier' => array(
          'identifier'    => $user,
          'type'          => IdentifierEnum::UID,
          'login'         => true,
          'org_identity_id' => $op_id,
          'status'        => StatusEnum::Active
        )
      );
      
      $this->Identifier->save($id);
      $id_id = $this->Identifier->id;

      // Add the OrgIdentity to the CO
      // (1) Create a CO Person

      $this->out("- " . _txt('se.db.cop'));
      
      $cop = array(
        'CoPerson' => array(
          'co_id'         => $co_id,
          'status'        => StatusEnum::Active
        ),
        'PrimaryName' => array(
          'given'        => $gn,
          'family'       => $sn,
          'type'         => NameEnum::Official,
          'primary_name' => true
        )
      );
      
      $this->CoPerson->saveAll($cop);
      $cop_id = $this->CoPerson->id;
      
      // (2) Create a CO Person Role
      
      $copr = array(
        'CoPersonRole' => array(
          'co_person_id'           => $cop_id,
          'title'                  => _txt('fd.admin'),
          'affiliation' => 'SA',
          'status'                 => StatusEnum::Active
        )
      );
      
      $this->CoPersonRole->save($copr);
      $copr_id = $this->CoPersonRole->id;
      
      // (3) Add an Identity Link
      
      $coil = array(
        'CoOrgIdentityLink' => array(
          'co_person_id'    => $cop_id,
          'org_identity_id' => $op_id
        )
      );
      
      $this->CoOrgIdentityLink->save($coil);
      $coil_id = $this->CoOrgIdentityLink->id;
        
      // Create the COmanage admin group
      
      $this->out("- " . _txt('se.db.group'));
      
      $gr = array(
        'CoGroup' => array(
          'co_id'       => $co_id,
          'name'        => 'admin',
          'description' => _txt('co.cm.gradmin'),
          'open'        => false,
          'status'      => StatusEnum::Active
        )
      );

      $this->CoGroup->save($gr);
      $gr_id = $this->CoGroup->id;
      
      // Add the CO Person Role to the admin group
      
      $grm = array(
        'CoGroupMember' => array(
          'co_group_id'   => $gr_id,
          'co_person_id'  => $cop_id,
          'member'        => true,
          'owner'         => true
        )
      );

      $this->CoGroupMember->save($grm);
      $grm_id = $this->CoGroupMember->id;

      // Create the security salt file using a random string
      // if one was not entered.

      $this->out("- " . _txt('se.security.salt'));

      if (!$salt) {
        $salt = str_repeat("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", 10);
        $salt = str_shuffle($salt);
        $salt = substr($salt, 0, 40);
      }

      $securitySaltFilename = APP . "/Config/security.salt";
      file_put_contents($securitySaltFilename, $salt);

      // Create the security seed file using a random string
      // if one was not entered.

      $this->out("- " . _txt('se.security.seed'));

      if (!$seed) {
        $seed = str_repeat("0123456789", 100);
        $seed = str_shuffle($seed);
        $seed = substr($seed, 0, 29);
      }

      $securitySeedFilename = APP . "/Config/security.seed";
      file_put_contents($securitySeedFilename, $seed);

      // Clear the models in the cache since the cm_users view
      // was just created and will not otherwise appear in the cache.
      //
      // See https://bugs.internet2.edu/jira/browse/CO-191
      clearCache(null, 'models');
      
      $this->out(_txt('se.done'));
    }
  }
