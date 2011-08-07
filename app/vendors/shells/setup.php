<?php
  /*
   * COmanage Gears Setup Shell
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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
   */

  App::import('Core', 'ConnectionManager');

  class SetupShell extends Shell {
    var $uses = array('Co', 'CoGroup', 'CoGroupMember', 'CoOrgIdentityLink', 'CoPerson', 'CoPersonRole', 'Identifier', 'OrgIdentity');
    
    function main()
    {
      // Prepare a new installation. Since there's a decent chance the user will
      // ctrl-c before we get started, prompt for info first.
      
      $gn = $this->in(_txt('se.cf.admin.given'));
      $sn = $this->in(_txt('se.cf.admin.sn'));
      $user = $this->in(_txt('se.cf.admin.user'));

      // Since we'll be doing some direct DB manipulation, find the table prefix
      $prefix = "";
      $db =& ConnectionManager::getDataSource('default');

      if(isset($db->config['prefix']))
        $prefix = $db->config['prefix'];

      $this->out("- " . _txt('se.users.drop'));
      $this->Identifier->query("DROP TABLE " . $prefix . "users");
      
      $this->out("- " . _txt('se.users.view'));
      $this->Identifier->query("CREATE VIEW " . $prefix . "users AS
SELECT a.username as username, a.password as password, a.id as api_user_id, null as org_identity_id
FROM cm_api_users a
UNION SELECT i.identifier as username, '*' as password, null as api_user_id, i.org_identity_id as org_identity_id
FROM cm_identifiers i
WHERE i.login=true;
");
      
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
          'status'      => 'A'
        )
      );
      
      $this->Co->save($co);
      $co_id = $this->Co->id;

      // Create the OrgIdentity

      $this->out("- " . _txt('se.db.op'));
      
      $op = array(
        'OrgIdentity' => array(
          'edu_person_affiliation' => 'member'
        ),
        'Name' => array(
          'given'   => $gn,
          'family'  => $sn,
          'type'    => 'O'
        )
      );
      
      if(!$this->OrgIdentity->saveAll($op))
        print_r($this->OrgIdentity->invalidFields());
      else
        $op_id = $this->OrgIdentity->id;

      // Add the OrgIdentity's identifier
      
      $id = array(
        'Identifier' => array(
          'identifier'    => $user,
          'type'          => 'uid',
          'login'         => true,
          'org_identity_id' => $op_id
        )
      );
      
      if(!$this->Identifier->save($id))
        print_r($this->Identifier->invalidFields());
      else
        $id_id = $this->Identifier->id;

      // Add the OrgIdentity to the CO
      // (1) Create a CO Person

      $this->out("- " . _txt('se.db.cop'));
      
      $cop = array(
        'CoPerson' => array(
          'co_id'         => $co_id,
          'status'        => 'A'
        ),
        'Name' => array(
          'given'   => $gn,
          'family'  => $sn,
          'type'    => 'P'
        )
      );
      
      $this->CoPerson->saveAll($cop);
      $cop_id = $this->CoPerson->id;
      
      // (2) Create a CO Person Role
      
      $copr = array(
        'CoPersonRole' => array(
          'co_person_id'           => $cop_id,
          'edu_person_affiliation' => 'staff',
          'status'                 => 'A'
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
          'status'      => 'A'
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

      $this->out(_txt('se.done'));
    }
  }
?>