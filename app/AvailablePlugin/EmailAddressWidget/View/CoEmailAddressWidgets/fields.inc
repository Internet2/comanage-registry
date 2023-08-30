<!--
/**
 * COmanage Registry CO Email Address Widgets Fields
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
 * @since         COmanage Registry v4.1.0
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
  $args[] = $co_email_address_widgets[0]['CoDashboardWidget']['co_dashboard_id'];
  $this->Html->addCrumb(_txt('ct.co_dashboards.1'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_dashboard_widgets';
  $args['action'] = 'index';
  $args['codashboard'] = $co_email_address_widgets[0]['CoDashboardWidget']['co_dashboard_id'];
  $this->Html->addCrumb(_txt('ct.co_dashboard_widgets.1'), $args);
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(filter_var($co_email_address_widgets[0]['CoDashboardWidget']['description'],FILTER_SANITIZE_SPECIAL_CHARS)));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_dashboard_widget_id', array('default' => $vv_dwid)) . "\n";
?>


<ul id="<?php print $this->action; ?>_co_url_widget" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('type', _txt('pl.emailaddresswidget.fd.type.default')) : _txt('pl.emailaddresswidget.fd.type.default')); ?>
      </div>
      <div class="field-desc">
        <?php print _txt('pl.emailaddresswidget.fd.type.default.desc'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
      $attrs['value'] = (isset($vv_config['CoEmailAddressWidget']['type']) ? $vv_config['CoEmailAddressWidget']['type'] : "");
      $attrs['empty'] = false;
  
      if($e) {
        print $this->Form->select('type',
          $vv_available_types,
          $attrs);
    
        if($this->Form->isFieldError('type')) {
          print $this->Form->error('type');
        }
      } else {
        print _txt('pl.emailaddresswidget.fd.type.default', null, filter_var($vv_config['type'],FILTER_SANITIZE_SPECIAL_CHARS));
      }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('max_allowed', _txt('pl.emailaddresswidget.fd.limit')) :_txt('pl.emailaddresswidget.fd.limit')); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.emailaddresswidget.fd.limit.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
        ? $this->Form->input('max_allowed')
        : filter_var($vv_config['CoEmailAddressWidget']['max_allowed'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('co_message_template_id', _txt('pl.emailaddresswidget.fd.message.template')) : _txt('pl.emailaddresswidget.fd.message.template')); ?>
      </div>
      <div class="field-desc">
        <p>
          <?php print _txt('pl.emailaddresswidget.fd.message.template.desc'); ?>
        </p>
        <p>
          <strong><?php print _txt('fd.subject');?></strong> 
          "<?php print _txt('pl.emailaddresswidget.email.subject');?>"
        </p>
        <p>
          <strong><?php print _txt('pl.emailaddresswidget.fd.body');?></strong>
          "<?php print _txt('pl.emailaddresswidget.email.body');?>"
        </p>
      </div>
    </div>
    <div class="field-info">
      <?php
      $attrs['value'] = isset($vv_config['CoEmailAddressWidget']['co_message_template_id'])
                        ? $vv_config['CoEmailAddressWidget']['co_message_template_id']
                        : "";
      $attrs['empty'] = true;

      if($e) {
        print $this->Form->select('co_message_template_id',
          $vv_message_templates,
          $attrs);
  
        if($this->Form->isFieldError('co_message_template_id')) {
          print $this->Form->error('co_message_template_id');
        }
      } else {
        print _txt('pl.emailaddresswidget.fd.message.template', null, filter_var($vv_config['co_message_template_id'],FILTER_SANITIZE_SPECIAL_CHARS));
      }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('verification_validity', _txt('pl.emailaddresswidget.fd.verification.validity')) :_txt('pl.emailaddresswidget.fd.verification.validity')); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.emailaddresswidget.fd.verification.validity.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
        ? $this->Form->input('verification_validity', array('default' => 10))
        : filter_var($vv_config['CoEmailAddressWidget']['verification_validity'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.emailaddresswidget.fd.allowreplace'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.emailaddresswidget.fd.allowreplace.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
        ? $this->Form->checkbox('allow_replace')
        : filter_var($vv_config['CoEmailAddressWidget']['allow_replace'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      <?php print ($e 
        ? $this->Form->label('allow_replace', _txt('pl.emailaddresswidget.fd.allowreplace')) 
        : ''); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.emailaddresswidget.fd.retainlast'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.emailaddresswidget.fd.retainlast.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
        ? $this->Form->checkbox('retain_last')
        : filter_var($vv_config['CoEmailAddressWidget']['retain_last'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      <?php print ($e 
        ? $this->Form->label('retain_last', _txt('pl.emailaddresswidget.fd.retainlast')) 
        : ''); ?>
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
