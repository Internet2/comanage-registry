<?php
/*
 * COmanage Registry Badge List
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
 * List of Badge configuration
 * $vv_badge_list = array(
 *   array(
 *    'order' => BadgeOrderEnum::Status,
 *    'text' => "mytext",
 *    'color' => BadgeColorModeEnum::Blue,
 *    'outline' => false,
 *    'pill' => true,
 *    'icon' => 'material-icons-key',
 *   ),
 * );
 *
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<?php
// Sort the Badges
usort($vv_badge_list, function ($item1, $item2) {
  if ($item1['order'] == $item2['order']) return 0;
  return $item1['order'] < $item2['order'] ? -1 : 1;
});

foreach($vv_badge_list as $badge) {
  $badge_classes = array();
  $icon = "";

  if(isset($badge['pill']) && $badge['pill']) {
    $badge_classes[] = "badge-pill";
  }
  if(!empty($badge['icon'])) {
    $icon = '<i class="mr-1 material-icons" aria-hidden="true">' . $badge["icon"] .'</i>';
  }
  if(isset($badge['outline']) && $badge['outline']) {
    $badge_classes[] = "badge-outline-" . $badge['color'];
  } else {
    $badge_classes[] = "badge-" . $badge['color'];
  }

  // Print the Badge
  print $this->Html->tag(
    'span',
    $icon . $badge['text'],
    array(
      'class' => 'mr-1 badge ' . implode(' ', $badge_classes),
      'escape' => false,
    )
  );
}