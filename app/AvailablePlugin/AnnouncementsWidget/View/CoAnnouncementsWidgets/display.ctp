<?php
/**
 * COmanage Registry Announcements Widget Display View
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
<ul class="widget-actions">
  <li>
    <em class="material-icons" aria-hidden="true">announcement</em>
    <?php
      print $this->Html->link(
        _txt('pl.announcementswidget.view_all'),
        array(
          'plugin'     => 'announcements_widget',
          'controller' => 'co_announcements',
          'action'     => 'index',
          'co'         => $cur_co['Co']['id'],
          'sort'       => 'CoAnonuncement.created',
          'direction'  => 'desc'
        )
      );
    ?>
  </li>
  <?php /* XXX Keep for enhancement (render "Add Announcement" link in widget)
  <li>
    <?php /* insert icons directly because this widget will be pulled in via ajax * / ? >
    <span class="ui-button-icon ui-icon ui-icon-circle-plus"></span>
    <span class="ui-button-icon-space"> </span>
    <?php
      if($permissions['add']) {
        print $this->Html->link(
          _txt('op.add-a', array(_txt('ct.co_announcements.1'))),
          array(
            'plugin'     => 'announcements_widget',
            'controller' => 'co_announcements',
            'action' => 'add',
            'co' => $cur_co['Co']['id']
          ),
          array('class' => 'addbutton')
        );
      }
    ?>
  </li>
  */ ?>
</ul>
<ul class="widget-announcements widget-list">
<?php if(empty($vv_widget_announcements)): ?>
  <li><?php print _txt('pl.announcementswidget.none'); ?></li>
<?php endif; ?>
<?php foreach($vv_widget_announcements as $a): ?>
  <li>
    <div class="announcement-title"><?php print $a['CoAnnouncement']['title']; ?></div>
    <?php if(!empty($a['PosterCoPerson']['PrimaryName']['id'])): ?>
      <div class="announcement-meta">
        <em class="announcement-poster"><?php print filter_var(generateCn($a['PosterCoPerson']['PrimaryName']), FILTER_SANITIZE_SPECIAL_CHARS); ?>,</em>
        <em class="announcement-created"><?php print  $this->Time->format('Y-n-d g:i a', $a['CoAnnouncement']['created']); ?></em>
      </div>
    <?php endif; // PrimaryName ?>
    <div class="announcement-body">
      <?php
        // Render HTML or not according to channel configuration
        if(isset($a['CoAnnouncementChannel']['publish_html']) && $a['CoAnnouncementChannel']['publish_html']) {
          print $a['CoAnnouncement']['body'];
        } else {
          // Also insert <br/> tags before newlines within the sanitized string
          $announcementBody = filter_var($a['CoAnnouncement']['body'], FILTER_SANITIZE_SPECIAL_CHARS);
          // the FILTER converts all newlines to &#10;, so simply convert them:
          $announcementBody = str_replace('&#10;', '<br />', $announcementBody);
          print $announcementBody;
        }
      ?>
      <?php  ?>
    </div>
  </li>
<?php endforeach; ?>
</ul>