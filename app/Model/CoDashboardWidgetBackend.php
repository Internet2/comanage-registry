<?php
/**
 * COmanage Registry CO Dashboard Widget Backend Parent Model
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

abstract class CoDashboardWidgetBackend extends AppModel {
  // Define class name for cake
  public $name = "CoWidget";
  
  // Plugin configuration (ie: CoFooWidget)
  protected $pluginCfg = null;
  
  /**
   * Obtain the configuration for this backend. This will correspond to CoFooWidget.
   *
   * @since  COmanage Registry v3.2.0
   * @return Array Array of configuration information, as returned by find()
   */
  
  public function getConfig() {
    return $this->pluginCfg;
  }
  
  /**
   * Obtain the CO ID for a record, overriding AppModel behavior.
   *
   * @since  COmanage Registry v3.2.0
   * @param  integer Record to retrieve for
   * @return integer Corresponding CO ID, or NULL if record has no corresponding CO ID
   * @throws InvalidArgumentException
   * @throws RunTimeException
   */
  
  public function findCoForRecord($id) {
    if(isset($this->validate['co_dashboard_widget_id'])) {
      // Dashboard Widget Plugins will refer to Dashboard Widget, which in turn
      // refers to a Dashboard
      
      $args = array();
      $args['conditions'][$this->alias.'.id'] = $id;
      $args['contain']['CoDashboardWidget'][] = 'CoDashboard';
    
      $codw = $this->find('first', $args);
      
      if(!empty($codw['CoDashboardWidget']['CoDashboard']['co_id'])) {
        return $codw['CoDashboardWidget']['CoDashboard']['co_id'];
      }
    } else {
      return parent::findCoForRecord($id);
    }
    
    throw new RuntimeException(_txt('er.co.fail'));
  }
  
  /**
   * Set the plugin configuration for this backend. This will correspond to CoFooWidget.
   *
   * @since  COmanage Registry v3.2.0
   * @param  Array $cfg Array of configuration information, as returned by find()
   */
  
  public function setConfig($cfg) {
    $this->pluginCfg = $cfg;
  }
}
