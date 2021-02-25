<?php
/**
 * COmanage Registry Utils Helper
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

class UtilsHelper extends AppHelper {

  public $helpers = array('Html');

  /**
   * Helper which will produce Bootstrap based Badge
   * Bootstrap v5 uses different notation, e.g. bg-success instead of badge success. So, keep everything in one place
   * to facilitate the upgrade process
   *
   * @since  COmanage Registry        v4.0.0
   * @param string $title             The title of the badge
   * @param string $type              Define the type of Badge. The value should be one of
   *                                  [primary,secondary,success,danger,warning,info,light,dark]
   *                                  Defaults to light
   * @param boolean $badge_pill       Is this a badge-pill. Defaults to false
   * @param boolean $badge_outline    Is this an outlined badge. Defaults to false
   * @todo Make this a cell on framework migration
   *
   */
  public function badgeIt($title, $type = 'secondary', $badge_pill = false, $badge_outline = false) {
    $badge_classes = array();

    if($badge_pill) {
      $badge_classes[] = "badge-pill";
    }
    if($badge_outline) {
      $badge_classes[] = "badge-outline-" . $type;
    } else {
      $badge_classes[] = "badge-" . $type;
    }

    return $this->Html->tag(
      'span',
      $title,
      array(
        'class' => 'mr-1 badge ' . implode(' ', $badge_classes),
        'escape' => false,
      )
    );
  }

}