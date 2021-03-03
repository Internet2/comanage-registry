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
   * Parse System Group names. The ones having the prefix CO: or CO:COU:
   *
   * @param string $name      Group's name as stored in the COmanage Database
   * @param string $cur_co    Current CO data
   * @return array
   *
   * @since  COmanage Registry        v4.0.0
   */
  public function systemGroupHFormat($name, $cur_co = "") {
    $auto_group_parts = explode(":", $name);
    $fname_group = array();
    // Is this a CO auto group or a CO:COU
    if(strpos($name, "CO:COU:") === false) {
      // This is CO auto group
      $fname_group['key'] = 'AUTO';
      $fname_group['name']['name'] = filter_var($cur_co["Co"]["name"], FILTER_SANITIZE_SPECIAL_CHARS) . '&nbsp;';
      $fname_group['name']['badge'] = '<small>' . $this->badgeIt(_txt('ct.cos.1'), BadgeColorModeEnum::LightGray, false, true) . '</small>';
      $auto_group_parts = array_slice($auto_group_parts, 1);
    } else {
      // This is CO:COU auto group
      $fname_group['key'] = $auto_group_parts[2];
      $fname_group['name']['name'] = filter_var($auto_group_parts[2], FILTER_SANITIZE_SPECIAL_CHARS) . '&nbsp;';
      $fname_group['name']['badge'] = "<small>" . $this->badgeIt(_txt('ct.cous.1'), BadgeColorModeEnum::LightGray, false, true) . '</small>';
      $auto_group_parts = array_slice($auto_group_parts, 3);
    }

    // XXX admins Group case
    if(in_array('admins', $auto_group_parts)) {
      $fname_group['name'] =
        $fname_group['name']['name']
        . $fname_group['name']['badge']
        . '<small>' . $this->badgeIt(_txt('fd.el.gr.admins'), BadgeColorModeEnum::LightGray) . '</small>';
      return $fname_group;
    }

    $fname_group['badge'] = array();
    // Extract Badges
    foreach($auto_group_parts as $part) {
      if(in_array($part, array("active", "all"))) {
        $fname_group['badge'][] = array(
          'order' => constant('BadgeOrderEnum::' . ucfirst($part)),
          'text' => _txt('fd.group.mem') . ' ' . ucfirst($part),
          'color' => BadgeColorModeEnum::Gray,
        );
      } elseif($part !== "members") {
        $fname_group['badge'][] = array(
          'order' => BadgeOrderEnum::Owner,
          'text' => ucfirst($part),
          'color' => BadgeColorModeEnum::Blue,
        );
      }
    }

    $fname_group['name'] =
      $fname_group['name']['name']
      . $fname_group['name']['badge']
      . '<small>' . $this->badgeIt(_txt('fd.co_group.auto'), BadgeColorModeEnum::LightGray, false, true) . '</small>';;
    return $fname_group;
  }

  /**
   * Helper which will produce Bootstrap based Badge
   * Bootstrap v5 uses different notation, e.g. bg-success instead of badge success. So, keep everything in one place
   * to facilitate an upgrade process
   *
   * @since  COmanage Registry        v4.0.0
   * @param string $title             The title of the badge
   * @param string $type              Define the type of Badge. The value should be one of
   *                                  [primary,secondary,success,danger,warning,info,light,dark]
   *                                  Defaults to light
   * @param boolean $badge_pill       Is this a badge-pill. Defaults to false
   * @param boolean $badge_outline    Is this an outlined badge. Defaults to false
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