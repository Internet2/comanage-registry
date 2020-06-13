<?php
/**
 * COmanage Registry Server Model
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

class Server extends AppModel {
  // Define class name for cake
  public $name = "Server";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");
  
  public $hasOne = array(
    "HttpServer" => array('dependent' => true),
    "LdapServer" => array('dependent' => true),
    "MatchServer" => array('dependent' => true),
    "Oauth2Server" => array('dependent' => true),
    "SqlServer" => array('dependent' => true)
  );
  
  public $hasMany = array(
    "CoPipeline" => array(
      'foreignKey' => 'match_server_id'
    )
  );
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  public $actsAs = array('Containable');
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false,
      'message' => 'A CO ID must be provided'
    ),
    'description' => array(
      'rule' => array('validateInput'),
      'required' => true,
      'allowEmpty' => false
    ),
    'server_type' => array(
      'rule' => array('inList', array(ServerEnum::HttpServer,
                                      ServerEnum::LdapServer,
                                      ServerEnum::MatchServer,
                                      ServerEnum::Oauth2Server,
                                      ServerEnum::SqlServer)),
      'required' => true,
      'allowEmpty' => false
    ),
    'status' => array(
      'rule' => array('inList', array(SuspendableStatusEnum::Active,
                                      SuspendableStatusEnum::Suspended)),
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  // Mapping from server type to model
  public $serverTypeModels = array(
    ServerEnum::HttpServer   => 'HttpServer',
    ServerEnum::LdapServer   => 'LdapServer',
    ServerEnum::MatchServer  => 'MatchServer',
    ServerEnum::Oauth2Server => 'Oauth2Server',
    ServerEnum::SqlServer    => 'SqlServer'
  );
  
  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Boolean $created True if new model is saved (ie: add)
   * @param  Array $options Options, as based to model::save()
   * @return Boolean True on success
   */

  public function afterSave($created, $options = Array()) {
    if($created) {
      // Create an instance of the server type.
      
      $smodel = $this->serverTypeModels[ $this->data['Server']['server_type'] ];

      $server = array();
      $server[$smodel]['server_id'] = $this->id;

      // Note that we have to disable validation because we want to create an empty row.
      if(!$this->$smodel->save($server, false)) {
        return false;
      }
    }

    return true;
  }
}
