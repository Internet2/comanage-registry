<?php
/**
 * COmanage Registry Database Shell
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  App::import('Model', 'ConnectionManager');

  // App::import doesn't handle this correctly
  require(APP . '/Vendor/adodb/adodb.inc.php');
  require(APP . '/Vendor/adodb/adodb-xmlschema03.inc.php');

  class DatabaseShell extends AppShell {
    function main()
    {
      // Database schema management. We use adodb rather than Cake's native schema
      // management because the latter is lacking (foreign keys not migrated, hard
      // to do upgrades).
      
      // Use the ConnectionManager to get the database config to pass to adodb.
      $db =& ConnectionManager::getDataSource('default');
      
      $db_driver = split("/", $db->config['datasource'], 2);
      
      if($db_driver[0] != 'Database') {
        throw new RuntimeException("Unsupported db_method: " . $db_driver[0]);
      }
      $dbc = ADONewConnection($db_driver[1]);
      
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

        // Parse the database XML schema from file unless we are targeting MySQL
        // in which case we use an XSL style sheet to first modify the schema
        // so that boolean columns are cast to TINYINT(1) and the cakePHP
        // automagic works. See
        //
        // https://bugs.internet2.edu/jira/browse/CO-175
        //
        if ($db_driver[1] != 'Mysql')
        {
          $sql = $schema->ParseSchema(APP . '/Config/Schema/schema.xml');
        }
        else
        {
          $xml = new DOMDocument;
          $xml->load($this->params['root'] . '/Config/Schema/schema.xml');

          $xsl = new DOMDocument;
          $xsl->load($this->params['root'] . '/Config/Schema/boolean_mysql.xsl');

          $proc = new XSLTProcessor;
          $proc->importStyleSheet($xsl);

          $sql = $schema->ParseSchemaString($proc->transformToXML($xml));
        }
        
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
