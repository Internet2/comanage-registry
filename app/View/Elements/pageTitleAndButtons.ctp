<?php
/*
 * COmanage Registry Page Title and In-Page Navigation
 * Displayed below all pages
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
 * @since         COmanage Registry v?
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<div class="titleNavContainer">
  <div class="pageTitle">
    <h1>
      <?php
      print filter_var($title,FILTER_SANITIZE_SPECIAL_CHARS);
      ?>
    </h1>
    <?php if(isset($vv_title_status_bg)
             && $vv_title_status_bg
             && !$co_people[0]['CoPerson']['deleted']                       // Deleted
             && is_null($co_people[0]['CoPerson']['co_person_id']) ):       // Archived
      ?>
    <div class="status">
      <?php
      list($status, $badgeColor, $badgeOrder) = $this->Badge->calculateStatusNBadge($co_people[0]['CoPerson'], $vv_tz);
      $outline = ($status === 'Active') ? true : false;
      $statusBadge = ' ' .  $this->Badge->badgeIt(
          $status,
          $badgeColor,
          false,
          $outline,
          $this->Badge->getBadgeIcon('Edit'),
          'inline-edit'
        );

      $e = false;
      $es = false;

      if(($this->action == "invite" && $permissions['invite'])
        || ($this->action == "canvas" && $permissions['canvas']))
        $e = true;

      if($this->action == "canvas" && $permissions['editself'])
        $es = true;
      if($e && $permissions['edit']
        && !$permissions['editself']) {
        $linkparams = array(
          'class' => 'status-edit-link',
          'escape' => false,
          'onclick' => 'inline_edit(true);',
        );
        // Tag link
        print $this->Html->link(
          $statusBadge,
          'javascript:void(0);',
          $linkparams
        );
        $field_args = array(
          'field' => 'status',
          'label' => _txt('fd.status'),
          'type'  => 'select',
          'empty' => true,
        );
        print $this->element('inlineFieldEdit', $field_args);
      } else {
        print $statusBadge;
      }
      ?>
    </div>
    <?php endif; ?>
  </div>

  <?php if(!empty($topLinks)): ?>
    <ul id="topLinks">
      <?php foreach ($topLinks as $t): ?>
        <li><?php print $t?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif ?>
</div>