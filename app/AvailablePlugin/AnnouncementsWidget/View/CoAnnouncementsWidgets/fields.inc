<!--
/**
 * COmanage Registry CO Announcements Widgets Fields
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
    return false;
  
  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_dashboards';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_dashboards.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_dashboards';
  $args['action'] = 'edit';
  $args[] = $co_announcements_widgets[0]['CoDashboardWidget']['co_dashboard_id'];
  $this->Html->addCrumb(_txt('ct.co_dashboards.1'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_dashboard_widgets';
  $args['action'] = 'index';
  $args['codashboard'] = $co_announcements_widgets[0]['CoDashboardWidget']['co_dashboard_id'];
  $this->Html->addCrumb(_txt('ct.co_dashboard_widgets.1'), $args);
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(filter_var($co_announcements_widgets[0]['CoDashboardWidget']['description'],FILTER_SANITIZE_SPECIAL_CHARS)));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_dashboard_widget_id', array('default' => $vv_dwid)) . "\n";
?>
<ul id="<?php print $this->action; ?>_co_announcements_widget" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('co_announcement_channel_id', _txt('ct.co_announcement_channels.1')) : _txt('ct.co_announcement_channels.1')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_announcements_widgets[0]['CoAnnouncementsWidget']['co_announcement_channel_id'])
                           ? $co_announcements_widgets[0]['CoAnnouncementsWidget']['co_announcement_channel_id']
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
          if(!empty($co_announcements_widgets[0]['CoAnnouncementChannel']['name'])) {
            print filter_var($co_announcements_widgets[0]['CoAnnouncementChannel']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
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
