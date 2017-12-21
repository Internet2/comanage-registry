<?php
/**
 * COmanage Registry Setup Shell
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
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  App::import('Model', 'ConnectionManager');

  class SetupShell extends AppShell {
    var $uses = array('CmpEnrollmentConfiguration',
                      'Co',
                      'CoGroup',
                      'CoGroupMember',
                      'CoOrgIdentityLink',
                      'CoPerson',
                      'CoPersonRole',
                      'Identifier',
                      'Meta',
                      'OrgIdentity');
    
    public function getOptionParser() {
      $parser = parent::getOptionParser();
      
      $parser->addOption(
        'force',
        array(
          'short' => 'f',
          'help' => _txt('se.arg.force'),
          'boolean' => true,
        )
      )->addOption(
        'admin-given-name',
        array(
          'help' => _txt('se.arg.admin.given')
        )
      )->addOption(
        'admin-family-name',
        array(
          'help' => _txt('se.arg.admin.sn')
        )
      )->addOption(
        'admin-username',
        array(
          'help' => _txt('se.arg.admin.user')
        )
      )->addOption(
        'enable-pooling',
        array(
          'help' => _txt('se.arg.pool')
        )
      )->description(_txt('se.arg.desc'));
      
      return $parser;
    }
    
    function main() {
      // As of v1.0.1, by default we will abort if the security salt file
      // already exists. This will facilitate packaging of COmanage.
      
      $securitySaltFilename = LOCAL . DS . "Config" . DS . "security.salt";
      
      if(file_exists($securitySaltFilename)) {
        $this->out("- " . _txt('se.already'));
        
        if(!isset($this->params['force']) || !$this->params['force']) {
          $this->out("- " . _txt('se.already.override'));
          exit;
        }
      }
      
      // Prepare a new installation. Since there's a decent chance the user will
      // ctrl-c before we get started, prompt for info first (or check to see
      // if it was provided).
      
      $gn = "";
      $sn = "";
      $user = "";
      $pooling = false;
      
      if(!empty($this->params['admin-given-name'])) {
        $gn = $this->params['admin-given-name'];
      } else {
        $gn = $this->in(_txt('se.cf.admin.given'));        
      }
      
      if(!empty($this->params['admin-family-name'])) {
        $sn = $this->params['admin-family-name'];
      } else {
        $sn = $this->in(_txt('se.cf.admin.sn'));        
      }
      
      if(!empty($this->params['admin-username'])) {
        $user = $this->params['admin-username'];
      } else {
        $user = $this->in(_txt('se.cf.admin.user'));        
      }
      
      /* As of v3.1.0, pooling can no longer be enabled for new deployments (CO-1471)
      if(!empty($this->params['enable-pooling'])) {
        $pooling = $this->params['enable-pooling'];
      } else {
        $pooling = $this->in(_txt('se.cf.pool'),
                             array(_txt('fd.yes'), _txt('fd.no')),
                             _txt('fd.no'));
      }
      */
      
      // Since we'll be doing some direct DB manipulation, find the table prefix
      $prefix = "";
      $db = ConnectionManager::getDataSource('default');

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
      
      // We need the following:
      // - The COmanage CO
      // - An OrgIdentity representing the administrator
      // - The administrator as member of the COmanage CO
      // - A login identifier for the administrator
      // - A group called 'admin' in the COmanage CO
      // - A group called 'members' in the COmanage CO
      // - The administrator as a member of the admin and members groups
      // - Set up platform defaults
      // - Register the current version for future upgrade purposes
      
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
          'affiliation'  => AffiliationEnum::Member,
          'co_id'        => $co_id
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
          'co_person_id'   => $cop_id,
          'title'          => _txt('fd.admin'),
          'affiliation'    => AffiliationEnum::Staff,
          'status'         => StatusEnum::Active
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
      
      // Add the CO Person to the admin group
      
      $grAdminId = $this->CoGroup->adminCoGroupId($co_id);

      $grm = array(
        'CoGroupMember' => array(
          'co_group_id'   => $grAdminId,
          'co_person_id'  => $cop_id,
          'member'        => true,
          'owner'         => true
        )
      );

      $this->CoGroupMember->save($grm);
      
      // Create platform defaults
      
      $this->out("- " . _txt('se.cmp.init'));
      $this->CmpEnrollmentConfiguration->createDefault($pooling == _txt('fd.yes'));
      
      // Register the current version for future upgrade purposes
      // Read the current release from the VERSION file
      $versionFile = APP . DS . 'Config' . DS . "VERSION";
      
      $targetVersion = rtrim(file_get_contents($versionFile));
      
      $this->Meta->setUpgradeVersion($targetVersion, true);
      
      // Generate security salt and seed files if they don't already exist
      
      if(file_exists($securitySaltFilename)) {
        $this->out("- " . _txt('se.security.salt.exists'));
      } else {
        // Create the security salt file using a random string
        $this->out("- " . _txt('se.security.salt'));
        
        $salt = str_repeat("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", 10);
        $salt = str_shuffle($salt);
        $salt = substr($salt, 0, 40);
        
        file_put_contents($securitySaltFilename, $salt);
      }
      
      $securitySeedFilename = LOCAL . DS . "Config" . DS . "security.seed";
      
      if(file_exists($securitySeedFilename)) {
        $this->out("- " . _txt('se.security.seed.exists'));
      } else {
        // Create the security seed file using a random string
        $this->out("- " . _txt('se.security.seed'));
        
        $seed = str_repeat("0123456789", 100);
        $seed = str_shuffle($seed);
        $seed = substr($seed, 0, 29);
        
        file_put_contents($securitySeedFilename, $seed);
      }

      // Clear the models in the cache since the cm_users view
      // was just created and will not otherwise appear in the cache.
      //
      // See https://bugs.internet2.edu/jira/browse/CO-191
      clearCache(null, 'models');
      
      $this->out(_txt('se.done'));
    }
  }
