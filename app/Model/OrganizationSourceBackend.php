<?php
/**
 * COmanage Registry Organization Source Backend (Plugin) Parent Model
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

abstract class OrganizationSourceBackend extends AppModel {  
  // Plugin in configuration (ie: FooSource, not OrganizationSource)
  protected $pluginCfg = null;
  
  /**
   * Obtain the configuration for this backend. This will correspond to FooSource.
   *
   * @since  COmanage Registry v4.4.0
   * @return Array Array of configuration information, as returned by find()
   */
  
  public function getConfig() {
    return $this->pluginCfg;
  }
  
  /**
   * Obtain all available records in the Organization Source, as a list of unique keys
   * (ie: suitable for passing to retrieve()).
   *
   * @since  COmanage Registry v4.4.0
   * @return Array Array of unique keys
   * @throws DomainException If the backend does not support this type of requests
   */
  
  abstract public function inventory();

  /**
   * Perform any tasks prior to beginning a Sync operation.
   * 
   * @since  COmanage Registry v4.4.0
   * @param  int    $coJobId   CO Job ID
   * @throws RuntimeException if processing should not proceed
   */

  public function preRunChecks($coJobId) { return; }
  
  /**
   * Retrieve a single record from the Organization Source. The return array consists
   * of two entries: 'raw', a string containing the raw record as returned by the
   * IdentitySource backend, and 'Organization', the data in Organization format.
   *
   * @since  COmanage Registry v4.4.0
   * @param  String $id Unique key to identify record
   * @return Array As specified
   * @throws InvalidArgumentException if not found
   * @throws OverflowException if more than one match
   * @throws RuntimeException on backend specific errors
   */
  
  abstract public function retrieve($id);

  /**
   * Perform a search against the OrganizationSource. The returned array should be of
   * the form uniqueId => attributes, where uniqueId is a persistent identifier
   * to obtain the same record and attributes represent an OrganizationIdentity, including
   * related models.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Array $attributes Array in key/value format, where key is the same as returned by searchableAttributes()
   * @return Array Array of search results, as specified
   */
  
  abstract public function search($attributes);
  
  /**
   * Generate the set of searchable attributes for the OrganizationSource.
   * The returned array should be of the form key => label, where key is meaningful
   * to the OrganizationSource (eg: a number or a field name) and label is the localized
   * string to be displayed to the user.
   *
   * @since  COmanage Registry v4.4.0
   * @return Array As specified
   */
  
  abstract public function searchableAttributes();
  
  /**
   * Set the plugin configuration for this backend. This will correspond to FooSource.
   *
   * @since  COmanage Registry v4.4.0
   * @param  Array $cfg Array of configuration information, as returned by find()
   */
  
  public function setConfig($cfg) {
    $this->pluginCfg = $cfg;
  }
}
