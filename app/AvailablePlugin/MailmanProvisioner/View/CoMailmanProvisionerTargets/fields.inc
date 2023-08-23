<!--
/**
 * COmanage Registry Mailman3 Provisioning Target Fields
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
 * @since         COmanage Registry v3.1.0
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
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('pl.mailmanprovisioner.info'); ?>
  </div>
</div>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('pl.mailmanprovisioner.info2'); ?>
  </div>
</div>

  <ul id="<?php print $this->action; ?>_co_mailman_provisioner_target" class="fields form-list form-list-admin">
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.mailmanprovisioner.serverurl'); ?>
          <span class="required">*</span>
        <div class="field-desc"><?php print _txt('pl.mailmanprovisioner.serverurl.desc'); ?></div>
        </div>  
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('serverurl') : filter_var($co_mailman_provisioner_targets[0]['CoMailmanProvisionerTarget']['serverurl'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.mailmanprovisioner.adminuser'); ?>
          <span class="required">*</span>
        </div>  
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('adminuser') : filter_var($co_mailman_provisioner_targets[0]['CoMailmanProvisionerTarget']['adminuser'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.mailmanprovisioner.password'); ?>
          <span class="required">*</span>
        </div>  
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('password') : filter_var($co_mailman_provisioner_targets[0]['CoMailmanProvisionerTarget']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.mailmanprovisioner.domain'); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.mailmanprovisioner.domain.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('domain') : filter_var($co_mailman_provisioner_targets[0]['CoMailmanProvisionerTarget']['domain'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.mailmanprovisioner.pref_email'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.mailmanprovisioner.pref_email.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_mailman_provisioner_targets[0]['CoMailmanProvisionerTarget']['pref_email_type'])
                           ? $co_mailman_provisioner_targets[0]['CoMailmanProvisionerTarget']['pref_email_type']
                           : "");
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('pref_email_type',
                                    $vv_available_types,
                                    $attrs);

          if($this->Form->isFieldError('pref_email_type')) {
            print $this->Form->error('pref_email_type');
          }
        } else {
          print _txt('en.email_address.type', null, filter_var($co_mailman_provisioner_targets[0]['CoMailmanProvisionerTarget']['pref_email_type'],FILTER_SANITIZE_SPECIAL_CHARS));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.mailmanprovisioner.uem'); ?><span class="required">*</span></div>
      <div class="field-desc"><?php print _txt('pl.mailmanprovisioner.uem.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;

        $attrs = array();
        $attrs['value'] = (isset($co_mailman_provisioner_targets[0]['CoMailmanProvisionerTarget']['unmanaged_email_mode'])
          ? $co_mailman_provisioner_targets[0]['CoMailmanProvisionerTarget']['unmanaged_email_mode']
          : MailmanProvUnmanEmailEnum::Remove);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('unmanaged_email_mode',
            $cm_texts[ $cm_lang ]['en.mailmanprovisioner.unmanagedemail'],
            $attrs);

          if($this->Form->isFieldError('unmanaged_email_mode')) {
            print $this->Form->error('unmanaged_email_mode');
          }
        } else {
          print _txt('en.mailmanprovisioner.unmanagedemail', null, $co_mailman_provisioner_targets[0]['CoMailmanProvisionerTarget']['unmanaged_email_mode']);
        }
      ?>
    </div>
  </li>
  <?php if($e): ?>
    <li>
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
    <?php endif; ?>
  </ul>
