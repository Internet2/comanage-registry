<?php
/**
 * COmanage Registry CO Dashboard Widgets Fields
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
  $args['plugin'] = null;
  $args['controller'] = 'co_dashboards';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_dashboards.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_dashboards';
  $args['action'] = 'edit';
  $args[] = $vv_db_id;
  $this->Html->addCrumb($vv_db_name, $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_dashboard_widgets';
  $args['action'] = 'index';
  $args['codashboard'] = $vv_db_id;
  $this->Html->addCrumb(_txt('ct.co_dashboard_widgets.pl'), $args);

  $this->Html->addCrumb(_txt('op.'.$this->action));
  
  if($this->action != 'view') {
    // Populate cross references
    print $this->Form->hidden('co_dashboard_id', array('default' => $vv_db_id)) . "\n";
  }
  
  $l = 1;
?>
<ul id="<?php print $this->action; ?>_co_dashboard_widgets" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('description')
                   : filter_var($co_dashboard_widgets[0]['CoDashboardWidget']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.plugin'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.plugin.warn', array(_txt('ct.co_dashboard_widgets.1'))); ?></div>
    </div>
    <div class="field-info">
      <?php
        switch($this->action) {
          case 'add':
            print $this->Form->input('plugin');
            break;
          case 'edit':
            print $this->Form->input('plugin', array('disabled' => true));
            // We need to re-include the value as hidden since it can't be changed (but is required by the model)
            print $this->Form->hidden('plugin',
                                      array('default' => filter_var($co_dashboard_widgets[0]['CoDashboardWidget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
            break;
          default:
            print filter_var($co_dashboard_widgets[0]['CoDashboardWidget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            break;
        }

        if($this->action == 'edit') {
          // And render a link to the plugin target

          $plugin = filter_var($co_dashboard_widgets[0]['CoDashboardWidget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
          $pl = Inflector::underscore($plugin);
          $plc = "co_" . Inflector::tableize($plugin);
          $plm = "Co" . $plugin;

          print $this->Html->link(_txt('op.config'),
                                  array(
                                    'plugin' => $pl,
                                    'controller' => $plc,
                                    'action' => 'edit',
                                    $co_dashboard_widgets[0][$plm]['id']
                                  ),
                                  array('class' => 'configurebutton'));
        }
      ?>
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
        $attrs['value'] = (isset($co_dashboard_widgets[0]['CoDashboardWidget']['status'])
                           ? $co_dashboard_widgets[0]['CoDashboardWidget']['status']
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
          print _txt('en.status', null, $co_dashboard_widgets[0]['CoDashboardWidget']['status']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('ordr', _txt('fd.order')) : _txt('fd.order')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.order.es.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('ordr')
                   : filter_var($co_dashboard_widgets[0]['CoDashboardWidget']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
