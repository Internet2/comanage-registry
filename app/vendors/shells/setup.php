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
    var $uses = array('Identifier');
    
    function main()
    {
      // Prepare a new installation
      
      // Since we'll be doing some direct DB manipulation, find the table prefix
      $prefix = "";
      $db =& ConnectionManager::getDataSource('default');

      if(isset($db->config['prefix']))
        $prefix = $db->config['prefix'];

      $this->out("-> " . _txt('se.users.drop'));
      $this->Identifier->query("DROP TABLE " . $prefix . "users");
      
      $this->out("-> " . _txt('se.users.view'));
      $this->Identifier->query("CREATE VIEW " . $prefix . "users AS
SELECT a.username as username, a.password as password, a.id as api_user_id, null as org_person_id
FROM cm_api_users a
UNION SELECT i.identifier as username, '*' as password, null as api_user_id, i.org_person_id as org_person_id
FROM cm_identifiers i
WHERE i.login=true;
");

      $this->out(_txt('se.done'));
    }
  }
?>