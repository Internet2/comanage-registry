<?php
  /**
   * COmanage Registry CO Configuration Controller
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
   * @since         COmanage Registry v0.9.2
   * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
   */

  App::uses("StandardController", "Controller");

  class CoConfigurationController extends StandardController {
    // Class name, used by Cake
    public $name = "CoConfiguration";

    // This controller needs a CO to be set
    public $requires_co = true;

    /**
     * For Models that accept a CO ID, find the provided CO ID.
     * - precondition: A coid must be provided in $this->request (params or data)
     *
     * @since  COmanage Registry v3.0.0
     * @return Integer The CO ID if found, or -1 if not
     */

    public function parseCOID($data = null) {
      if($this->action == 'configuration') {
        if(isset($this->request->params['named']['co'])) {
          return $this->request->params['named']['co'];
        }
      }

      return parent::parseCOID();
    }

    /**
     * Render the CO Configuration page.
     *
     * @since  COmanage Registry v3.0.0
     */

    public function index() {
      $this->set('title_for_layout', _txt('ct.co_configuration.1', array($this->cur_co['Co']['name'])));
    }

    /**
     * Authorization for this Controller, called by Auth component
     * - precondition: Session.Auth holds data used for authz decisions
     * - postcondition: $permissions set with calculated permissions
     *
     * @since  COmanage Registry v3.0.0
     * @return Array Permissions
     */

    function isAuthorized() {
      $roles = $this->Role->calculateCMRoles();

      // Construct the permission set for this user, which will also be passed to the view.
      $p = array();

      // Determine what operations this user can perform
      // Configure the specified CO?
      $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);

      $this->set('permissions', $p);
      return $p[$this->action];
    }
  }