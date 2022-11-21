<!--
/**
 * COmanage Registry CO Recovery Widgets Fields
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
 * @link          https://www.internet2.edu/comanage COmanage Project
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
  $args[] = $co_recovery_widgets[0]['CoDashboardWidget']['co_dashboard_id'];
  $this->Html->addCrumb(_txt('ct.co_dashboards.1'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_dashboard_widgets';
  $args['action'] = 'index';
  $args['codashboard'] = $co_recovery_widgets[0]['CoDashboardWidget']['co_dashboard_id'];
  $this->Html->addCrumb(_txt('ct.co_dashboard_widgets.1'), $args);
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(filter_var($co_recovery_widgets[0]['CoDashboardWidget']['description'],FILTER_SANITIZE_SPECIAL_CHARS)));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_dashboard_widget_id', array('default' => $vv_dwid)) . "\n";
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->

  function fields_update_gadgets() {
    var lookup = document.getElementById('CoRecoveryWidgetIdentifierType').value;

    if(lookup != '') {
      document.getElementById('identifierTemplate').style.display = 'block';
    } else {
      document.getElementById('identifierTemplate').style.display = 'none';
    }

    var reset = document.getElementById('CoRecoveryWidgetAuthenticatorId').value;

    if(reset != '') {
      document.getElementById('resetTemplate').style.display = 'block';
      document.getElementById('resetValidity').style.display = 'block';
      document.getElementById('resetRedirect').style.display = 'block';
    } else {
      document.getElementById('resetTemplate').style.display = 'none';
      document.getElementById('resetValidity').style.display = 'none';
      document.getElementById('resetRedirect').style.display = 'none';
    }
  }

  function js_local_onload() {
    fields_update_gadgets();
  }
</script>

<ul id="<?php print $this->action; ?>_co_recovery_widget" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.recoverywidget.enable_confirmation_resend'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('enable_confirmation_resend'); ?>
    </div>
  </li>
  <li id="identifierTemplate">
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.recoverywidget.identifier_template'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (!empty($co_recovery_widgets[0]['CoRecoveryWidget']['identifier_template_id'])
                           ? $co_recovery_widgets[0]['CoRecoveryWidget']['identifier_template_id']
                           : 0);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('identifier_template_id',
                                    $vv_message_templates,
                                    $attrs);

          if($this->Form->isFieldError('identifier_template_id')) {
            print $this->Form->error('identifier_template_id');
          }
        } else {
          if(!empty($co_recovery_widgets[0]['CoRecoveryWidget']['identifier_template_id'])) {
            print filter_var($vv_message_templates[ $co_recovery_widgets[0]['CoRecoveryWidget']['identifier_template_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php
        if($e) {
          print $this->Form->label('authenticator_id', _txt('pl.recoverywidget.authenticator'));
        } else {
          print _txt('pl.recoverywidget.authenticator_id');
        }
      ?>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (!empty($co_recovery_widgets[0]['CoRecoveryWidget']['authenticator_id']) ? $co_recovery_widgets[0]['CoRecoveryWidget']['authenticator_id'] : "");
        $attrs['empty'] = true;
        $attrs['onchange'] = "fields_update_gadgets();";

        if($e) {
          print $this->Form->select('authenticator_id',
                                    $vv_available_authenticators,
                                    $attrs);

          if($this->Form->isFieldError('authenticator_id')) {
            print $this->Form->error('authenticator_id');
          }
        } else {
          print filter_var($vv_available_authenticators[ $co_recovery_widgets[0]['CoRecoveryWidget']['authenticator_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </div>
  </li>
  <li id="resetTemplate">
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.recoverywidget.authenticator_reset_template'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (!empty($co_recovery_widgets[0]['CoRecoveryWidget']['authenticator_reset_template_id'])
                           ? $co_recovery_widgets[0]['CoRecoveryWidget']['authenticator_reset_template_id']
                           : 0);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('authenticator_reset_template_id',
                                    $vv_message_templates,
                                    $attrs);

          if($this->Form->isFieldError('authenticator_reset_template_id')) {
            print $this->Form->error('authenticator_reset_template_id');
          }
        } else {
          if(!empty($co_recovery_widgets[0]['CoRecoveryWidget']['authenticator_reset_template_id'])) {
            print filter_var($vv_message_templates[ $co_recovery_widgets[0]['CoRecoveryWidget']['authenticator_reset_template_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li id="resetValidity">
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.recoverywidget.ssr.validity'); ?></div>
      <div class="field-desc"><?php print _txt('pl.recoverywidget.ssr.validity.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('authenticator_reset_validity', array('default' => 10)) : filter_var($co_recovery_widgets[0]['CoRecoveryWidget']['authenticator_reset_validity'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li id="resetRedirect">
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.recoverywidget.ssr.redirect'); ?></div>
      <div class="field-desc"><?php print _txt('pl.recoverywidget.ssr.redirect.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('authenticator_success_redirect') : filter_var($co_recovery_widgets[0]['CoRecoveryWidget']['authenticator_success_redirect'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
