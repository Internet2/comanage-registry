<?php
/**
 * COmanage Registry Elector Data Filter Precedences Controller
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

class ElectorDataFilterPrecedencesController extends StandardController
{
  // Class name, used by Cake
  public $name = "ElectorDataFilterPrecedences";

  public $uses = array(
    // ElectorDataFilterPrecedence should go first!
    'ElectorDataFilter.ElectorDataFilterPrecedence',
    'ElectorDataFilter.ElectorDataFilter'
  );

  public $edit_contains = array(
    'ElectorDataFilter' => array('DataFilter'),
    'OrgIdentitySource' => array(
      'conditions' => array(
        'OrgIdentitySource.deleted != true',
        'OrgIdentitySource.org_identity_source_id is NULL'
      )
    )
  );

  /**
   * Callback after controller methods are invoked but before views are rendered.
   * - precondition: Request Handler component has set $this->request
   *
   * @since  COmanage Registry v4.1.0
   */

  public function beforeRender() {
    parent::beforeRender();

    // Pull the types from the parent table
    if($this->action == 'add') {
      if(empty($this->request->params["named"]["electfilterid"])) {
        throw new InvalidArgumentException(_txt('er.elector_data_filter.electfilterid.specify'), HttpStatusCodesEnum::HTTP_BAD_REQUEST);
      }

      $args = array();
      $args['conditions']['ElectorDataFilter.id'] = $this->request->params["named"]["electfilterid"];
      $args['contain'] = array('DataFilter');

      $elector_data_filters = $this->ElectorDataFilter->find('all', $args);
      if(empty($elector_data_filters)) {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.elector_data_filters.1'),
                                                  filter_var($this->request->params["named"]["electfilterid"],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
      // Elector data filter configuration
      $this->set('elector_data_filters', $elector_data_filters);
      $attribute_model = $elector_data_filters[0]['ElectorDataFilter']['attribute_name'];
    } elseif ($this->action == 'edit') {
      $attribute_model = $this->viewVars["elector_data_filter_precedences"][0]["ElectorDataFilter"]["attribute_name"];
    }

    // List of available types
    $modell = ClassRegistry::init($attribute_model);
    $available_types = $modell->types($this->cur_co["Co"]["id"], 'type');
    $this->set('vv_attribute_types', $available_types);

    // Provide a list of org identity sources
    $args = array();
    $args['conditions']['OrgIdentitySource.co_id'] = $this->cur_co['Co']['id'];
    $args['conditions']['OrgIdentitySource.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = false;

    $OrgIdentitySource = ClassRegistry::init('OrgIdentitySource');
    $this->set('vv_avail_ois', $OrgIdentitySource->find('list', $args));


    if ($this->request->action == 'add') {
      $this->set('title_for_layout', _txt('op.add.new', array(_txt('ct.elector_data_filter_precedences.1'))));
    }
  }

  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v4.1.0
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */

  protected function calculateImpliedCoId($data = null) {
    if(empty($this->request->params["named"]["electfilterid"]) && $this->action == "add") {
      throw new InvalidArgumentException(_txt('er.elector_data_filter.electfilterid.specify'), HttpStatusCodesEnum::HTTP_BAD_REQUEST);
    }

    if(empty($this->request->params["pass"][0]) && in_array($this->action, array("delete", "edit"))) {
      throw new InvalidArgumentException(_txt('er.elector_data_filter.id.specify'), HttpStatusCodesEnum::HTTP_BAD_REQUEST);
    }

    $args = array();
    if(!empty($this->request->params["pass"][0])) {
      $args['joins'][0]['table']                            = 'cm_elector_data_filter_precedences';
      $args['joins'][0]['alias']                            = 'ElectorDataFilterPrecedence';
      $args['joins'][0]['type']                             = 'INNER';
      $args['joins'][0]['conditions'][0]                    = 'ElectorDataFilter.id=ElectorDataFilterPrecedence.elector_data_filter_id';
      $args['conditions']['ElectorDataFilterPrecedence.id'] = $this->request->params["pass"][0];
    } else {
      $args['conditions']['ElectorDataFilter.id'] = $this->request->params["named"]["electfilterid"];
    }
    $args['contain'] = array('DataFilter');

    $elector_data_filter = $this->ElectorDataFilter->find('first', $args);
    if(empty($elector_data_filter)) {
      if(!empty($this->request->params["pass"][0])) {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.elector_data_filter_precedences.1'),
                                                  filter_var($this->request->params["pass"][0],FILTER_SANITIZE_SPECIAL_CHARS))));
      }
      throw new InvalidArgumentException(_txt('er.notfound',
                                              array(_txt('ct.elector_data_filters.1'),
                                                filter_var($this->request->params["named"]["electfilterid"],FILTER_SANITIZE_SPECIAL_CHARS))));
    }

     $coId = $elector_data_filter['DataFilter']['co_id'];
     if($coId) {
       return $coId;
     }

    return parent::calculateImpliedCoId();
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

    // Delete an existing Elector Data Filter Precedences?
    $p['delete'] = $roles['cmadmin'] || $roles['coadmin'];

    // Edit an existing Elector Data Filter Precedences?
    $p['edit'] = $roles['cmadmin'] || $roles['coadmin'];

    // Add an existing Elector Data Filter Precedences?
    $p['add'] = $roles['cmadmin'] || $roles['coadmin'];

    // View all existing Elector Data Filter Precedences?
    $p['index'] = $roles['cmadmin'] || $roles['coadmin'];

    // View an existing Elector Data Filter Precedences?
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
    $target = array();
    $target['plugin'] = 'elector_data_filter';
    $target['controller'] = "elector_data_filters";
    $target['action'] = 'edit';
    if(!empty($this->request->query["electfilterid"])) {
      $target[] = $this->request->query["electfilterid"];
    } elseif (!empty($this->ElectorDataFilterPrecedence->data['ElectorDataFilter']['id'])) {
      $target[] = $this->ElectorDataFilterPrecedence->data['ElectorDataFilter']['id'];
    } else {
      $target[] = $this->data['ElectorDataFilterPrecedence']['elector_data_filter_id'];
    }

    $this->redirect($target);
  }
}