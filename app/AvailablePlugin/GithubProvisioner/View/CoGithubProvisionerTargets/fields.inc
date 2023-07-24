<!--
/**
 * COmanage Registry CO GitHub Provisioning Target Fields
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
 * @since         COmanage Registry v0.9.1
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
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('co_provisioning_target_id', array('default' => $vv_ptid)) . "\n";
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    if(document.getElementById('CoGithubProvisionerTargetProvisionGroupMembers').checked) {
      $("#CoGithubProvisionerTargetRemoveUnknownMembers").closest("tr").show('fade');
    } else {
      $("#CoGithubProvisionerTargetRemoveUnknownMembers").closest("tr").hide('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }
</script>
<?php if(empty($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['client_id'])): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <div class="co-info-topbox-text">
      <?php print _txt('pl.githubprovisioner.register',
        array($this->Html->url($vv_github_callback_url, true))); ?>
    </div>
  </div>
<?php elseif(empty($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['access_token'])): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <div class="co-info-topbox-text">
      <?php print _txt('pl.githubprovisioner.token.none'); ?>
    </div>
  </div>
<?php endif; // client_id/access_token ?>
<?php if(!$vv_github_type): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <div class="co-info-topbox-text">
      <?php print _txt('pl.githubprovisioner.type', array($this->Html->url($vv_extended_type_url))); ?>
    </div>
  </div>
<?php endif; // github_type ?>

<ul id="<?php print $this->action; ?>_co_github_provisioner_target" class="fields form-list form-list-admin">
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.githubprovisioner.callback_url'); ?>
        </div>  
      </div>
      <div class="field-info">
        <input type="text" readonly="true" size="60" value="<?php print $this->Html->url($vv_github_callback_url, true); ?>" />
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.githubprovisioner.github_user'); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.githubprovisioner.github_user.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('github_user') : filter_var($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['github_user'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.githubprovisioner.github_org'); ?>
        </div>
        <div class="field-desc"><?php print _txt('pl.githubprovisioner.github_org.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php
          if(!empty($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['github_org'])) {
            print filter_var($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['github_org'],FILTER_SANITIZE_SPECIAL_CHARS);
            
            print $this->Html->link(_txt('op.edit'),
                                    array(
                                      'plugin'     => 'github_provisioner',
                                      'controller' => 'co_github_provisioner_targets',
                                      'action'     => 'select',
                                      $co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['id']
                                    ),
                                    array('class' => 'editbutton'));
          }
        ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.githubprovisioner.client_id'); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.githubprovisioner.client_id.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e
                     ? $this->Form->input('client_id', array('type' => 'string'))
          : filter_var($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['client_id'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.githubprovisioner.client_secret'); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.githubprovisioner.client_secret.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e
                     ? $this->Form->input('client_secret')
                     : (!empty($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['client_secret'])
                        ? "*"
                        : "")); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.githubprovisioner.provision_group_members'); ?>
        </div>
        <div class="field-desc"><?php print _txt('pl.githubprovisioner.provision_group_members.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e
                     ? $this->Form->input('provision_group_members', array('default' => true,
                                                                           'onClick' => 'fields_update_gadgets()'))
                     : ($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['provision_group_members']
                        ? _txt('fd.yes')
                        : _txt('fd.no'))); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.githubprovisioner.remove_unknown_members'); ?>
        </div>
        <div class="field-desc"><?php print _txt('pl.githubprovisioner.remove_unknown_members.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e
                     ? $this->Form->input('remove_unknown_members')
                     : ($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['remove_unknown_members']
                        ? _txt('fd.yes')
                        : _txt('fd.no'))); ?>
      </div>
    </li>
  <!-- disabled currently, see CO-944
    <tr class="line<?php /* false && print ($l % 2); $l++; */ ?>">
      <div class="field-name">
        <div class="field-title"><?php false && print _txt('pl.githubprovisioner.provision_ssh_keys'); ?></div>
        <div class="field-desc"><?php false && print _txt('pl.githubprovisioner.provision_ssh_keys.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php false && print ($e
                     ? $this->Form->input('provision_ssh_keys')
                     : ($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['provision_ssh_keys']
                        ? _txt('fd.yes')
                        : _txt('fd.no'))); ?>
      </div>
    </li>
  -->
  <?php if($e): ?>
    <li>
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
        <div class="field-desc">
          <span class="ui-icon ui-icon-info"></span>
          <em><?php print _txt('pl.githubprovisioner.oauth'); ?></em>
        </div>
      </div>
    </li>
  <?php endif; ?>
</ul>
