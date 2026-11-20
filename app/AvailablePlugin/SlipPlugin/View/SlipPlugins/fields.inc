<!--
/**
 * COmanage Registry SLIP Plugin Fields
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
 * @since         COmanage Registry v4.7.0
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
  $args['controller'] = 'co_settings';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_settings.pl'), $args);

  $crumbTxt = _txt('ct.slip_plugins.1');
  $this->Html->addCrumb($crumbTxt);
  
  // Populate cross references
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";

  $start_url = $this->Html->url(
    array(
      'plugin' => 'slip_plugin',
      'controller' => 'slip_plugins',
      'action' => 'start',
      'co' => $cur_co['Co']['id']
    ),
    true
  );
?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('pl.slipplugin.start.url', array($start_url)); ?>
  </div>
</div>
<ul id="<?php print $this->action; ?>_slip_plugin" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.registrar_co_group_id'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($slip_plugins[0]['SlipPlugin']['registrar_co_group_id'])
                                 ? $slip_plugins[0]['SlipPlugin']['registrar_co_group_id']
                                 : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('registrar_co_group_id',
                                    $vv_available_groups,
                                    $attrs);

          if($this->Form->isFieldError('registrar_co_group_id')) {
            print $this->Form->error('registrar_co_group_id');
          }
        } else {
          print $vv_available_groups[ $slip_plugins[0]['SlipPlugin']['registrar_co_group_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.proofing_valid_co_group_id'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.slipplugin.proofing_valid_co_group_id.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($slip_plugins[0]['SlipPlugin']['proofing_valid_co_group_id'])
                                 ? $slip_plugins[0]['SlipPlugin']['proofing_valid_co_group_id']
                                 : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('proofing_valid_co_group_id',
                                    $vv_available_groups,
                                    $attrs);

          if($this->Form->isFieldError('proofing_valid_co_group_id')) {
            print $this->Form->error('proofing_valid_co_group_id');
          }
        } else {
          print $vv_available_groups[ $slip_plugins[0]['SlipPlugin']['proofing_valid_co_group_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.proofing_exempt_co_group_id'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($slip_plugins[0]['SlipPlugin']['proofing_exempt_co_group_id'])
                                 ? $slip_plugins[0]['SlipPlugin']['proofing_exempt_co_group_id']
                                 : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('proofing_exempt_co_group_id',
                                    $vv_available_groups,
                                    $attrs);

          if($this->Form->isFieldError('proofing_exempt_co_group_id')) {
            print $this->Form->error('proofing_exempt_co_group_id');
          }
        } else {
          print $vv_available_groups[ $slip_plugins[0]['SlipPlugin']['proofing_exempt_co_group_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.initiation_template_id'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.slipplugin.initiation_template_id.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($slip_plugins[0]['SlipPlugin']['initiation_template_id'])
                                 ? $slip_plugins[0]['SlipPlugin']['initiation_template_id']
                                 : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('initiation_template_id',
                                    $vv_available_templates,
                                    $attrs);

          if($this->Form->isFieldError('initiation_template_id')) {
            print $this->Form->error('initiation_template_id');
          }
        } else {
          print $vv_available_groups[ $slip_plugins[0]['SlipPlugin']['initiation_template_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.scheduling_template_id'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.slipplugin.scheduling_template_id.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($slip_plugins[0]['SlipPlugin']['scheduling_template_id'])
                                 ? $slip_plugins[0]['SlipPlugin']['scheduling_template_id']
                                 : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('scheduling_template_id',
                                    $vv_available_templates,
                                    $attrs);

          if($this->Form->isFieldError('scheduling_template_id')) {
            print $this->Form->error('scheduling_template_id');
          }
        } else {
          print $vv_available_groups[ $slip_plugins[0]['SlipPlugin']['scheduling_template_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.binding_template_id'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.slipplugin.binding_template_id.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($slip_plugins[0]['SlipPlugin']['binding_template_id'])
                                 ? $slip_plugins[0]['SlipPlugin']['binding_template_id']
                                 : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('binding_template_id',
                                    $vv_available_templates,
                                    $attrs);

          if($this->Form->isFieldError('binding_template_id')) {
            print $this->Form->error('binding_template_id');
          }
        } else {
          print $vv_available_groups[ $slip_plugins[0]['SlipPlugin']['binding_template_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.vouching_template_id'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.slipplugin.vouching_template_id.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($slip_plugins[0]['SlipPlugin']['vouching_template_id'])
                                 ? $slip_plugins[0]['SlipPlugin']['vouching_template_id']
                                 : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('vouching_template_id',
                                    $vv_available_templates,
                                    $attrs);

          if($this->Form->isFieldError('vouching_template_id')) {
            print $this->Form->error('vouching_template_id');
          }
        } else {
          print $vv_available_groups[ $slip_plugins[0]['SlipPlugin']['vouching_template_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.approval_template_id'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.slipplugin.approval_template_id.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($slip_plugins[0]['SlipPlugin']['approval_template_id'])
                                 ? $slip_plugins[0]['SlipPlugin']['approval_template_id']
                                 : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('approval_template_id',
                                    $vv_available_templates,
                                    $attrs);

          if($this->Form->isFieldError('approval_template_id')) {
            print $this->Form->error('approval_template_id');
          }
        } else {
          print $vv_available_groups[ $slip_plugins[0]['SlipPlugin']['approval_template_id'] ];
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
