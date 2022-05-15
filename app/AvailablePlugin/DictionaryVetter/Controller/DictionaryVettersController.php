<?php
/**
 * COmanage Registry Dictionary Vetters Controller
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

App::uses("StandardPluginController", "Controller");

class DictionaryVettersController extends StandardPluginController {
  // Class name, used by Cake
  public $name = "DictionaryVetters";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'dictionary_vetter_id' => 'asc'
    )
  );

  /**
   * Callback before views are rendered.
   *
   * @since  COmanage Registry v4.1.0
   */

  function beforeRender() {
    parent::beforeRender();

    $args = array();
    $args['conditions']['Dictionary.co_id'] = $this->cur_co['Co']['id'];
    // We only support Standard Dictionaries because it's not clear what it would
    // mean to validate against on Organization or Department
    $args['conditions']['Dictionary.mode'] = DictionaryModeEnum::Standard;
    $args['fields'] = array('id', 'description');

    $this->set('vv_available_dictionaries', $this->DictionaryVetter->Dictionary->find('list', $args));
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.1.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $p = $this->calculateVetterPermissions();
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
