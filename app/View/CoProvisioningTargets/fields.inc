<?php
/**
 * COmanage Registry CO Provisioning Target Fields
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
 * @since         COmanage Registry v0.8
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
  $args['controller'] = 'co_provisioning_targets';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_provisioning_targets.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_provisioning_targets.1')));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    
    var mode = document.getElementById('CoProvisioningTargetStatus').value;
    
    if(mode == '<?php print ProvisionerModeEnum::QueueMode; ?>'
       || mode == '<?php print ProvisionerModeEnum::QueueOnErrorMode; ?>') {
      $("#requeuediv").show('fade');
    } else {
      $("#requeuediv").hide('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }
</script>
<?php if(empty($plugins)): ?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <?php print _txt('er.plugin.none', array(_txt('ct.co_provisioning_targets.pl'))); ?>
</div>
<?php else: /* plugins */ ?>
<ul id="<?php print $this->action; ?>_co_provisioning_target" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description') : filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e && $this->action == 'add' ? $this->Form->label('plugin', _txt('fd.plugin')) : _txt('fd.plugin')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.plugin.warn', array(_txt('ct.co_provisioning_targets.1'))); ?></div>
    </div>
    <div class="field-info">
      <span class="field-info-prefix">
      <?php
        switch($this->action) {
          case 'add':
            print $this->Form->input('plugin');
            break;
          case 'edit':
            print filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            // Send the value as hidden since it can't be changed (but is required by the model)
            print $this->Form->hidden('plugin', array('default' => filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
            break;
          default:
            print filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            break;
        }
      ?>
      </span>

      <?php
        
        if($this->action == 'edit') {
          // And render a link to the plugin target

          $pl = Inflector::underscore(filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS));
          $plmodel = "Co" . filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS) . "Target";

          print ' ' . $this->Html->link(_txt('op.config'),
                                             array(
                                               'plugin' => $pl,
                                               'controller' => 'co_' . $pl . '_targets',
                                               'action' => 'edit',
                                               $co_provisioning_targets[0][$plmodel]['id'],
                                               'co' => $cur_co['Co']['id']
                                             ),
                                             array('class' => 'configurebutton'));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
      </div>
      <div class="field-desc"><?php print _txt('en.status.prov.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        
        $attrs = array();
        $attrs['value'] = (isset($co_provisioning_targets) ? $co_provisioning_targets[0]['CoProvisioningTarget']['status'] : ProvisionerModeEnum::AutomaticMode);
        $attrs['onChange'] = 'fields_update_gadgets();';
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.prov'],
                                    $attrs);
          
          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status', null, $co_provisioning_targets[0]['CoProvisioningTarget']['status']);
        }
      ?>
    </div>
    <div id="requeuediv" style="display:none">
      <ul class="field-children">
        <li>
          <div class="field-name">
            <div class="field-title">
              <?php print ($e ? $this->Form->label('requeue_interval', _txt('fd.job.retry_interval')) : _txt('fd.job.retry_interval')); ?>
            </div>
            <div class="field-desc"><?php print _txt('fd.prov.retry_interval.desc'); ?></div>
          </div>
          <div class="field-info">
            <?php print ($e
                         ? $this->Form->input('retry_interval')
                         : filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['retry_interval'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
          </div>
        </li>
      </ul>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('provisioning_co_group_id', _txt('fd.prov.group')) : _txt('fd.prov.group')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.prov.group.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_provisioning_targets[0]['CoProvisioningTarget']['provision_co_group_id'])
                           ? $co_provisioning_targets[0]['CoProvisioningTarget']['provision_co_group_id']
                           : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('provision_co_group_id',
                                    $vv_available_groups,
                                    $attrs);

          if($this->Form->isFieldError('provision_co_group_id')) {
            print $this->Form->error('provision_co_group_id');
          }
        } else {
          print $vv_available_groups[ $co_provisioning_targets[0]['CoProvisioningTarget']['provision_co_group_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('skip_org_identity_source_id', _txt('fd.prov.skipois')) : _txt('fd.prov.skipois')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.prov.skipois.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_provisioning_targets[0]['CoProvisioningTarget']['skip_org_identity_source_id'])
                           ? $co_provisioning_targets[0]['CoProvisioningTarget']['skip_org_identity_source_id']
                           : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('skip_org_identity_source_id',
                                    $vv_org_identity_sources,
                                    $attrs);

          if($this->Form->isFieldError('skip_org_identity_source_id')) {
            print $this->Form->error('skip_org_identity_source_id');
          }
        } else {
          print $vv_org_identity_sources[ $co_provisioning_targets[0]['CoProvisioningTarget']['skip_org_identity_source_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('ordr', _txt('fd.order')) : _txt('fd.order')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.order.prov.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('ordr')
                   : filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
<?php endif; /* plugins */
