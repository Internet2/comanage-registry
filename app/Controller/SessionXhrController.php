<?php
  /**
   * COmanage Registry Session Controller
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
   * @since         COmanage Registry v4.0.0
   * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
   */
  
App::uses("StandardController", "Controller");

/* SessionXhr is used to manage session data via ajax calls */
class SessionXhrController extends StandardController {
  // Class name, used by Cake
  public $name = "SessionXhr";

  // There is no DB backend for this controller (yet)
  public $uses = null;

  /**
   * Manage a session variable via ajax. The URL will look something like
   * /registry/user_session/manage/delete/Ui.CoGroups.Search
   *
   * For write, the URL will look something like
   * /registry/user_session/manage/write/Ui.CoGroups.Search.name=test
   *
   * @since  COmanage Registry v4.0.0
   * @param  String method - Session method to call - for the moment just delete and write
   * @param  String keyval - Session key (and optional val for write)
   * @return String status - "not found", "ok", "bad parameter" // XXX Should probably be an enum
   */
  public function manage($method,$keyval) {
    $status = "not found";
    $sessionVal = "";

    switch($method) {
      case "delete":
        $this->Session->delete($keyval);
        $status = "ok";
        break;
      case "write":
        $data = explode("=",$keyval);
        if(count($data) == 2) {
          $this->Session->write($key, $val);
          $status = "ok";
        } else {
          $status = "bad parameter";
        }
        break;
    }

    // return the status code as a simple string
    $this->layout='raw';
    $this->set("body",$status);
  }


  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: User is authenticated
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v4.0.0
   * @return Array Permissions
   */

  function isAuthorized() {
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();

    // All session functions are available to the user
    $p['manage'] = true;

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
