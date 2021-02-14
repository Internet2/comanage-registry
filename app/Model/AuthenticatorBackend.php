<?php
/**
 * COmanage Registry Authenticator Backend Parent Model
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

abstract class AuthenticatorBackend extends AppModel {
  // Define class name for cake
  public $name = "AuthenticatorBackend";
  
  // Plugin configuration (ie: FooAuthenticator, not Authenticator)
  // XXX This is similar to OrgIdentitySourceBackend
  protected $pluginCfg = null;
  
  /**
   * Obtain current data suitable for passing to manage().
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $id         Authenticator ID
   * @param  integer $backendId  Authenticator Backend ID
   * @param  integer $coPersonId CO Person ID
   * @return Array As returned by find
   * @throws RuntimeException
   */
  
  public function current($id, $backendId, $coPersonId) {
    // As of v4.0.0, we'll provide default behavior for "simple" cases
    // (where the object being managed matches our alias)
    
    // $authplugin = (eg) PasswordAuthenticator
    // $authmodel = (eg) Password
    $authplugin = $this->alias;
    $authmodel = substr($authplugin, 0, -13);
    
    $args = array();
    $args['conditions'][$authmodel.'.password_authenticator_id'] = $backendId;
    $args['conditions'][$authmodel.'.co_person_id'] = $coPersonId;
    $args['contain'] = false;

    return $this->$authmodel->find('all', $args);
  }
  
  /**
   * Obtain the configuration for this backend. This will correspond to FooAuthenticator.
   *
   * @since  COmanage Registry v3.1.0
   * @return Array Array of configuration information, as returned by find()
   */
  
  public function getConfig() {
    return $this->pluginCfg;
  }
  
  /**
   * Obtain the CO ID for a record, overriding AppModel behavior.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function findCoForRecord($id) {    
    if(isset($this->validate['authenticator_id'])) {
      // Authenticator plugins will refer to an authenticator
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $id;
      $args['contain'][] = 'Authenticator';
    
      $copt = $this->find('first', $args);
      
      if(!empty($copt['Authenticator']['co_id'])) {
        return $copt['Authenticator']['co_id'];
      }
    } else {
      return parent::findCoForRecord($id);
    }
    
    throw new RuntimeException(_txt('er.co.fail'));
  }
  
  /**
   * Perform backend specific actions on a lock operation.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $id         Authenticator ID
   * @param  integer $coPersonId CO Person ID
   * @return Boolean             true on success
   * @throws RuntimeException
   */
  
  public function lock($id, $coPersonId) {
    // Plugin can override this but is not required to
    return true;
  }
  
  /**
   * Manage Authenticator data, as submitted from the view.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array   $data            Array of Authenticator data submitted from the view
   * @param  Integer $actorCoPersonId Actor CO Person ID
   * @return String Human readable (localized) result comment
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  
  public function manage($data, $actorCoPersonId) {
    // Plugin either needs to override and implement this, or override and implement manage()
    throw new RuntimeException(_txt('er.notimpl'));
  }
  
  /**
   * Reset Authenticator data for a CO Person.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $coPersonId      CO Person ID
   * @param  integer $actorCoPersonId Actor CO Person ID
   * @return boolean true on success
   */
  
  abstract public function reset($coPersonId, $actorCoPersonId);
  
  /**
   * Set the plugin configuration for this backend. This will correspond to FooAuthenticator.
   *
   * @since  COmanage Registry v3.1.0
   * @param  Array $cfg Array of configuration information, as returned by find()
   */
  
  public function setConfig($cfg) {
    $this->pluginCfg = $cfg;
  }
  
  /**
   * Obtain the current Authenticator status for a CO Person.
   *
   * @since  COmanage Registry v3.1.0
   * @param  integer $coPersonId      CO Person ID
   * @return Array Array with values
   *               status: AuthenticatorStatusEnum
   *               comment: Human readable string, visible to the CO Person
   */
  
  abstract public function status($coPersonId);
  
  /**
   * Perform backend specific actions on an unlock operation.
   *
   * @since  COmanage Registry v4.0.0
   * @param  integer $id         Authenticator ID
   * @param  integer $coPersonId CO Person ID
   * @return Boolean             true on success
   * @throws RuntimeException
   */
  
  public function unlock($id, $coPersonId) {
    // Plugin can override this but is not required to
    return true;
  }
}
