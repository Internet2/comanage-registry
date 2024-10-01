<?php
/**
 * COmanage Registry KDC Server Fields
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
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

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
  $args['controller'] = 'servers';
  $args['action'] = 'index';
  $args['co'] = $kdc_servers[0]['Server']['co_id'];
  $this->Html->addCrumb(_txt('ct.servers.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'servers';
  $args['action'] = 'edit';
  $args[] = $kdc_servers[0]['Server']['id'];
  $this->Html->addCrumb($kdc_servers[0]['Server']['description'], $args);
  
  $this->Html->addCrumb(_txt('op.config-a', array($kdc_servers[0]['Server']['description'])));
  
  print $this->Form->hidden('server_id', array('default' => $kdc_servers[0]['Server']['id'])) . "\n";
?>
<ul id="<?php print $this->action; ?>_kdc_server" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print $this->Form->label('hostname',_txt('fd.server.hostname')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('hostname', array('class' => 'focusFirst')) : filter_var($kdc_servers[0]['KdcServer']['hostname'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('admin_hostname',_txt('fd.server.kdc.admin_hostname')); ?>
      <div class="field-desc">
        <?php print _txt('fd.server.kdc.admin_hostname.desc'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('admin_hostname') : filter_var($kdc_servers[0]['KdcServer']['admin_hostname'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('admin_port',_txt('fd.server.kdc.admin_port')); ?>
      <div class="field-desc">
        <?php print _txt('fd.server.kdc.admin_port.desc'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('admin_port') : filter_var($kdc_servers[0]['KdcServer']['admin_port'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('realm',_txt('fd.server.kdc.realm')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('realm') : filter_var($kdc_servers[0]['KdcServer']['realm'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('principal',_txt('fd.server.kdc.principal')); ?><span class="required">*</span>
      <div class="field-desc">
        <?php print _txt('fd.server.kdc.principal.desc'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('principal') : filter_var($kdc_servers[0]['KdcServer']['principal'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('keytab',_txt('fd.server.kdc.keytab')); ?><span class="required">*</span>
      <div class="field-desc">
        <?php print _txt('fd.server.kdc.keytab.desc'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('keytab') : filter_var($kdc_servers[0]['KdcServer']['keytab'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
