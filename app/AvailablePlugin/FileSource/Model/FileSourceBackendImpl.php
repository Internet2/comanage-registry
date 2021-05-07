<?php
/**
 * COmanage Registry File OrgIdentitySource Abstract Backend Model
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

abstract class FileSourceBackendImpl {
  protected $pluginCfg = null;
  
  public function __construct($pluginCfg) {
    $this->pluginCfg = $pluginCfg;
  }
  
  /**
   * Generate the set of attributes for the IdentitySource that can be used to map
   * to group memberships. The returned array should be of the form key => label,
   * where key is meaningful to the IdentitySource (eg: a number or a field name)
   * and label is the localized string to be displayed to the user. Backends should
   * only return a non-empty array if they wish to take advantage of the automatic
   * group mapping service.
   *
   * @since  COmanage Registry v4.0.0
   * @return Array As specified
   */
  
  abstract public function groupableAttributes();
  
  /**
   * Convert a raw result, as from eg retrieve(), into an array of attributes that
   * can be used for group mapping.
   *
   * @since  COmanage Registry v4.0.0
   * @param  String $raw Raw record, as obtained via retrieve()
   * @return Array Array, where keys are attribute names and values are lists (arrays) of attributes
   */
  
  abstract public function resultToGroups($raw);
    
  /**
   * Convert a search result into an Org Identity.
   *
   * @since  COmanage Registry v4.0.0
   * @param  Array $result File Search Result
   * @return Array Org Identity and related models, in the usual format
   */
  
  abstract public function resultToOrgIdentity($result);
  
  /**
   * Search a CSV file.
   *
   * @since  COmanage Registry v4.0.0
   * @param  Array $attributes Attributes to query (ie: searchableAttributes()), or null to obtain a list of all SORIDs
   * @return Array Search results
   * @throws RuntimeException
   */
  
  abstract public function searchFile($attributes=null);
  
  /**
   * Set the plugin configuration for this backend.
   *
   * @since  COmanage Registry v4.0.0
   * @param  Array $cfg Array of configuration information, as returned by find()
   */
  
  public function setConfig($pluginCfg) {
    $this->pluginCfg = $pluginCfg;
  }
}