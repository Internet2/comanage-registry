<?php
/**
 * COmanage Registry CO Grouper Provisioning Target Fields
 *
 * Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */


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
  
  function legacy_update_gadgets() {
    // Hide and show accordingly. 
    
    var legacy = document.getElementById('CoGrouperProvisionerTargetLegacyComanageSubject').checked;
    
    if(legacy) {
      $("#CoGrouperProvisionerTargetSubjectIdentifier").closest("li").hide('fade');
      $("#CoGrouperProvisionerTargetSubjectIdentifier").removeAttr("required");
      $("#CoGrouperProvisionerTargetSubjectView").closest("li").show('fade');
      $("#CoGrouperProvisionerTargetSubjectView").attr("required", true);

    } else {
      $("#CoGrouperProvisionerTargetSubjectIdentifier").closest("li").show('fade');
      $("#CoGrouperProvisionerTargetSubjectIdentifier").attr("required", true);
      $("#CoGrouperProvisionerTargetSubjectView").closest("li").hide('fade');
      $("#CoGrouperProvisionerTargetSubjectView").removeAttr("required");
    }
  }

  function js_local_onload() {
    legacy_update_gadgets();
  }
</script>
<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php print _txt('pl.grouperprovisioner.info'); ?>
</div>
<ul id="<?php print $this->action; ?>_co_grouper_provisioner_target" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.grouperprovisioner.serverurl'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.grouperprovisioner.serverurl.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('serverurl', array('size' => 50)) : Sanitize::html($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['serverurl'])); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.grouperprovisioner.contextpath'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.grouperprovisioner.contextpath.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('contextpath', array('size' => 20)) : Sanitize::html($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['contextpath'])); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.grouperprovisioner.login'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.grouperprovisioner.login.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('login', array('size' => 24)) : Sanitize::html($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['login'])); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.grouperprovisioner.password'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.grouperprovisioner.password.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('password') : Sanitize::html($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['password'])); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.grouperprovisioner.stem'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.grouperprovisioner.stem.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('stem', array('size' => 50)) : Sanitize::html($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['stem'])); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.grouperprovisioner.legacy'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.grouperprovisioner.legacy.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php 
        $args = array();
        $args['onchange'] = "legacy_update_gadgets();";
        if ($e) { 
          print $this->Form->input('legacy_comanage_subject', $args);
        } else {
          print Sanitize::html($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['legacy_comanage_subject']); 
        }
      ?>
    </div>
  </li>
  <li style="display:none">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.grouperprovisioner.subjectview'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.grouperprovisioner.subjectview.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php 
      if ($e) {
        $args = array();
        $args['size'] = 30;
        if (!isset($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['subject_view'])) {
          $args['default'] = $viewDefault;  
        }
        print $this->Form->input('subject_view', $args);
      } else {
        print Sanitize::html($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['subject_view']); 
      }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.grouperprovisioner.subjectidentifier'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.grouperprovisioner.subjectidentifier.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php 
        if($e) {
          $args = array();
          $args['empty'] = '(choose one)';
          print $this->Form->select('subject_identifier', $identifier_types, $args);
        } else {
          print Sanitize::html($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['subject_identifier']); 
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.grouperprovisioner.loginidentifier'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.grouperprovisioner.loginidentifier.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php 
        if($e) {
          $args = array();
          $args['empty'] = '(choose one)';
          print $this->Form->select('login_identifier', $identifier_types, $args);
        } else {
          print Sanitize::html($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['login_identifier']); 
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.grouperprovisioner.emailidentifier'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.grouperprovisioner.emailidentifier.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php 
        if($e) {
          $args = array();
          $args['empty'] = '(choose one)';
          print $this->Form->select('email_identifier', $email_address_types, $args);
        } else {
          print Sanitize::html($co_grouper_provisioner_targets[0]['CoGrouperProvisionerTarget']['email_identifier']); 
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
