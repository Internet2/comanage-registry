<!--
/**
 * COmanage Registry CO Announcement Channel Fields
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
  $args['controller'] = 'co_announcement_channels';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_announcement_channels.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_announcement_channels.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->

  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    var readergroup = document.getElementById('CoAnnouncementChannelReaderCoGroupId').value;
    
    if(readergroup) {
      $("#CoAnnouncementChannelRegisterNotifications").closest("li").show('fade');
    } else {
      $("#CoAnnouncementChannelRegisterNotifications").closest("li").hide('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }
</script>
<ul id="<?php print $this->action; ?>_co_announcement_channels" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('name', _txt('fd.name')) : _txt('fd.name')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('name', array('class' => 'focusFirst'))
                   : filter_var($co_announcement_channels[0]['CoAnnouncementChannel']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_announcement_channels[0]['CoAnnouncementChannel']['status'])
                           ? $co_announcement_channels[0]['CoAnnouncementChannel']['status']
                           : SuspendableStatusEnum::Active);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.susp'],
                                    $attrs);

          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status', null, $co_announcement_channels[0]['CoAnnouncementChannel']['status']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('author_co_group_id', _txt('pl.announcementswidget.author')) : _txt('fd.svc.group')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.announcementswidget.author.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_announcement_channels[0]['CoAnnouncementChannel']['author_co_group_id'])
                           ? $co_announcement_channels[0]['CoAnnouncementChannel']['author_co_group_id']
                           : 0);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('author_co_group_id',
                                    $vv_co_groups,
                                    $attrs);

          if($this->Form->isFieldError('author_co_group_id')) {
            print $this->Form->error('author_co_group_id');
          }
        } else {
          if(!empty( $co_announcement_channels[0]['CoAnnouncementChannel']['author_co_group_id'])) {
            print filter_var($vv_co_groups[  $co_announcement_channels[0]['CoAnnouncementChannel']['author_co_group_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('reader_co_group_id', _txt('pl.announcementswidget.reader')) : _txt('fd.svc.group')); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.announcementswidget.reader.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_announcement_channels[0]['CoAnnouncementChannel']['reader_co_group_id'])
                           ? $co_announcement_channels[0]['CoAnnouncementChannel']['reader_co_group_id']
                           : 0);
        $attrs['empty'] = true;
        $attrs['onChange'] = 'fields_update_gadgets()';

        if($e) {
          print $this->Form->select('reader_co_group_id',
                                    $vv_co_groups,
                                    $attrs);

          if($this->Form->isFieldError('reader_co_group_id')) {
            print $this->Form->error('reader_co_group_id');
          }
        } else {
          if(!empty( $co_announcement_channels[0]['CoAnnouncementChannel']['reader_co_group_id'])) {
            print filter_var($vv_co_groups[  $co_announcement_channels[0]['CoAnnouncementChannel']['reader_co_group_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('register_notifications', _txt('pl.announcementswidget.notify')) : _txt('pl.announcementswidget.notify')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.announcementswidget.notify.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('register_notifications')
                   : ($co_announcement_channels[0]['CoAnnouncementChannel']['register_notifications'] ? _txt('fd.yes') : _txt('fd.no')));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('publish_html', _txt('pl.announcementswidget.publish_html')) : _txt('pl.announcementswidget.publish_html')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.announcementswidget.publish_html.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('publish_html')
                   : ($co_announcement_channels[0]['CoAnnouncementChannel']['publish_html'] ? _txt('fd.yes') : _txt('fd.no')));
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
