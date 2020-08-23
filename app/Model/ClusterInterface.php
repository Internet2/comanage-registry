<?php
/**
 * COmanage Registry Cluster Interface Parent Model
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
 * @package       registry-plugin
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

abstract class ClusterInterface extends AppModel {
  // Define class name for cake
  public $name = "ClusterInterface";
  
  // Plugin configuration (ie: FooAuthenticator, not Authenticator)
  // XXX This is similar to OrgIdentitySourceBackend
  protected $pluginCfg = null;
  
  /**
   * Assign accounts for the specified CO Person.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array   $cluster    Array of Cluster configuration
   * @param  Integer $coPersonId CO Person ID
   * @return Boolean             True if an account was created, false if an account already existed
   * @throws RuntimeException
	 */
  
  abstract public function assign($cluster, $coPersonId);
  
  /**
   * Obtain the configuration for this backend. This will correspond to FooCluster.
   *
   * @since  COmanage Registry v3.3.0
   * @return Array Array of configuration information, as returned by find()
   */
  
  public function getConfig() {
    return $this->pluginCfg;
  }
  
  /**
   * Obtain the CO ID for a record, overriding AppModel behavior.
   *
   * @since  COmanage Registry v3.3.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function findCoForRecord($id) {    
    if(isset($this->validate['cluster_id'])) {
      // Authenticator plugins will refer to an authenticator
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $id;
      $args['contain'][] = 'Cluster';
      
      $cluster = $this->find('first', $args);
      
      if(!empty($cluster['Cluster']['co_id'])) {
        return $cluster['Cluster']['co_id'];
      }
    } else {
      return parent::findCoForRecord($id);
    }
  }
  
  /**
   * Set the plugin configuration for this backend. This will correspond to FooCluster.
   *
   * @since  COmanage Registry v3.3.0
   * @param  Array $cfg Array of configuration information, as returned by find()
   */
  
  public function setConfig($cfg) {
    $this->pluginCfg = $cfg;
  }
  
  /**
	 * Obtain the current Cluster status for a CO Person.
	 *
	 * @since  COmanage Registry v3.3.0
	 * @param  integer $coPersonId			CO Person ID
	 * @return Array Array with values
	 * 							 status: AuthenticatorStatusEnum
	 * 							 comment: Human readable string, visible to the CO Person
   */
  
  abstract public function status($coPersonId);
}
