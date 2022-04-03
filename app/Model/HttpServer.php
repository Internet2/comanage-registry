<?php
/**
 * COmanage Registry HTTP Server Model
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

App::uses('CoHttpClient', 'Lib');

class HttpServer extends AppModel {
  // Define class name for cake
  public $name = "HttpServer";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("Server");
  
  // Default display field for cake generated views
  public $displayField = "serverurl";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'server_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'serverurl' => array(
      // The Cake URL rule accepts FTP, gopher, news, and file... not clear that
      // we'd want all of those
      'rule' => array('custom', '/^https?:\/\/.*/'),
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
    'authtype' => array(
      'rule' => array('inList', array(HttpServerAuthType::Basic,
                                      HttpServerAuthType::Bearer,
                                      HttpServerAuthType::None)),
      'required' => true,
      'allowEmpty' => false
    ),
    'ssl_verify_peer' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
    'ssl_verify_host' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v3.2.0
   * @return Boolean
   */
  /*
   * We don't implement this here because we don't know that serverurl is valid.
   * ie: The configured URL might be https://server.org/myapp, but that might
   * always return unauthorized because the actual URL being assembled is
   * something like https://server.org/myapp/api/v1/resources.json and we have
   * no way of knowing what the latter component is. So it's up to the code
   * using the HttpServer object to whatever validation it wants to do.
   * 
  public function beforeSave($options = array()) {
  }
   */

  /**
   * Perform HttpServer model upgrade steps for version 4.0.0.
   * Resizes the password column.
   * This function should only be called by UpgradeVersionShell.
   *
   * @since  COmanage Registry v4.0.0
   */
  
  public function _ug400() {
    $dbc = $this->getDataSource();

    $db_driver = explode("/", $dbc->config['datasource'], 2);

    if ($db_driver[0] !== 'Database') {
      throw new RuntimeException("Unsupported db_method: " . $db_driver[0]);
    }

    $db_driverName = $db_driver[1];

    // Nothing to do if not Postgres
    if ($db_driverName !== 'Postgres') {
      return;
    }

    $dbprefix = $dbc->config['prefix'];

    $sql = "ALTER TABLE " . $dbprefix . "http_servers ALTER COLUMN password TYPE varchar(400)";

    $this->query($sql);
  }
}