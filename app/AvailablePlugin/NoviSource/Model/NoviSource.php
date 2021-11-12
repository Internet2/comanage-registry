<?php
/**
 * COmanage Registry NoviSource OrgIdentitySource Model
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class NoviSource extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "orgidsource";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  // Request HTTP servers
  public $cmServerType = ServerEnum::HttpServer;
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "OrgIdentitySource",
    "Server"
  );
  
  // Default display field for cake generated views
  public $displayField = "server_id";
  
  // Validation rules for table elements
  public $validate = array(
    'org_identity_source_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'server_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'unfreeze' => 'CO'
      )
    )
  );
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v4.1.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
}
