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
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('pl.githubprovisioner.register',
                             array($this->Html->url($vv_github_callback_url, true))); ?></strong>
  </p>
</div>
<?php elseif(empty($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['access_token'])): ?>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('pl.githubprovisioner.token.none'); ?></strong>
  </p>
</div>
<?php endif; // client_id/access_token ?>
<?php if(!$vv_github_type): ?>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('pl.githubprovisioner.type', array($this->Html->url($vv_extended_type_url))); ?></strong>
  </p>
</div>
<?php endif; // github_type ?>
<br />
<?php $l=1; ?>
<table id="<?php print $this->action; ?>_co_github_provisioner_target" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.githubprovisioner.callback_url'); ?>
      </td>
      <td>
        <input type="text" readonly="true" size="60" value="<?php print $this->Html->url($vv_github_callback_url, true); ?>" />
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.githubprovisioner.github_user'); ?></strong><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.githubprovisioner.github_user.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('github_user') : filter_var($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['github_user'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.githubprovisioner.github_org'); ?></strong><br />
        <font class="desc"><?php print _txt('pl.githubprovisioner.github_org.desc'); ?></font>
      </td>
      <td>
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
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.githubprovisioner.client_id'); ?></strong><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.githubprovisioner.client_id.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('client_id', array('type' => 'string'))
                     : filter_var($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['client_id'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.githubprovisioner.client_secret'); ?></strong><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.githubprovisioner.client_secret.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('client_secret')
                     : (!empty($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['client_secret'])
                        ? "*"
                        : "")); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.githubprovisioner.provision_group_members'); ?></strong><br />
        <font class="desc"><?php print _txt('pl.githubprovisioner.provision_group_members.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('provision_group_members', array('default' => true,
                                                                           'onClick' => 'fields_update_gadgets()'))
                     : ($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['provision_group_members']
                        ? _txt('fd.yes')
                        : _txt('fd.no'))); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.githubprovisioner.remove_unknown_members'); ?></strong><br />
        <font class="desc"><?php print _txt('pl.githubprovisioner.remove_unknown_members.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('remove_unknown_members')
                     : ($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['remove_unknown_members']
                        ? _txt('fd.yes')
                        : _txt('fd.no'))); ?>
      </td>
    </tr>
  <!-- disabled currently, see CO-944
    <tr class="line<?php false && print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php false && print _txt('pl.githubprovisioner.provision_ssh_keys'); ?></strong><br />
        <font class="desc"><?php false && print _txt('pl.githubprovisioner.provision_ssh_keys.desc'); ?></font>
      </td>
      <td>
        <?php false && print ($e
                     ? $this->Form->input('provision_ssh_keys')
                     : ($co_github_provisioner_targets[0]['CoGithubProvisionerTarget']['provision_ssh_keys']
                        ? _txt('fd.yes')
                        : _txt('fd.no'))); ?>
      </td>
    </tr>
  -->
    <tr>
      <td>
        <i><font class="required"><?php print _txt('fd.req'); ?></font></i><br />
      </td>
      <td><?php
          if($e)
            print $this->Form->submit($submit_label);
        ?>
        <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
        <i><?php print _txt('pl.githubprovisioner.oauth'); ?></i>

      </td>
    </tr>
  </tbody>
</table>
