<?php
/**
 * COmanage Registry Test (Invitation) Confirmer Model
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class TestConfirmer extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "confirmer";
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
  /**
   * Expose menu items.
   * 
   * @since COmanage Registry v3.1.0
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   */
  
  public function cmPluginMenus() {
    return array();
  }
  
  /**
   * Determine if this plugin wants to handle a given request.
   *
   * @since COmanage Registry v3.1.0
   * @param  Integer $coId       CO ID
   * @param  Array   $coInvite   Array of data representing the relevant CO Invitation
   * @param  Array   $coPetition Array of data representing the relevant CO Petition, if applicable
   * @return Boolean True if the plugin wants to handle the request, false otherwise.
   */
  
  public function willHandle($coId, $coInvite, $coPetition) {
    // We always want to handle the request!
    return true;
  }
}
