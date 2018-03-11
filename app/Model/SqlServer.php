<?php
/**
 * COmanage Registry SQL Server Model
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::import('Model', 'ConnectionManager');

class SqlServer extends AppModel {
  // Define class name for cake
  public $name = "SqlServer";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("Server");
  
  // Default display field for cake generated views
  public $displayField = "hostname";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'server_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'type' => array(
      'rule' => array(
        'inList',
        array(
          SqlServerEnum::Mysql,
          SqlServerEnum::Postgres,
          SqlServerEnum::SqlServer 
        )
      ),
      'required' => true
    ),
    'hostname' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'username' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'password' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'database' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Establish a connection (via Cake's ConnectionManager) to the specified SQL server.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Integer $serverId Server ID (NOT SqlServerId)
   * @param  String  $name     Connection name, used for subsequent access via Models
   * @return Boolean true on success
   * @throws Exception
   */
  
  public function connect($serverId, $name) {
    // Get our connection information
    
    $args = array();
    $args['conditions']['SqlServer.server_id'] = $serverId;
    $args['contain'] = false;
    
    $sqlserver = $this->find('first', $args);
    
    if(empty($sqlserver)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.sql_servers.1'), $serverId)));
    }
    
    $dbmap = array(
      SqlServerEnum::Mysql     => 'Mysql',
      SqlServerEnum::Postgres  => 'Postgres',
      SqlServerEnum::SqlServer => 'Sqlserver'
    );
    
    $dbconfig = array(
      'datasource' => 'Database/' . $dbmap[ $sqlserver['SqlServer']['type'] ],
      'persistent' => false,
      'host' => $sqlserver['SqlServer']['hostname'],
      'login' => $sqlserver['SqlServer']['username'],
      'password' => $sqlserver['SqlServer']['password'],
      'database' => $sqlserver['SqlServer']['database'],
//    'prefix' => '',
//    'encoding' => 'utf8',
    );
    
    $datasource = ConnectionManager::create($name, $dbconfig);
    
    return true;
  }
}
