<!--
/**
 * COmanage Registry CO Jira Provisioning Target Fields
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
 * @since         COmanage Registry v3.3.2
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
  $args['controller'] = 'co_provisioning_targets';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_provisioning_targets.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_provisioning_targets';
  $args['action'] = 'edit';
  $args[] = $vv_ptid;
  $this->Html->addCrumb($co_jira_provisioner_targets[0]['CoProvisioningTarget']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('co_provisioning_target_id', array('default' => $vv_ptid)) . "\n";
?>
<?php if(empty($vv_servers)): ?>
<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php print _txt('er.server.none', array(_txt('en.server', null, ServerEnum::HttpServer))); ?>
</div>
<?php else: // vv_servers ?>
<ul id="<?php print $this->action; ?>_co_jira_provisioner_target" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('server_id', _txt('fd.server')) : _txt('fd.server')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['server_id']) 
                           ? $co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['server_id']
                           : null);
        // Since required=true, this will render blank by default but not allow save until a server is selected
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('server_id',
                                    $vv_servers,
                                    $attrs);

          if($this->Form->isFieldError('server_id')) {
            print $this->Form->error('server_id');
          }
        } else {
          if(!empty($co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['server_id'])) {
            print filter_var($vv_servers[ $co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['server_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.jiraprovisioner.pref_username'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.jiraprovisioner.pref_username.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['username_type'])
                           ? $co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['username_type']
                           : "");
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('username_type',
                                    $vv_identifiers_types,
                                    $attrs);

          if($this->Form->isFieldError('username_type')) {
            print $this->Form->error('username_type');
          }
        } else {
          print _txt('en.identifier.type', null, filter_var($co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['username_type'],FILTER_SANITIZE_SPECIAL_CHARS));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.jiraprovisioner.query_by_username'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.jiraprovisioner.query_by_username.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        if(!$e) {
          $attrs['disabled'] = true;
        }
        print $this->Form->input('query_by_username', $attrs);
        if($this->Form->isFieldError('query_by_username')) {
          print $this->Form->error('query_by_username');
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.jiraprovisioner.provisioning_group'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.jiraprovisioner.provisioning_group.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['provision_co_group_id'])
                           ? $co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['provision_co_group_id']
                           : "");
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('provision_co_group_id',
                                    $vv_available_groups,
                                    $attrs);

          if($this->Form->isFieldError('provision_co_group_id')) {
            print $this->Form->error('provision_co_group_id');
          }
        } else {
          print $vv_available_groups[$co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['provision_co_group_id']];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.jiraprovisioner.deactivate'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.jiraprovisioner.deactivate.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        if(!$e) {
          $attrs['disabled'] = true;
        }
        print $this->Form->input('deactivate', $attrs);
        if($this->Form->isFieldError('deactivate')) {
          print $this->Form->error('deactivate');
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.jiraprovisioner.group_type'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.jiraprovisioner.group_type.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['group_type'])
                           ? $co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['group_type']
                           : "");
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('group_type',
                                    $vv_identifiers_types,
                                    $attrs);

          if($this->Form->isFieldError('group_type')) {
            print $this->Form->error('group_type');
          }
        } else {
          print _txt('en.identifier.type', null, filter_var($co_jira_provisioner_targets[0]['CoJiraProvisionerTarget']['group_type'],FILTER_SANITIZE_SPECIAL_CHARS));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.jiraprovisioner.group_name'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.jiraprovisioner.group_name.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        if(!$e) {
          $attrs['disabled'] = true;
        }
        print $this->Form->input('group_name', $attrs);
        if($this->Form->isFieldError('group_name')) {
          print $this->Form->error('group_name');
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.jiraprovisioner.remove_unknown'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.jiraprovisioner.remove_unknown.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        if(!$e) {
          $attrs['disabled'] = true;
        }
        print $this->Form->input('remove_unknown', $attrs);
        if($this->Form->isFieldError('remove_unknown')) {
          print $this->Form->error('remove_unknown');
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
<?php endif; // vv_servers
