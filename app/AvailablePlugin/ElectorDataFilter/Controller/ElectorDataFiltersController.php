<?php
/**
 * COmanage Registry Elector Data Filters Controller
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("StandardController", "Controller");

class ElectorDataFiltersController extends StandardController {
  // Class name, used by Cake
  public $name = "ElectorDataFilters";

  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'ElectorDataFilter.attribute_name' => 'asc'
    )
  );

  public $requires_co = true;

  public $edit_contains = array(
    'ElectorDataFilterPrecedence' => array(
      'conditions' => array(
        'ElectorDataFilterPrecedence.deleted != true',
        'ElectorDataFilterPrecedence.elector_data_filter_precedence_id is NULL'
      )
    ),
    'DataFilter'
  );

  public $view_contains = array(
    'ElectorDataFilterPrecedence' => array(
      'conditions' => array(
        'ElectorDataFilterPrecedence.deleted != true',
        'ElectorDataFilterPrecedence.elector_data_filter_precedence_id is null'
      )
    ),
    'DataFilter'
  );

  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request->params
   *
   * @since  COmanage Registry v4.1.0
   */

  function beforeRender() {
    $attribute_types = array();

    foreach($this->ElectorDataFilter->validate["attribute_name"]["content"]["rule"][1] as $m) {
      $modell = ClassRegistry::init($m);
      $available_types = $modell->types($this->cur_co["Co"]["id"], 'type');
      foreach ($available_types as $type) {
        $attribute_types[$m][$type] = $type;
      }
      natsort($attribute_types[$m]);
    }
    $attribute_names = array_combine(
      $this->ElectorDataFilter->validate["attribute_name"]["content"]["rule"][1],
      $this->ElectorDataFilter->validate["attribute_name"]["content"]["rule"][1]
    );
    natsort($attribute_names);
    $this->set('vv_attribute_names', $attribute_names);
    $this->set('vv_attribute_types', $attribute_types);
    parent::beforeRender();
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
    $roles = $this->Role->calculateCMRoles();

    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();

    // Delete an existing Elector Data Filter?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];

    // Edit an existing Elector Data Filter?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];

    // View all existing Elector Data Filter?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];

    // View an existing Elector Data Filter?
    $p['view'] = $roles['cmadmin'] || $roles['coadmin'];

    $this->set('permissions', $p);
    return($p[$this->action]);
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.1.0
   */

  public function performRedirect() {
    $modelName = $pluginName = Inflector::singularize($this->name);

    $target = array();
    $target['plugin'] = Inflector::underscore($pluginName);
    $target['controller'] = Inflector::tableize($modelName);
    $target['action'] = 'edit';
    $target[] = $this->request->data['ElectorDataFilter']['id'];

    $this->redirect($target);
  }
}
