<!--
/**
 * COmanage Registry CO Salesforce Provisioning Target Fields
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
 * @since         COmanage Registry v3.2.0
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
  $this->Html->addCrumb($co_salesforce_provisioner_targets[0]['CoProvisioningTarget']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('co_provisioning_target_id', array('default' => $vv_ptid)) . "\n";
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    var copersonobj = document.getElementById('CoSalesforceProvisionerTargetObjCoperson').checked;
    
    if(copersonobj) {
      $("#CoSalesforceProvisionerTargetPlatformIdType").closest("li").show('fade');
      $("#CoSalesforceProvisionerTargetAppIdType").closest("li").show('fade');
    } else {
      $("#CoSalesforceProvisionerTargetPlatformIdType").closest("li").hide('fade');
      $("#CoSalesforceProvisionerTargetAppIdType").closest("li").hide('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }
</script>
<?php if(empty($vv_servers)): ?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('er.server.none', array(_txt('en.server', null, ServerEnum::Oauth2Server))); ?>
  </div>
</div>
<?php else: // vv_servers ?>
<ul id="<?php print $this->action; ?>_co_salesforce_provisioner_target" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('server_id', _txt('fd.server')) : _txt('fd.server')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['server_id']) 
                           ? $co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['server_id']
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
          if(!empty($co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['server_id'])) {
            print filter_var($vv_servers[ $co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['server_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('instance_url',_txt('pl.salesforceprovisioner.instanceurl')); ?><br />
      <div class="field-desc"><?php print _txt('pl.salesforceprovisioner.instanceurl.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('instance_url', array('class' => 'focusFirst')) : filter_var($co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['instance_url'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.salesforceprovisioner.middlename'); ?></div>
      <div class="field-desc"><?php print _txt('pl.salesforceprovisioner.middlename.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php
        if($e) {
          print $this->Form->input('middle_name', array('label' => _txt('pl.salesforceprovisioner.middlename')));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('email_address_type',_txt('pl.salesforceprovisioner.email.type')); ?><br />
      <div class="field-desc"><?php print _txt('pl.salesforceprovisioner.email.type.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (!empty($co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['email_address_type'])
                           ? $co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['email_address_type']
                           : "");
        $attrs['empty'] = true;

        print $this->Form->select('email_address_type',
                                  $vv_email_addresses_types,
                                  $attrs);
        
        if($this->Form->isFieldError('email_address_type')) {
          print $this->Form->error('email_address_type');
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.salesforceprovisioner.coperson'); ?></div>
      <div class="field-desc"><?php print _txt('pl.salesforceprovisioner.coperson.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php
        if($e) {
          $attrs = array(
            'label'   => _txt('pl.salesforceprovisioner.coperson'),
            'onclick' => 'fields_update_gadgets();'
          );
          
          print $this->Form->input('obj_coperson', $attrs);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('platform_id_type',_txt('pl.salesforceprovisioner.coperson.platformid')); ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (!empty($co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['platform_id_type'])
                           ? $co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['platform_id_type']
                           : "");
        $attrs['empty'] = false;

        print $this->Form->select('platform_id_type',
                                  $vv_identifiers_types,
                                  $attrs);
        
        if($this->Form->isFieldError('platform_id_type')) {
          print $this->Form->error('platform_id_type');
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('app_id_type',_txt('pl.salesforceprovisioner.coperson.appid')); ?>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (!empty($co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['app_id_type'])
                           ? $co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['app_id_type']
                           : "");
        $attrs['empty'] = true;

        print $this->Form->select('app_id_type',
                                  $vv_identifiers_types,
                                  $attrs);
        
        if($this->Form->isFieldError('app_id_type')) {
          print $this->Form->error('app_id_type');
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('instance_url',_txt('pl.salesforceprovisioner.account')); ?><br />
      <div class="field-desc"><?php print _txt('pl.salesforceprovisioner.account.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('default_account') : filter_var($co_salesforce_provisioner_targets[0]['CoSalesforceProvisionerTarget']['default_account'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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