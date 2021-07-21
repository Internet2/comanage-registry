<?php
/**
 * COmanage Registry Database Shell
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

  App::import('Controller', 'AppController');
  App::import('Model', 'ConnectionManager');

  // App::import doesn't handle this correctly
  require(APP . '/Vendor/adodb5/adodb.inc.php');
  require_once(APP . '/Vendor/adodb5/adodb-xmlschema03.inc.php');
  
  // On some installs, AppController isn't loaded by App::import
  require(APP . '/Controller/AppController.php');

  class DatabaseShell extends AppShell {
    function main()
    {
      // Database schema management. We use adodb rather than Cake's native schema
      // management because the latter is lacking (foreign keys not migrated, hard
      // to do upgrades).
      
      // Use the ConnectionManager to get the database config to pass to adodb.
      $db = ConnectionManager::getDataSource('default');
      
      $db_driver = explode("/", $db->config['datasource'], 2);
      
      if($db_driver[0] != 'Database') {
        throw new RuntimeException("Unsupported db_method: " . $db_driver[0]);
      }

      $db_driverName = $db_driver[1];
      if(preg_match("/mysql/i", $db_driverName) && PHP_MAJOR_VERSION >= 7) {
        $db_driverName = 'mysqli';
      }

      $dbc = ADONewConnection($db_driverName);
      
      if($dbc->Connect($db->config['host'],
                       $db->config['login'],
                       $db->config['password'],
                       $db->config['database'])) {
        // Plugins can have their own schema files, so we need to account for that
        
        $schemaSources = array_merge(array("."), App::objects('plugin'));
        
        foreach($schemaSources as $schemaSource) {
          $schemaFile = APP . '/Config/Schema/schema.xml';
          
          if($schemaSource != ".") {
            // This is a plugin, look for a schema file
            $found = false;
            
            // Plugins can be under either APP or LOCAL
            foreach(array(APP, LOCAL) as $dir) {
              // Check to see if the file exists/is readable
              $schemaFile = $dir . '/Plugin/' . $schemaSource . '/Config/Schema/schema.xml';
              
              if(is_readable($schemaFile)) {
                $found = true;
                break;
              }
            }
            
            // No schema file fonud
            // See if the file exists. If it doesn't, there's no schema to load.
            if(!$found) {
              print "No schema found for " . $schemaSource . "\n";
              continue;
            }
          }
          
          $this->out(_txt('op.db.schema', array($schemaFile)));
          
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
          if ($db_driver[1] != 'Mysql') {
            $sql = $schema->ParseSchema($schemaFile);
          }
          else {
            $xml = new DOMDocument;
            $xml->load($schemaFile);
  
            $xsl = new DOMDocument;
            $xsl->load(APP . '/Config/Schema/boolean_mysql.xsl');
  
            $proc = new XSLTProcessor;
            $proc->importStyleSheet($xsl);
  
            $sql = $schema->ParseSchemaString($proc->transformToXML($xml));
          }
          
          switch($schema->ExecuteSchema($sql)) {
          case 2: // !!!
            $this->out(_txt('op.db.ok'));
            break;
          default:
            $this->out(_txt('er.db.schema'));
            break;
          }
        }
        
        $dbc->Disconnect();
      }
      else {
        $this->out(_txt('er.db.connect', array($dbc->ErrorMsg())));
        exit;
      }
    }
  }
