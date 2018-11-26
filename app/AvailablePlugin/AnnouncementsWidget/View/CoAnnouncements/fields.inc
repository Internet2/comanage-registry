<!--
/**
 * COmanage Registry CO Announcement Fields
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
 * @package       registry-plugin
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case

  if(!$e && !$permissions['view'])
    return(false);
    
  // Add breadcrumbs
  
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = 'announcements_widget';
  $args['controller'] = 'co_announcements';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_announcements.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_announcements.1')));
  $this->Html->addCrumb($crumbTxt);
  
  // For now, the default poster is the current CO Person, though this may change at some point.
  // (Note the complication for making this a SELECT is that we need to know all potential authors
  // in accordance with the currently selected channel, which could be changed.)
  print $this->Form->hidden('poster_co_person_id', array('default' => $this->Session->read('Auth.User.co_person_id'))) . "\n";
?>
<ul id="<?php print $this->action; ?>_co_announcements" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('co_announcement_channel_id', _txt('ct.co_announcement_channels.1')) : _txt('ct.co_announcement_channels.1')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_announcements[0]['CoAnnouncement']['co_announcement_channel_id'])
                           ? $co_announcements[0]['CoAnnouncement']['co_announcement_channel_id']
                           : 0);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('co_announcement_channel_id',
                                    $vv_co_announcement_channels,
                                    $attrs);

          if($this->Form->isFieldError('co_announcement_channel_id')) {
            print $this->Form->error('co_announcement_channel_id');
          }
        } else {
          if(!empty($co_announcements[0]['CoAnnouncementChannel']['name'])) {
            print filter_var($co_announcements[0]['CoAnnouncementChannel']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('title', _txt('fd.title')) : _txt('fd.title')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('title', array('class' => 'focusFirst'))
                   : filter_var($co_announcements[0]['CoAnnouncement']['title'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if(!empty($co_announcements[0]['PosterCoPerson']['PrimaryName'])): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('poster_co_person_id', _txt('pl.announcementswidget.postedby')) : _txt('pl.announcementswidget.postedby')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print filter_var(generateCn($co_announcements[0]['PosterCoPerson']['PrimaryName']),FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
  <?php endif; // PosterCoPerson ?>
  <li class="field-stack">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.announcementswidget.body'); ?>
        <span class="required">*</span>
      </div>
      <?php if($this->action != 'view'): ?>
      <span class="descr"><?php print _txt('fd.mt.body.desc'); ?></span>
      <?php endif; // view ?>
    </div>
    <div class="field-info">
      <?php
        // Allow HTML in view if the channel permits it
        
        $filter = !(isset($co_announcements[0]['CoAnnouncementChannel']['publish_html'])
                   && $co_announcements[0]['CoAnnouncementChannel']['publish_html']);
        
        print ($e
               ? $this->Form->input('body')
               : ($filter
                 ? filter_var($co_announcements[0]['CoAnnouncement']['body'], FILTER_SANITIZE_SPECIAL_CHARS)
                 : $co_announcements[0]['CoAnnouncement']['body'])); ?>
    </div>
  </li>
  <li class="modelbox-data">
    <div class="field-name">
      <?php
        if($e) {
          print $this->Form->label('valid_from', _txt('fd.valid_from.tz', array($vv_tz)));
        } else {
          print _txt('fd.valid_from');
        }
      ?>
      <?php if($this->action != 'view'): ?>
      <div class="field-desc">
        <?php print _txt('fd.valid_from.desc'); ?>
      </div>
      <?php endif; // view ?>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $args = array(
            'class' => 'datepicker-f'
          );

          if(isset($co_announcements[0]['CoAnnouncement']['valid_from'])
            && $co_announcements[0]['CoAnnouncement']['valid_from'] > 0) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($co_announcements[0]['CoAnnouncement']['valid_from'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $co_announcements[0]['CoAnnouncement']['valid_from'];
            }
          }

          print $this->Form->text('valid_from', $args);
        } else {
          if(isset($co_announcements[0]['CoAnnouncement']['valid_from'])
            && $co_announcements[0]['CoAnnouncement']['valid_from'] > 0) {
            print $this->Time->format($co_announcements[0]['CoAnnouncement']['valid_from'], "%c $vv_tz", false, $vv_tz);
          }
        }
      ?>
    </div>
  </li>
  <li class="modelbox-data">
    <div class="field-name">
      <?php
        if($e) {
          print $this->Form->label('valid_through', _txt('fd.valid_through.tz', array($vv_tz)));
        } else {
          print _txt('fd.valid_through');
        }
      ?>
      <?php if($this->action != 'view'): ?>
      <div class="field-desc">
        <?php print _txt('fd.valid_through.desc'); ?>
      </div>
      <?php endif; // view ?>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $args = array(
            'class' => 'datepicker-u'
          );

          if(isset($co_announcements[0]['CoAnnouncement']['valid_through'])
            && $co_announcements[0]['CoAnnouncement']['valid_through'] > 0) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($co_announcements[0]['CoAnnouncement']['valid_through'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $co_announcements[0]['CoAnnouncement']['valid_through'];
            }
          }

          print $this->Form->text('valid_through', $args);
        } else {
          if(isset($co_announcements[0]['CoAnnouncement']['valid_through'])
            && $co_announcements[0]['CoAnnouncement']['valid_through'] > 0) {
            print $this->Time->format($co_announcements[0]['CoAnnouncement']['valid_through'], "%c $vv_tz", false, $vv_tz);
          }
        }
      ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
<?php
  print $this->element("changelog");
