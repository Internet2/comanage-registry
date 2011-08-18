<?php
  /*
   * COmanage Gears Database Shell
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

  // App::import doesn't handle this correctly
  require($this->params['root'] . '/app/vendors/adodb/adodb.inc.php');
  require($this->params['root'] . '/app/vendors/adodb/adodb-xmlschema03.inc.php');

  class DatabaseShell extends Shell {
    function main()
    {
      // Database schema management. We use adodb rather than Cake's native schema
      // management because the latter is lacking (foreign keys not migrated, hard
      // to do upgrades).
      
      // Use the ConnectionManager to get the database config to pass to adodb.
      $db =& ConnectionManager::getDataSource('default');
        
      $dbc = ADONewConnection($db->config['driver']);
      
      if($dbc->Connect($db->config['host'],
                       $db->config['login'],
                       $db->config['password'],
                       $db->config['database']))
      {
        $schema = new adoSchema($dbc);
        $schema->setPrefix($db->config['prefix']);
        // ParseSchema is generating bad SQL for Postgres. eg:
        //  ALTER TABLE cm_cos ALTER COLUMN id SERIAL
        // which (1) should be ALTER TABLE cm_cos ALTER COLUMN id TYPE SERIAL
        // and (2) SERIAL isn't usable in an ALTER TABLE statement
        // So we continue on error
        $schema->ContinueOnError(true);
        $sql = $schema->ParseSchema($this->params['root'] . '/app/config/schema/schema.xml');
        
        switch($schema->ExecuteSchema($sql))
        {
        case 2: // !!!
          $this->out(_txt('op.db.ok'));
          break;
        default:
          $this->out(_txt('er.db.schema'));
          break;
        }
        
        $dbc->Disconnect();
      }
      else
      {
        $this->out(_txt('er.db.connect', array($dbc->ErrorMsg())));
        exit;
      }
    }
  }
?>