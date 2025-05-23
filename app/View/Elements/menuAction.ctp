<?php
/*
 * COmanage Registry Action Menu
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

  // If we're not passed an array of actions, we'll just return an empty string
  $defaultActionMenuClasses = 'field-actions-menu dropdown dropleft';
  $menuClasses = !empty($vv_menu_classes) ? $defaultActionMenuClasses . ' ' . $vv_menu_classes : $defaultActionMenuClasses;
?>

<div id="action-menu_<?php print md5($vv_attr_mdl . $vv_attr_id); ?>"
     class="<?php print $menuClasses ?>">
  <?php
  $linkparams = array(
    'id' => 'action-menu-content_' . md5($vv_attr_mdl . $vv_attr_id),
    'class' => 'action-list-toggle',
    'escape' => false,
    'data-toggle' => 'dropdown',
    'aria-haspopup' => 'true',
    'aria-expanded' => 'false',
  );
  print $this->Html->link(
    '<em class="material-icons">settings</em>',
    'javascript:void(0);',
    $linkparams
  );

  // Sort the actions
  usort($vv_actions, function ($item1, $item2) {
    if ($item1['order'] == $item2['order']) return 0;
    return $item1['order'] < $item2['order'] ? -1 : 1;
  });
  ?>
  <ul id="action-list_<?php print  md5($vv_attr_mdl . $vv_attr_id); ?>"
      class="dropdown-menu">
    <?php foreach($vv_actions as $action): ?>
      <?php if(empty($action['onclick'])): ?>
        <?php $lightbox = (isset($action['lightbox']) && $action['lightbox']) ? " lightbox" : "";  ?>
        <li class="dropdown-item">
          <a class="spin ignore-invalid <?php print $lightbox; ?>" href="<?php print $action['url']; ?>">
            <?php if(!empty($action['icon'])): ?>
            <em class="material-icons" aria-hidden="true"><?php print $action['icon']; ?></em>
            <?php endif; ?>
            <?php print $action['label']; ?>
          </a>
        </li>
      <?php else: ?>
      <?php
        $dg_onclick = 'javascript:js_confirm_generic(\''
          . $action['onclick']['dg_bd_txt'] . '\',\''            // dialog body text
          . $action['onclick']['dg_url'] . '\',\''               // redirect URL
          . $action['onclick']['dg_conf_btn'] . '\',\''          // dialog confirm button
          . $action['onclick']['dg_cancel_btn'] . '\',\''        // dialog cancel button
          . $action['onclick']['dg_title'] . '\',[\''            // dialog title
          . $action['onclick']['db_bd_txt_repl_str']             // dialog body text replacement strings
          . '\']);';
      ?>
        <li class="dropdown-item">
          <a href="<?php print $action['url']; ?>" onclick="<?php print $dg_onclick; ?>">
            <?php if(!empty($action['icon'])): ?>
              <em class="material-icons" aria-hidden="true"><?php print $action['icon']; ?></em>
            <?php endif; ?>
            <?php print $action['label']; ?>
          </a>
        </li>
      <?php endif; ?>
    <?php endforeach;?>
  </ul>
</div>
