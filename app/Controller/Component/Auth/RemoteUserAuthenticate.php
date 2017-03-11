<?php
/**
 * COmanage Registry Remote User Authentication
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('BaseAuthenticate', 'Controller/Component/Auth');

class RemoteUserAuthenticate extends BaseAuthenticate {
  /**
   * Authenticate a user via $REMOTE_USER
   * - precondition: User has been authenticated via web server and $REMOTE_USER is populated
   *
   * @since  COmanage Registry v0.1
   * @param  CakeRequest Request information
   * @param  CakeResponse Response structure
   * @return array Array of authenticated user information
   */
  
  public function authenticate(CakeRequest $request, CakeResponse $response) {
    // Grab the authenticated user from the session (as set by webroot/auth/login/index.php).
    // We don't seem to have access to the SessionComponent here, so just read it natively.
    
    if(isset($_SESSION['Auth']['external']['user'])) {
      $u = $_SESSION['Auth']['external']['user'];
    }
    
    if(empty($u)) {
      return false;
    }
    
    // We only populate username here... it's up to UsersController to populate
    // any other user information
    $ret['username'] = $u;
    
    return($ret);
  }
}