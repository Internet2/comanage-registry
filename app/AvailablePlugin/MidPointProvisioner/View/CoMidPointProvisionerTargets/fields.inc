<!--
/**
 * COmanage Registry CO MidPoint Provisioning Target Fields
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
 * @since         COmanage Registry v3.3.0
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
  <i class="material-icons">info</i>
  <?php print _txt('pl.midpointprovisioner.info'); ?>
</div>

<ul id="<?php print $this->action; ?>_co_mid_point_provisioner_target" class="fields form-list form-list-admin">
    <li>
        <div class="field-name">
            <div class="field-title">
              <?php print _txt('pl.midpointprovisioner.serverurl'); ?>
                <span class="required">*</span>
                <div class="field-desc"><?php print _txt('pl.midpointprovisioner.serverurl.desc'); ?></div>
            </div>
        </div>
        <div class="field-info">
          <?php print ($e ? $this->Form->input('serverurl') : filter_var($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['serverurl'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </div>
    </li>
    <li>
        <div class="field-name">
            <div class="field-title">
              <?php print _txt('pl.midpointprovisioner.username'); ?>
                <span class="required">*</span>
            </div>
        </div>
        <div class="field-info">
          <?php print ($e ? $this->Form->input('username') : filter_var($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['username'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </div>
    </li>
    <li>
        <div class="field-name">
            <div class="field-title">
              <?php print _txt('pl.midpointprovisioner.password'); ?>
                <span class="required">*</span>
            </div>
        </div>
        <div class="field-info">
          <?php print ($e ? $this->Form->input('password') : filter_var($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </div>
    </li>
    <li>
        <div class="field-name">
            <div class="field-title"><?php print _txt('pl.midpointprovisioner.user_name_identifier'); ?><span class="required">*</span></div>
            <div class="field-desc"><?php print _txt('pl.midpointprovisioner.user_name_identifier.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php
          $xval = "";

          if(isset($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['user_name_identifier'])) {
            $xval = $co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['user_name_identifier'];
          }

          if($e) {
            $args = array();
            $args['empty'] = '(choose one)';
            print $this->Form->select('user_name_identifier', $identifier_types, $args);
          } else {
            print filter_var($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['user_name_identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
          }
          ?>
        </div>
    </li>
    <li>
        <div class="field-name">
            <div class="field-title"><?php print _txt('pl.midpointprovisioner.ssl_allow_self_signed'); ?></div>
            <div class="field-desc"><?php print _txt('pl.midpointprovisioner.ssl_allow_self_signed.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php if ($e) print $this->Form->input('ssl_allow_self_signed', array('checked' => filter_var($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['ssl_allow_self_signed'],FILTER_VALIDATE_BOOLEAN), 'disabled' => false)); ?>
        </div>
    </li>
    <li>
        <div class="field-name">
            <div class="field-title"><?php print _txt('pl.midpointprovisioner.ssl_verify_host'); ?></div>
            <div class="field-desc"><?php print _txt('pl.midpointprovisioner.ssl_verify_host.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php if($e) print $this->Form->input('ssl_verify_host', array('checked' => filter_var($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['ssl_verify_host'],FILTER_VALIDATE_BOOLEAN), 'disabled' => false)); ?>
        </div>
    </li>
    <li>
        <div class="field-name">
            <div class="field-title"><?php print _txt('pl.midpointprovisioner.ssl_verify_peer'); ?></div>
            <div class="field-desc"><?php print _txt('pl.midpointprovisioner.ssl_verify_peer.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php if($e) print $this->Form->input('ssl_verify_peer', array('checked' => filter_var($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['ssl_verify_peer'],FILTER_VALIDATE_BOOLEAN), 'disabled' => false)); ?>
        </div>
    </li>
    <li>
        <div class="field-name">
            <div class="field-title"><?php print _txt('pl.midpointprovisioner.ssl_verify_peer_name'); ?></div>
            <div class="field-desc"><?php print _txt('pl.midpointprovisioner.ssl_verify_peer_name.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php if($e) print $this->Form->input('ssl_verify_peer_name', array('checked' => filter_var($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['ssl_verify_peer_name'],FILTER_VALIDATE_BOOLEAN), 'disabled' => false)); ?>
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