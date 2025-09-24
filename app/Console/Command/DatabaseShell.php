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
    const DB_DRIVER_MYSQL = 'mysqli';

    /**
     * @var object Database connection object
     */
    protected $db;

    /**
     * @var string Database driver name
     */
    protected $db_driverName;

    /**
     * @var array Database driver details
     */
    protected $db_driver;

    /**
     * @var object ADODB connection instance
     */
    protected $dbc;

    /**
     * Initialize function to perform setup tasks for the shell.
     *
     * This function is automatically called before running any commands.
     * Use it to load configurations, set properties, or perform any
     * other setup tasks.
     * @since         COmanage Registry v4.5.0
     */
    public function initialize()
    {
      parent::initialize();
      // Database schema management. We use adodb rather than Cake's native schema
      // management because the latter is lacking (foreign keys not migrated, hard
      // to do upgrades).

      // Use the ConnectionManager to get the database config to pass to adodb.
      $this->db = ConnectionManager::getDataSource('default');

      $this->db_driver = explode("/", $this->db->config['datasource'], 2);

      if($this->db_driver[0] != 'Database') {
        throw new RuntimeException("Unsupported db_method: " . $this->db_driver[0]);
      }

      $this->db_driverName = $this->db_driver[1];
      if(preg_match("/mysql/i", $this->db_driverName) && PHP_MAJOR_VERSION >= 7) {
        $this->db_driverName = self::DB_DRIVER_MYSQL;
      }

      $this->dbc = ADONewConnection($this->db_driverName);
      $envDatabaseVerbose = getenv('COMANAGE_REGISTRY_DATABASE_VERBOSE');
      if (Configure::read('debug') >= 2
          || filter_var($envDatabaseVerbose, FILTER_VALIDATE_BOOLEAN)) {
        $this->dbc->debug = true;
      }
      if(isset($this->db->config['port'])) {
        $this->dbc->port = (int)$this->db->config['port'];
      }

      if (
        !$this->dbc->Connect(
          $this->db->config['host'],
          $this->db->config['login'],
          $this->db->config['password'],
          $this->db->config['database'])
      ) {
        $this->out(_txt('er.db.connect', array($this->dbc->ErrorMsg())));
        exit;
      }
    }


    /**
     * Main entry point of the DatabaseShell script.
     *
     * This function retrieves schema files for the application and its plugins,
     * and updates the database schema using the retrieved files.
     * If no schema file exists for a plugin, an error message is displayed.
     *
     * The method handles various database drivers and includes functionality
     * for MySQL-specific schema transformations using XSLT.
     *
     * @since        COmanage Registry v4.5.0
     * @package registry
     */
    function main()
    {
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

        $schema = new adoSchema($this->dbc);
        $schema->setPrefix($this->db->config['prefix']);
        // ParseSchema is generating bad SQL for Postgres. eg:
        //  ALTER TABLE cm_cos ALTER COLUMN id SERIAL
        // which (1) should be ALTER TABLE cm_cos ALTER COLUMN id TYPE SERIAL
        // and (2) SERIAL isn't usable in an ALTER TABLE statement
        // So we continue on error
//        $schema->ContinueOnError(true);

        // Parse the database XML schema from file unless we are targeting MySQL
        // in which case we use an XSL style sheet to first modify the schema
        // so that boolean columns are cast to TINYINT(1) and the cakePHP
        // automagic works. See
        //
        // https://bugs.internet2.edu/jira/browse/CO-175
        //
        $xslFile = $this->db_driverName == self::DB_DRIVER_MYSQL ?
          APP . '/Config/Schema/transform_mysql.xsl' :
          APP . '/Config/Schema/transform_postgresql.xsl';

        $schemaString = $this->transformSchemaFile($schemaFile, $xslFile);
        $sqlQueries = $schema->parseSchemaString($schemaString);

        if ($this->db_driverName == self::DB_DRIVER_MYSQL) {
          $sqlQueries = $this->transformSqlQueryList($sqlQueries);
        } else {
          $sqlQueries = $this->transformPGSqlQueryList($sqlQueries);
        }

        switch($schema->ExecuteSchema($sqlQueries)) {
          case 2: // !!!
            $this->out(_txt('op.db.ok'));
            break;
          default:
            $this->err('<error>' . _txt('er.db.schema') . '</error>');
            $this->err('<error>' . $schema->db->ErrorMsg() . '</error>');
            break;
        }
      }

      $this->dbc->Disconnect();
    }

    /**
     * Load and transform a xml file according to xsl configuration
     *
     * @param string $schemaFile
     * @param string $xslFile
     * @return false|string|null
     * @since         COmanage Registry v4.5.0
     * @package registry
     */
    function transformSchemaFile($schemaFile, $xslFile) {
      $xml = new DOMDocument;
      $xml->load($schemaFile);

      $xsl = new DOMDocument;
      $xsl->load($xslFile);

      $proc = new XSLTProcessor;
      $proc->importStyleSheet($xsl);
      return $proc->transformToXml($xml);
    }

    /**
     * Iterate over the SqlQueryList and apply corrections
     * For MySQL
     *
     * @param array $sqlQueryList transformSqlQueryList List of SQL query statements.
     *
     * @return array Modified SQL statements generated from the XML schema file.
     * @since         COmanage Registry v4.5.0
     * @package registry
     */
    public function transformSqlQueryList($sqlQueryList) {
      $constraintsNameList = $this->getConstraints($this->dbc);
      $indexNameList = $this->getIndexes($this->dbc);
      $indexesUsedByConstraints = $this->getIndexesUsedByConstraints($this->dbc);

      // Use ADD COLUMN if not exists
      $reAddConstraint = '/ALTER TABLE (.*?) ADD CONSTRAINT (.*?) (.*)/m';
      $reAddIndex = '/ALTER TABLE (.*?) ADD(?:\s?\bUNIQUE\b\s?)? INDEX (.*?) (.*)/m';
      // Since this is only form mysql we assume that the after the key COLUMN we only find the column name
      $reDropColumn = '/ALTER TABLE (.*?) DROP COLUMN (.*)/m';

      $dropConstraints = array();
      foreach ($sqlQueryList as $idx => $sqlQuery) {
        // Remove unnecessary multiple spaces.
        $sqlQuery = preg_replace('/\s+/', ' ', $sqlQuery);
        // Add column
        if ($this->columnExistsFromAlterQuery($this->dbc, $sqlQuery)) {
          unset($sqlQueryList[$idx]);
          continue;
        }

        // Shorten too long Constraints
        $matches = array();
        preg_match($reAddConstraint, $sqlQuery, $matches);
        if (!empty($matches)) {
          $constraintName = $this->shortenFieldName($matches[2]);
          $subst = "ALTER TABLE $matches[1] ADD CONSTRAINT $constraintName {$matches[3]}";
          $sqlQueryList[$idx] = $subst;
          $sqlQuery = $subst;
        }

        // Shorten too long Indexes
        $indexAddMatches = array();
        preg_match($reAddIndex, $sqlQuery, $indexAddMatches);
        if (!empty($indexAddMatches)) {
          $indexName = $this->shortenFieldName($indexAddMatches[2]);
          $subst = "ALTER TABLE $indexAddMatches[1] ADD INDEX $indexName {$indexAddMatches[3]}";
          if (strpos($sqlQuery, 'ADD UNIQUE INDEX') !== false) {
            $subst = "ALTER TABLE $indexAddMatches[1] ADD UNIQUE INDEX $indexName {$indexAddMatches[3]}";
          }
          $sqlQueryList[$idx] = $subst;
          $sqlQuery = $subst;
        }

        // Add Constraints
        $matches = array();
        preg_match($reAddConstraint, $sqlQuery, $matches);
        if (!empty($matches) && in_array($matches[2], $constraintsNameList)) {
          unset($sqlQueryList[$idx]);
          continue;
        }

        // Do not allow to Drop Indexes associated to Constraints
        $indexQueryMatch = array();
        $reIndexDrop = '/DROP INDEX (.*?) ON (.*)/m';
        preg_match($reIndexDrop, $sqlQuery, $indexQueryMatch);
        if (!empty($indexQueryMatch) && isset($indexesUsedByConstraints[$indexQueryMatch[1]])) {
          unset($sqlQueryList[$idx]);
          continue;
        }

        // Add Index
        $indexAddMatches = array();
        preg_match($reAddIndex, $sqlQuery, $indexAddMatches);
        if (!empty($indexAddMatches) && isset($indexNameList[$indexAddMatches[2]])) {
          unset($sqlQueryList[$idx]);
        }

        // Rename column name when dropping
        $columnDropMatches = array();
        preg_match($reDropColumn, $sqlQuery, $columnDropMatches);
        if (!empty($columnDropMatches)) {
          // Before we drop we need to check for a foreign. If we find one we need to drop it first
          $constraintName = $this->getConstraintNameFromColumn($columnDropMatches[1], $columnDropMatches[2], $this->dbc);
          if (!empty($constraintName)) {
            $subst = "ALTER TABLE $columnDropMatches[1] DROP CONSTRAINT $constraintName";
            $dropConstraints[] = $subst;
          }
          $subst = "ALTER TABLE $columnDropMatches[1] DROP COLUMN `{$columnDropMatches[2]}`";
          $sqlQueryList[$idx] = $subst;
        }
      }

      $sqlQueryList = array_merge($dropConstraints, $sqlQueryList);
      return $sqlQueryList;
    }

    /**
     * Iterate over the SqlQueryList and apply corrections
     * For PostgreSQL
     *
     * @param array $sqlQueryList transformSqlQueryList List of SQL query statements.
     *
     * @return array Modified SQL statements generated from the XML schema file.
     * @since       COmanage Registry v4.6.0
     * @package registry
     */
    public function transformPGSqlQueryList($sqlQueryList) {
      // regex pattern to match the DROP INDEX statement and extract the index name
      $reIndexDrop = "/^\s*DROP\s+INDEX\s+([A-Za-z0-9_]+)\s*;?\s*$/i";

      foreach ($sqlQueryList as $idx => $sqlQuery) {
        // Do not allow to Drop Indexes associated to Constraints
        $indexQueryMatch = array();
        preg_match($reIndexDrop, $sqlQuery, $indexQueryMatch);
        if (
          !empty($indexQueryMatch)
          && $this->getPGConstraintNameFromIndex($indexQueryMatch[1], $this->dbc)
        ) {
          unset($sqlQueryList[$idx]);
        }
      }



      return $sqlQueryList;
    }

    /**
     * Retrieves the list of constraints from the database schema.
     *
     * This method queries the `information_schema` database to extract
     * constraints depending on the database type (MySQL or PostgreSQL).
     *
     * @param ADOConnection $dbc The ADOdb database connection instance
     *
     * @return array The list of constraint names in the database schema
     * @package       registry
     * @since         COmanage Registry v4.5.0
     */
    public function getConstraints($dbc) {
      $mysqlQuery = <<<MYSQL
SELECT kcu.TABLE_SCHEMA,
       kcu.TABLE_NAME,
       kcu.COLUMN_NAME,
       kcu.CONSTRAINT_NAME,
       kcu.REFERENCED_TABLE_NAME,
       kcu.REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE kcu
WHERE kcu.TABLE_SCHEMA = DATABASE()
  AND kcu.REFERENCED_COLUMN_NAME IS NOT NULL
ORDER BY kcu.TABLE_NAME;
MYSQL;

      $result = $dbc->Execute($mysqlQuery);
      $data = ($result && !$result->EOF) ? $result->GetArray() : [];
      $constraintNames = Hash::extract($data, '{n}.CONSTRAINT_NAME');

      return $constraintNames;
    }

    /**
     * Checks whether a column exists in a given table, parsed from an ALTER TABLE ADD COLUMN statement.
     *
     * @param ADOConnection $db The ADOdb database connection instance
     * @param string        $query The ALTER TABLE SQL query string to parse
     *
     * @return bool True if the column exists, false otherwise
     * @throws InvalidArgumentException if the query is malformed
     * @since         COmanage Registry v4.5.0
     * @package       registry
     */
    function columnExistsFromAlterQuery($db, $query) {
      // Regex to parse the ALTER TABLE statement to extract table and column names
      $pattern = '/ALTER TABLE (.*?) ADD `(.*?)` (.*)/i';

      // Perform regex match
      if (!preg_match($pattern, $query, $matches)) {
        return false;
      }

      // Extracted table and column name
      $table = $matches[1];
      $column = $matches[2];

      // Execute query
      // Use ADOdb MetaColumns to check column existence
      $columns = $db->MetaColumns($table);

      if ($columns === false) {
        throw new RuntimeException("Unable to retrieve column information for table '{$table}'");
      }

      return isset($columns[strtoupper($column)]);
    }

    /**
     * Retrieves a list of indexes used by constraints in the current database schema.
     *
     * This method queries the database to extract information about indexes that are
     * tied to table constraints, such as primary keys, foreign keys, and unique constraints.
     *
     * @param ADOConnection $db The ADOdb database connection instance
     *
     * @return array An associative array where the keys are index names and the values are the corresponding constraint names
     * @package       registry
     * @since         COmanage Registry v4.5.0
     */
    function getIndexesUsedByConstraints($db)
    {
      $mySql = "
        SELECT DISTINCT
            tc.TABLE_NAME,
            kcu.COLUMN_NAME,
            tc.CONSTRAINT_NAME,
            tc.CONSTRAINT_TYPE,
            s.INDEX_NAME
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
        JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ON
            tc.TABLE_SCHEMA = kcu.TABLE_SCHEMA AND
            tc.TABLE_NAME = kcu.TABLE_NAME AND
            tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
        JOIN INFORMATION_SCHEMA.STATISTICS s ON
            s.TABLE_SCHEMA = tc.TABLE_SCHEMA AND
            s.TABLE_NAME = tc.TABLE_NAME AND
            s.COLUMN_NAME = kcu.COLUMN_NAME
        WHERE tc.TABLE_SCHEMA = DATABASE()
        ORDER BY tc.TABLE_NAME, tc.CONSTRAINT_NAME;
    ";
      $result = $db->Execute($mySql);
      $data = ($result && !$result->EOF) ? $result->GetArray() : [];
      return Hash::combine($data, '{n}.INDEX_NAME', '{n}.CONSTRAINT_NAME');
    }

    /**
     * Retrieves all indexes available in the database schema.
     *
     * This method provides index details for the given database, supporting
     * MySQL and PostgreSQL. It queries the database metadata to identify and
     * return index information.
     *
     * @param ADOConnection $dbc The ADOdb database connection instance
     *
     * @return array An array where index names are mapped to their corresponding column names.
     * @throws RuntimeException if the database driver is unsupported or fails to execute the query.
     * @since COmanage Registry v4.5.0
     * @package registry
     */
    public function getIndexes($dbc) {
      $mysqlQuery = <<<MYSQL
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME, NON_UNIQUE, SEQ_IN_INDEX
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
MYSQL;

      $indexes = $dbc->GetAll($mysqlQuery);
      return Hash::combine($indexes, '{n}.INDEX_NAME', '{n}.COLUMN_NAME');
    }

    /**
     * Shorten the field Name
     *
     * @param string $fieldName The original constraint name to enforce the length on.
     * @param int $maxLength The maximum allowed length for the constraint name (default is 60).
     *
     * @return string The adjusted field name shortened
     * @package registry
     * @since COmanage Registry v4.5.0
     */
    function shortenFieldName($fieldName, $maxLength = 64) {
      if (strlen($fieldName) <= $maxLength) {
        return $fieldName;
      }
      $listOfWords = explode('_', $fieldName);
      foreach ($listOfWords as $idx => $word) {
        if (strlen($word) > 3) {
          $length = strlen($word);
          $partialPostfix = substr($word, 3, $length);
          $partialPostfix = str_ireplace(array('a','e','i','o','u',' '), '', $partialPostfix);
          $listOfWords[$idx] = substr($word, 0, 3) . $partialPostfix;
        }
      }
      $newFieldName = implode('_', $listOfWords);

      if (strlen($newFieldName) > $maxLength) {
        // Shorten more aggressively here.
        // Not required at the moment
      }

      // Shorten, remove all vowels
      return $newFieldName;
    }


    /**
     * Retrieves the constraint name for a given table and column from MySQL database.
     *
     * @param string $table The name of the table to check
     * @param string $column The name of the column to check
     * @param ADOConnection $db The ADOdb database connection instance
     * @return string|null The constraint name if found, null otherwise
     * @since COmanage Registry v4.5.1
     * @package registry
     */
    function getConstraintNameFromColumn($table, $column, $db) {
      // Find the constraint name
      $sql = "
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = ?
      AND COLUMN_NAME = ?
      AND REFERENCED_TABLE_NAME IS NOT NULL
";
      $row = $db->GetRow($sql, array($table, $column));
      if (!$row) {
        return;
      }
      return $row['CONSTRAINT_NAME'];
    }

    /**
     * Resolve the owning constraint name for a given index (or constraint) name on a table (PostgreSQL).
     *
     * @param string         $index Index or constraint name to look up (eg, 'cm_cos_name_key')
     * @param ADOConnection  $db ADOdb connection (connected to the target database)
     * @param string         $schema Schema name (default 'public')
     * @return bool          true if the index exists, false otherwise or if the query fails
     * @throws RuntimeException if the query fails
     * @package registry
     * @since COmanage Registry v4.6.0
     */
    public function getPGConstraintNameFromIndex($index, $db, $schema = 'public') {
       $sql = <<<SQL
    SELECT c.conname
    FROM pg_constraint c
    JOIN pg_class t ON t.oid = c.conrelid
    JOIN pg_namespace n ON n.oid = t.relnamespace
    LEFT JOIN pg_class i ON i.oid = c.conindid
    WHERE n.nspname = ?
      AND (c.conname = ? OR i.relname = ?)
    LIMIT 1
    SQL;


      $rs = $db->Execute($sql, array($schema, $index, $index));
      if ($rs === false) {
        throw new RuntimeException("Error executing query: " . $db->ErrorMsg() );
      }

      $exists = !$rs->EOF;  // true if there is at least one row, without fetching any field
      $rs->Close();
      return $exists;
    }
  }
