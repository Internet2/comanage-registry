<?php
/**
 * COmanage Registry Duplicate Check Enroller Plugin Controller
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SEWController", "Controller");

class DuplicateCheckEnrollersController extends SEWController {
  // Class name, used by Cake
  public $name = "DuplicateCheckEnrollers";

  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'co_enrollment_flow_wedge_id' => 'asc'
    )
  );

  public $edit_contains = array(
    'CoEnrollmentFlowWedge' => array('CoEnrollmentFlow')
  );

  public $view_contains = array(
    'CoEnrollmentFlowWedge' => array('CoEnrollmentFlow')
  );

  /**
   * Callback after controller methods are invoked but before views are rendered.
   *
   * @since  COmanage Registry v4.3.0
   */

  public function beforeRender() {
    // We do not allow REST
    if($this->request->is('restful')) {
      $this->Api->restResultHeader(HttpStatusCodesEnum::HTTP_NOT_IMPLEMENTED, _txt('er.notimpl'));
    }

    // Get identifier types
    $Identifier = ClassRegistry::init('Identifier');
    $availableTypes = $Identifier->types($this->cur_co['Co']['id'], 'type');
    $this->set('vv_available_types', $availableTypes);

    parent::beforeRender();
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions sets with calculated permissions
   *
   * @since  COmanage Registry v4.3.0
   * @return Array Permissions
   */

  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();

    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();

    // Determine what operations this user can perform

    // Delete an existing Identifier Enroller?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit an existing Identifier Enroller?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View all existing Identifier Enroller?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing Identifier Enroller?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }

  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   * @since  COmanage Registry v4.3.0
   */

  public function performRedirect() {
    $target = array();
    $target['plugin'] = 'duplicate_check_enroller';
    $target['controller'] = "duplicate_check_enrollers";
    $target['action'] = 'edit';
    $target[] = $this->request->params["pass"][0];

    $this->redirect($target);
  }
}