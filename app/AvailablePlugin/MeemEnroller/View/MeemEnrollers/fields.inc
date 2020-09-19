<!--
/**
 * COmanage Registry Meem Enrollers Plugin Fields
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
 * @since         COmanage Registry v4.0.0
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
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'edit';
  $args[] = $meem_enrollers[0]['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $this->Html->addCrumb($meem_enrollers[0]['CoEnrollmentFlowWedge']['CoEnrollmentFlow']['name'], $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'index';
  $args['coef'] = $meem_enrollers[0]['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $args[] = $meem_enrollers[0]['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flow_wedges.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'edit';
  $args[] = $meem_enrollers[0]['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb($meem_enrollers[0]['CoEnrollmentFlowWedge']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  // Populate cross references
  print $this->Form->hidden('co_enrollment_flow_wedge_id', array('default' => $meem_enrollers[0]['CoEnrollmentFlowWedge']['id'])) . "\n";
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    
    if(document.getElementById('MeemEnrollerEnableReminderPage').checked) {
      $("#allowlist").show('fade');
    } else {
      $("#allowlist").hide('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }
</script>
<ul id="<?php print $this->action; ?>_meem_enroller" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.meemenroller.env_idp'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.meemenroller.env_idp.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('env_idp'); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.meemenroller.env_mfa'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.meemenroller.env_mfa.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('env_mfa'); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.meemenroller.mfa_exempt_co_group_id'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.meemenroller.mfa_exempt_co_group_id.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($meem_enrollers[0]['MeemEnroller']['mfa_exempt_co_group_id'])
                                 ? $meem_enrollers[0]['MeemEnroller']['mfa_exempt_co_group_id']
                                 : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('mfa_exempt_co_group_id',
                                    $vv_available_groups,
                                    $attrs);

          if($this->Form->isFieldError('mfa_exempt_co_group_id')) {
            print $this->Form->error('mfa_exempt_co_group_id');
          }
        } else {
          print $vv_available_groups[ $meem_enrollers[0]['MeemEnroller']['mfa_exempt_co_group_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.meemenroller.mfa_initial_exemption'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.meemenroller.mfa_initial_exemption.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('mfa_initial_exemption'); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.meemenroller.mfa_co_enrollment_flow_id'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.meemenroller.mfa_co_enrollment_flow_id.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($meem_enrollers[0]['MeemEnroller']['mfa_co_enrollment_flow_id'])
                                 ? $meem_enrollers[0]['MeemEnroller']['mfa_co_enrollment_flow_id']
                                 : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('mfa_co_enrollment_flow_id',
                                    $vv_available_flows,
                                    $attrs);

          if($this->Form->isFieldError('mfa_co_enrollment_flow_id')) {
            print $this->Form->error('mfa_co_enrollment_flow_id');
          }
        } else {
          print $vv_available_flows[ $meem_enrollers[0]['MeemEnroller']['mfa_co_enrollment_flow_id'] ];
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.meemenroller.enable_reminder_page'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('enable_reminder_page',
                                     array('onChange' => 'fields_update_gadgets();',
                                           'default' => false)); ?>
    </div>
  </li>
  <div id="allowlist" style="display:none">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.meemenroller.return_url_allowlist'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.meemenroller.return_url_allowlist.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('return_url_allowlist'); ?>
    </div>
  </li>
  </div>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.api_users.1'); ?>
      </div>
      <span class="descr"><?php print _txt('pl.meemenroller.api_user.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($meem_enrollers[0]['MeemEnroller']['api_user_id'])
                           ? $meem_enrollers[0]['MeemEnroller']['api_user_id']
                           : "");
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('api_user_id',
                                    $vv_api_users,
                                    $attrs);

          if($this->Form->isFieldError('api_user_id')) {
            print $this->Form->error('api_user_id');
          }
        } else {
          print filter_var($vv_api_users[ $meem_enrollers[0]['MeemEnroller']['api_user_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
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
