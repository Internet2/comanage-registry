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
      'EmailVerify'   => 1,   // fa fa-envelope
      'PrimaryName'   => 2,   // fa fa-tag
      'AuthEvent'     => 3,   // fa fa-sign-in
      'Provision'     => 4,   // fa fa-forward
      'PetitionView'  => 9,   // fa fa-user-plus
      'View'          => 10,  // fa fa-eye
      'Edit'          => 12,  // fa fa-edit
      'Relink'        => 14,  // fa fa-link
      'Unlink'        => 17,  // fa fa-chain-broken
      'Delete'        => 20,  // fa fa-trash
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
      'EmailVerify'   =>  'fa fa-envelope',
      'PrimaryName'   =>  'fa fa-tag',
      'AuthEvent'     =>  'fa fa-sign-in',
      'Provision'     =>  'fa fa-forward',
      'PetitionView'  =>  'fa fa-user-plus',
      'View'          =>  'fa fa-eye',
      'Edit'          =>  'fa fa-edit',
      'Relink'        =>  'fa fa-link',
      'Unlink'        =>  'fa fa-chain-broken',
      'Delete'        =>  'fa fa-trash',
    );

    return $icon[$action];
  }

}