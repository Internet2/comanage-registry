<?php
/**
 * COmanage Registry Meta Model
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
 * @since         COmanage Registry v0.9.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class Meta extends AppModel {
  // Define class name for cake
  public $name = "Meta";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Default display field for cake generated views
  public $displayField = "upgrade_version";
  
  // Validation rules for table elements
  public $validate = array(
    'upgrade_version' => array(
      'rule' => '/.*/',
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  /**
   * Determine the current "upgrade" version.
   *
   * @since  COmanage Registry v0.9.4
   * @return Current version
   */
  
  public function getUpgradeVersion() {
    // If the table cm_meta doesn't exist or is empty, we assume we're at
    // a version prior to 0.9.4 (which is when this was introduced). This should
    // be OK because new installs won't call this function.
    
    $ret = "0.9.3";
    
    try {
      $dbc = $this->getDataSource();
      $dbprefix = $dbc->config['prefix'];
      
      $v = $this->query("SELECT upgrade_version FROM " . $dbprefix . "meta");
      
      // We appear to get results in a slightly different format for Postgres
      // (first check) vs MySQL (second check).
      
      if(!empty($v[0][0]['upgrade_version'])) {
        $ret = $v[0][0]['upgrade_version'];
      } elseif(!empty($v[0]['cm_meta']['upgrade_version'])) {
        $ret = $v[0]['cm_meta']['upgrade_version'];
      }
    }
    catch(MissingTableException $e) {
      // The first time through cm_meta won't exist. This should be as
      // part of an upgrade to 0.9.4, so we'll set the current version to 0.9.3.
    }
    // We'll let other Exceptions pass up
    
    return $ret;
  }
  
  /**
   * Update the current "upgrade" version.
   *
   * @since  COmanage Registry v0.9.4
   * @param  String $version New current version
   * @param  Boolean $insert Whether to assume an insert rather than an update
   * @return Boolean True on success
   */
  
  public function setUpgradeVersion($version, $insert=false) {
    $dbc = $this->getDataSource();
    $dbprefix = $dbc->config['prefix'];
    
    $sql = null;
    
    if($insert) {
      $sql = "INSERT INTO " . $dbprefix . "meta (upgrade_version) VALUES ("
             . $dbc->value($version) . ")";
    } else {
      $sql = "UPDATE " . $dbprefix . "meta SET upgrade_version = "
             . $dbc->value($version);
    }
    
    $this->query($sql);
    
    return true;
  }
}
