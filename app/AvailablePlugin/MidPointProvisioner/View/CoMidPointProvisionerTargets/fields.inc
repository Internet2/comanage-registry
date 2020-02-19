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

<?php if (empty($vv_servers)): ?>
<div class="co-info-topbox"><i class="material-icons">info</i>
  <?php print _txt('er.server.none', array(_txt('en.server', null, ServerEnum::HttpServer))); ?>
</div>
<?php else: // vv_servers ?>
<ul id="<?php print $this->action; ?>_co_mid_point_provisioner_target" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('server_id', _txt('fd.server')) : _txt('fd.server')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['server_id'])
          ? $co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['server_id']
          : null);
        // Since required=true, this will render blank by default but not allow save until a server is selected
        $attrs['empty'] = true;

        if ($e) {
          print $this->Form->select('server_id',
            $vv_servers,
            $attrs);

          if ($this->Form->isFieldError('server_id')) {
            print $this->Form->error('server_id');
          }
        } else {
          if (!empty($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['server_id'])) {
            print filter_var($vv_servers[$co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['server_id']], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
        ?>
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

        if (isset($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['user_name_identifier'])) {
          $xval = $co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['user_name_identifier'];
        }

        if ($e) {
          $args = array();
          $args['empty'] = '(choose one)';
          print $this->Form->select('user_name_identifier', $identifier_types, $args);
        } else {
          print filter_var($co_mid_point_provisioner_targets[0]['CoMidPointProvisionerTarget']['user_name_identifier'], FILTER_SANITIZE_SPECIAL_CHARS);
        }
        ?>
    </div>
  </li>
  <?php if ($e): ?>
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
<?php endif; // vv_servers ?>