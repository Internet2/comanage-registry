<?php
/**
 * COmanage Registry Menu Helper
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

App::uses('AppHelper', 'View/Helper');
App::import('Lib/lang.php');

class MenuHelper extends AppHelper {

  public $helpers = array('Html');

  /**
   * Get the Menu Order per action
   *
   * @param string $action
   * @return int|null
   *
   * @since  COmanage Registry        v4.0.0
   */
  public function getMenuOrder($action) {
    if(empty($action)) {
      return null;
    }

    $order = array(
      'EmailVerify'   => 1,   // material-icons "mail_outline"
      'PrimaryName'   => 2,   // material-icons "local_offer"
      'AuthEvent'     => 3,   // material-icons "login"
      'Provision'     => 4,   // material-icons "fast_forward"
      'PetitionView'  => 9,   // material-icons "person_add"
      'View'          => 10,  // material-icons "visibility"
      'Edit'          => 12,  // material-icons "edit"
      'Relink'        => 14,  // material-icons "link"
      'Unlink'        => 17,  // material-icons "link_off"
      'Delete'        => 20,  // material-icons "delete"
    );

    return $order[$action];
  }

  /**
   * Get the Menu Icon per action
   *
   * @param string $action
   * @return int|null
   *
   * @since  COmanage Registry        v4.0.0
   */
  public function getMenuIcon($action) {
    if(empty($action)) {
      return null;
    }

    $icon = array(
      'EmailVerify'   =>  'mail',
      'PrimaryName'   =>  'local_offer',
      'AuthEvent'     =>  'login',
      'Provision'     =>  'fast_forward',
      'PetitionView'  =>  'person_add',
      'View'          =>  'visibility',
      'Edit'          =>  'edit',
      'Relink'        =>  'link',
      'Unlink'        =>  'link_off',
      'Delete'        =>  'delete',
    );

    return $icon[$action];
  }

}