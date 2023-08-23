<?php
/**
 * COmanage Registry CO Enrollment Configuration Fields
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
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'), $args);
  $this->Html->addCrumb(_txt('op.' . $this->action . '-a', array(_txt('ct.co_enrollment_flows.1'))));

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  $l = 1;
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  
  function approver_update_gadgets() {
    // Hide and show accordingly. Reset sub-popups to prevent superfluous data from
    // being saved. (ie: we don't need a group ID if approval is not required)
    
    var approval = document.getElementById('CoEnrollmentFlowApprovalRequired').checked;
    
    if(approval) {
      document.getElementById('approvergroupdiv').style.display = "block";
      document.getElementById('approvernotifydiv').style.display = "block";
      document.getElementById('approvalnotifydiv').style.display = "block";
      $("#CoEnrollmentFlowRequestVetting").closest("li").show('fade');
    } else {
      document.getElementById('approvergroupdiv').style.display = "none";
      document.getElementById('approvernotifydiv').style.display = "none";
      document.getElementById('approvalnotifydiv').style.display = "none";
      document.getElementById('CoEnrollmentFlowApproverCoGroupId').value = "";
      $("#CoEnrollmentFlowRequestVetting").closest("li").hide('fade');
    }
  }
  
  function authn_update_gadgets() {
    // Hide and show accordingly.

    var confirm = document.getElementById('CoEnrollmentFlowEmailVerificationMode').value;
    var authn = document.getElementById('CoEnrollmentFlowRequireAuthn').checked;
    
    if(confirm != '<?php print VerificationModeEnum::None; ?>') {
      $("#CoEnrollmentFlowInvitationValidity").closest("ul.field-children").show('fade');
      fields_update_gadgets();
      $("#CoEnrollmentFlowRequireAuthn").closest("li").show('fade');
      
      if(authn) {
        $("#CoEnrollmentFlowDuplicateMode").closest("li").show('fade');
      } else {
        $("#CoEnrollmentFlowDuplicateMode").closest("li").hide('fade');
      }
      
      dupe_update_gadgets();
    } else {
      $("#CoEnrollmentFlowInvitationValidity").closest("ul.field-children").hide('fade');
    }
  }
  
  function authz_update_gadgets() {
    // Hide and show accordingly. Reset sub-popups to prevent superfluous data from
    // being saved. (ie: we don't need a group ID if not setting authz to 'CG')
    
    document.getElementById('authzcoudiv').style.display = "none";
    document.getElementById('authzgroupdiv').style.display = "none";
    
    var authz = document.getElementById('CoEnrollmentFlowAuthzLevel').value;
    
    // We set the values they way they're done below (as opposed to blanking them
    // out first, like for the divs) so that changing between UA and UP does not
    // blank out the selected COU.

    if(authz == '<?php print EnrollmentAuthzEnum::CoGroupMember; ?>') {
      document.getElementById('authzgroupdiv').style.display = "block";
      if (document.getElementById('CoEnrollmentFlowAuthzCouId')) {
        document.getElementById('CoEnrollmentFlowAuthzCouId').value = "";
      }
    } else if(authz == '<?php print EnrollmentAuthzEnum::CouAdmin ?>' || authz == '<?php print EnrollmentAuthzEnum::CouPerson ?>') {
      document.getElementById('authzcoudiv').style.display = "block";
      if (document.getElementById('CoEnrollmentFlowAuthzCoGroupId')) {
        document.getElementById('CoEnrollmentFlowAuthzCoGroupId').value = "";
      }
    } else {
      if (document.getElementById('CoEnrollmentFlowAuthzCoGroupId')) {
        document.getElementById('CoEnrollmentFlowAuthzCoGroupId').value = "";
      }
      if (document.getElementById('CoEnrollmentFlowAuthzCouId')) {
        document.getElementById('CoEnrollmentFlowAuthzCouId').value = "";
      }
    }
    
    // Update the People Picker tickbox, which only needs to be made available
    // for unregistered petitioners
    if(authz == '<?php print EnrollmentAuthzEnum::None; ?>' || authz == '<?php print EnrollmentAuthzEnum::AuthUser; ?>') {
      $("#CoEnrollmentFlowEnablePersonFind").closest("li").show('fade');
    } else {
      $("#CoEnrollmentFlowEnablePersonFind").closest("li").hide('fade');
    }
  }
  
  function dupe_update_gadgets() {
    // We use the same configuration for old style duplicate on $REMOTE_USER
    // and new style Identity Matching = External.
    
    // Because of how this code is currently structured, we have to reimplement
    // the same lofic in authn_update_gadgets and fields_update_gadgets.
    
    var externalMatch = false;
    var confirmation = false;  // This will go away in Registry v5
    
    var matchpolicy = document.getElementById('CoEnrollmentFlowMatchPolicy').value;
    
    if(matchpolicy == "<?php print EnrollmentMatchPolicyEnum::External; ?>") {
      externalMatch = true;
    }
    
    var confirm = document.getElementById('CoEnrollmentFlowEmailVerificationMode').value;
    var authn = document.getElementById('CoEnrollmentFlowRequireAuthn').checked;
    
    if(confirm != '<?php print VerificationModeEnum::None; ?>' && authn) {
      confirmation = true;
    }
    
    if(externalMatch || confirmation) {
      $("#CoEnrollmentFlowDuplicateMode").closest("li").show('fade');
    } else {
      $("#CoEnrollmentFlowDuplicateMode").closest("li").hide('fade');
    }
  }
  
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    
    // If a validation template is selected, hide the subject and body fields
    var vtemplate = document.getElementById('CoEnrollmentFlowVerificationTemplateId').value;
    
    if(vtemplate) {
      $("#CoEnrollmentFlowVerificationSubject").closest("li").hide('fade');
      $("#CoEnrollmentFlowVerificationBody").closest("li").hide('fade');
    } else {
      $("#CoEnrollmentFlowVerificationSubject").closest("li").show('fade');
      $("#CoEnrollmentFlowVerificationBody").closest("li").show('fade');
    }
    
    // If notify on approval is enabled, show the relevant options
    
    if(document.getElementById('CoEnrollmentFlowNotifyOnApproval').checked) {
      // Hide subject and body if a template is selected
      var atemplate = document.getElementById('CoEnrollmentFlowApprovalTemplateId').value;
      
      $("#CoEnrollmentFlowApprovalTemplateId").closest("li").show('fade');
      $("#CoEnrollmentFlowDenialTemplateId").closest("li").show('fade');
      
      if(atemplate) {
        $("#CoEnrollmentFlowApprovalSubject").closest("li").hide('fade');
        $("#CoEnrollmentFlowApprovalBody").closest("li").hide('fade');
      } else {
        $("#CoEnrollmentFlowApprovalSubject").closest("li").show('fade');
        $("#CoEnrollmentFlowApprovalBody").closest("li").show('fade');
      }
    } else {
      $("#CoEnrollmentFlowApprovalTemplateId").closest("li").hide('fade');
      $("#CoEnrollmentFlowApprovalSubject").closest("li").hide('fade');
      $("#CoEnrollmentFlowApprovalBody").closest("li").hide('fade');
      $("#CoEnrollmentFlowDenialTemplateId").closest("li").hide('fade');
    }
    
    // If notify on finalize is enabled, show the template selector
    
    if(document.getElementById('CoEnrollmentFlowNotifyOnFinalize').checked) {
      $("#CoEnrollmentFlowFinalizationTemplateId").closest("li").show('fade');
    } else {
      $("#CoEnrollmentFlowFinalizationTemplateId").closest("li").hide('fade');
    }
    
    // If Establish Authenticators is enabled, show the available authenticators.
    // Note during add, Authenticators are not yet available for configuration.
    
    <?php if(!empty($vv_authenticators) && !empty($co_enrollment_flows[0]['CoEnrollmentFlow']['id'])): ?>
    if(document.getElementById('CoEnrollmentFlowEstablishAuthenticators').checked) {
      $("#authenticators").show('fade');
    } else {
      $("#authenticators").hide('fade');
    }
    <?php endif; // $vv_authenticators ?>
    
    // If Establish Clusters is enabled, show the available clusters
    
    <?php if(!empty($vv_clusters) && !empty($co_enrollment_flows[0]['CoEnrollmentFlow']['id'])): ?>
    if(document.getElementById('CoEnrollmentFlowEstablishClusterAccounts').checked) {
      $("#clusters").show('fade');
    } else {
      $("#clusters").hide('fade');
    }
    <?php endif; // $vv_clusters ?>

    // If Identity Matching is External, show the available servers
    
    var matchpolicy = document.getElementById('CoEnrollmentFlowMatchPolicy').value;
    
    if(matchpolicy == "<?php print EnrollmentMatchPolicyEnum::External; ?>") {
      $("#CoEnrollmentFlowMatchServerId").closest("li").show('fade');
    } else {
      $("#CoEnrollmentFlowMatchServerId").closest("li").hide('fade');
    }
    
    dupe_update_gadgets();
  }
  
  function js_local_onload() {
    approver_update_gadgets();
    fields_update_gadgets();
    authn_update_gadgets();
    authz_update_gadgets();
    theme_config_fields();
  }

  function theme_config_fields() {
      const $eofCustomTheme = $('#CoEnrollmentFlowCoThemeId > option:selected').val();
      if($eofCustomTheme != undefined
          && $eofCustomTheme !== '') {
          $('.eof-theme-stacking').show().fadeIn('slow','swing');
      } else {
          $('.eof-theme-stacking').hide().fadeOut('slow','swing');
      }
  }
</script>
<ul id="<?php print $this->action; ?>_co_enrollment_configuration" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('name',_txt('fd.name')) : _txt('fd.name')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('name', array('default' => _txt('fd.untitled'),'class' => 'focusFirst'))
                   : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('status',_txt('fd.status')) : _txt('fd.status')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_enrollment_flows) ? $co_enrollment_flows[0]['CoEnrollmentFlow']['status'] : TemplateableStatusEnum::Active);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.temp'],
                                    $attrs);

          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status.temp', null, $co_enrollment_flows[0]['CoEnrollmentFlow']['status']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('authz_level', _txt('fd.ef.authz')) : _txt('fd.ef.authz')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.authz.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_enrollment_flows)
                            ? $co_enrollment_flows[0]['CoEnrollmentFlow']['authz_level']
                            : EnrollmentAuthzEnum::CoOrCouAdmin);
        $attrs['empty'] = false;
        $attrs['onChange'] = 'authz_update_gadgets()';

        if($e) {
          print $this->Form->select('authz_level',
                                    $cm_texts[ $cm_lang ]['en.enrollment.authz'],
                                    $attrs);

          if($this->Form->isFieldError('authz_level')) {
            print $this->Form->error('authz_level');
          }
        } else {
          print _txt('en.enrollment.authz', null, $co_enrollment_flows[0]['CoEnrollmentFlow']['authz_level']);
        }
      ?>
      <div id="authzgroupdiv" class="subfield" style="display:none">
        <?php
          if(!empty($co_groups)) {
            $attrs = array();
            $attrs['value'] = (isset($co_enrollment_flows)
                               ? $co_enrollment_flows[0]['CoEnrollmentFlow']['authz_co_group_id']
                               : null);
            $attrs['empty'] = _txt('op.select.empty');

            if($e) {
              print $this->Form->label('authz_co_group_id', _txt('fd.ef.authz.group')) . ': ';
              print $this->Form->select('authz_co_group_id',
                                        $co_groups,
                                        $attrs);

              if($this->Form->isFieldError('authz_co_group_id')) {
                print $this->Form->error('authz_co_group_id');
              }
            } else {
              print $co_groups[ $co_enrollment_flows[0]['CoEnrollmentFlow']['authz_co_group_id'] ];
            }
          }
        ?>
      </div>
      <div id="authzcoudiv" class="subfield" style="display:none">
        <?php
          if(!empty($cous)) {
            $attrs = array();
            $attrs['value'] = (isset($co_enrollment_flows)
                               ? $co_enrollment_flows[0]['CoEnrollmentFlow']['authz_cou_id']
                               : null);
            $attrs['empty'] = true;

            if($e) {
              print $this->Form->label('authz_cou_id', _txt('fd.ef.authz.cou')) . ': ';
              print $this->Form->select('authz_cou_id',
                                        $cous,
                                        $attrs);

              if($this->Form->isFieldError('authz_cou_id')) {
                print $this->Form->error('authz_cou_id');
              }
            } else {
              print $cous[ $co_enrollment_flows[0]['CoEnrollmentFlow']['authz_cou_id'] ];
            }
          }
        ?>
      </div>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title">
        <?php print _txt('fd.ef.shortcut'); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.shortcut.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('my_identity_shortcut') . 
                     $this->Form->label('my_identity_shortcut', _txt('fd.ef.shortcut'))
                   : ($co_enrollment_flows[0]['CoEnrollmentFlow']['my_identity_shortcut'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('match_policy', _txt('fd.ef.match')) : _txt('fd.ef.match')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.match.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_enrollment_flows) ? $co_enrollment_flows[0]['CoEnrollmentFlow']['match_policy'] : EnrollmentMatchPolicyEnum::Advisory);
        $attrs['onChange'] = 'fields_update_gadgets()';
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('match_policy',
                                    $cm_texts[ $cm_lang ]['en.enrollment.match'],
                                    $attrs);

          if($this->Form->isFieldError('match_policy')) {
            print $this->Form->error('match_policy');
          }
        } else {
          print _txt('en.enrollment.match', null, $co_enrollment_flows[0]['CoEnrollmentFlow']['match_policy']);
        }
      ?>
    </div>
    <ul class="field-children">
      <li style="display:none">
        <div class="field-name">
          <div class="field-title"><?php print _txt('ct.match_servers.1'); ?></div>
        </div>
        <div class="field-info">
          <?php
            $attrs = array();
            $attrs['value'] = (isset($co_enrollment_flows[0]['CoEnrollmentFlow']['match_server_id'])
                               ? $co_enrollment_flows[0]['CoEnrollmentFlow']['match_server_id']
                               : null);
            $attrs['empty'] = false;

            if($e) {
              print $this->Form->select('match_server_id',
                                        $vv_servers,
                                        $attrs);

              if($this->Form->isFieldError('match_server_id')) {
                print $this->Form->error('match_server_id');
              }
            } else {
              if(!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['match_server_id'])) {
                print filter_var($vv_servers[ $co_enrollment_flows[0]['CoEnrollmentFlow']['match_server_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
              }
            }
          ?>
        </div>
      </li>
    </ul>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('sor_label',_txt('fd.sor_label')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.sor_label.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('sor_label') : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['sor_label'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('enable_person_find', _txt('fd.ef.enable_person_find')) : _txt('fd.ef.enable_person_find')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.enable_person_find.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('enable_person_find') . 
                     $this->Form->label('enable_person_find', _txt('fd.ef.enable_person_find'))
                   : ($co_enrollment_flows[0]['CoEnrollmentFlow']['enable_person_find'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title">
        <?php print _txt('fd.ef.appr'); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.appr.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('approval_required', array('onClick' => 'approver_update_gadgets()')) . ' ' .
                     $this->Form->label('approval_required', _txt('fd.ef.appr'))
                   : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_required'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      <div id="approvergroupdiv" class="subfield" style="display:none">
        <?php
          if(!empty($co_groups)) {
            $attrs = array();
            $attrs['value'] = (isset($co_enrollment_flows[0]['CoEnrollmentFlow']['approver_co_group_id'])
                               ? $co_enrollment_flows[0]['CoEnrollmentFlow']['approver_co_group_id']
                               : null);
            $attrs['empty'] = true;

            if($e) {
              print $this->Form->label('approver_co_group_id', _txt('fd.ef.appr.pl')) . ': ';
              print $this->Form->select('approver_co_group_id',
                                        $co_groups,
                                        $attrs);

              if($this->Form->isFieldError('approver_co_group_id')) {
                print $this->Form->error('approver_co_group_id');
              }

              print '<div class="field-desc">' . _txt('fd.ef.appr.gr') . '</div>';
            } else {
              print $co_groups[ $co_enrollment_flows[0]['CoEnrollmentFlow']['approver_co_group_id'] ];
            }
          }
        ?>
      </div>
    </div>
    <div id="approvernotifydiv" class="subfield" style="display:none">
      <ul class="field-children">
        <li>
          <div class="field-name">
            <div class="field-title"><?php print _txt('fd.ef.appr.ntf'); ?></div>
            <div class="field-desc"><?php print _txt('fd.ef.appr.ntf.desc'); ?></div>
          </div>
          <div class="field-info">
            <?php
            global $cm_lang, $cm_texts;
            $attrs = array();
            $attrs['value'] = (!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['approver_template_id'])
              ? $co_enrollment_flows[0]['CoEnrollmentFlow']['approver_template_id']
              : null);
            $attrs['empty'] = true;

            if($e) {
              print $this->Form->select('approver_template_id',
                (!empty($vv_message_templates[MessageTemplateEnum::EnrollmentApprover])
                  ? $vv_message_templates[MessageTemplateEnum::EnrollmentApprover]
                  : array()), $attrs);

              if($this->Form->isFieldError('approver_template_id')) {
                print $this->Form->error('approver_template_id');
              }
            } elseif(!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['approver_template_id'])) {
              print filter_var($vv_message_templates[MessageTemplateEnum::EnrollmentApprover][ $co_enrollment_flows[0]['CoEnrollmentFlow']['approver_template_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
            }
            ?>
          </div>
        </li>
      </ul>
    </div>

  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.ef.ce'); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.ce.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['email_verification_mode'])
                           ? $co_enrollment_flows[0]['CoEnrollmentFlow']['email_verification_mode']
                           : VerificationModeEnum::None);
        $attrs['empty'] = false;
        $attrs['onchange'] = "authn_update_gadgets();";

        if($e) {
          print $this->Form->select('email_verification_mode',
                                    $cm_texts[ $cm_lang ]['en.enrollment.verification'],
                                    $attrs);

          if($this->Form->isFieldError('email_verification_mode')) {
            print $this->Form->error('email_verification_mode');
          }
        } else {
          print _txt('en.enrollment.verification', null, $co_enrollment_flows[0]['CoEnrollmentFlow']['email_verification_mode']);
        }
      ?>
    </div>
    <ul class="field-children">
      <li>
        <div class="field-name">
          <div class="field-title">
            <?php print ($e ? $this->Form->label('invitation_validity', _txt('fd.ef.invval')) :_txt('fd.ef.invval')); ?>
          </div>
          <div class="field-desc"><?php print _txt('fd.ef.invval.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php print ($e
            ? $this->Form->input('invitation_validity',
              array('default' => DEF_INV_VALIDITY))
            : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['invitation_validity'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </div>
      </li>
      <li>
        <div class="field-name">
          <div class="field-title">
            <?php print ($e ? $this->Form->label('regenerate_expired_verification', _txt('fd.ef.inv.regen')) :_txt('fd.ef.inv.regen')); ?>
          </div>
          <div class="field-desc"><?php print _txt('fd.ef.inv.regen.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php print ($e
            ? $this->Form->input('regenerate_expired_verification') . ' ' .
            $this->Form->label('regenerate_expired_verification', _txt('fd.ef.inv.regen'))
            : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['regenerate_expired_verification'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </div>
      </li>
      <li>
        <div class="field-name">
          <div class="field-title"><?php print _txt('fd.ef.vmt'); ?></div>
          <div class="field-desc"><?php print _txt('fd.ef.vmt.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php
            global $cm_lang, $cm_texts;
            $attrs = array();
            $attrs['value'] = (!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['verification_template_id'])
              ? $co_enrollment_flows[0]['CoEnrollmentFlow']['verification_template_id']
              : null);
            $attrs['empty'] = true;
            $attrs['onchange'] = "fields_update_gadgets();";

            if($e) {
              print $this->Form->select('verification_template_id',
                (!empty($vv_message_templates[MessageTemplateEnum::EnrollmentVerification])
                  ? $vv_message_templates[MessageTemplateEnum::EnrollmentVerification]
                  : array()),
                $attrs);

              if($this->Form->isFieldError('verification_template_id')) {
                print $this->Form->error('verification_template_id');
              }
            } elseif(!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['verification_template_id'])) {
              print filter_var($vv_message_templates[MessageTemplateEnum::EnrollmentVerification][ $co_enrollment_flows[0]['CoEnrollmentFlow']['verification_template_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </div>
      </li>
      <li>
        <div class="field-name">
          <div class="field-title">
            <?php print ($e ? $this->Form->label('verification_subject', _txt('fd.ef.vsub')) : _txt('fd.ef.vsub')); ?>
          </div>
          <div class="field-desc"><?php print _txt('fd.ef.vsub.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php print ($e
            ? $this->Form->input('verification_subject',
              array('default' => _txt('em.invite.subject.ef')))
            : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['verification_subject'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </div>
      </li>
      <li class="field-stack">
        <div class="field-name">
          <div class="field-title">
            <?php print ($e ? $this->Form->label('verification_body', _txt('fd.ef.vbody')) : _txt('fd.ef.vbody')); ?>
          </div>
          <div class="field-desc"><?php print _txt('fd.ef.vbody.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php print ($e
            ? $this->Form->input('verification_body',
              array('default' => _txt('em.invite.body.ef')))
            : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['verification_body'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </div>
      </li>
      <li>
        <div class="field-name">
          <div class="field-title">
            <?php print _txt('fd.ef.authn'); ?>
          </div>
          <div class="field-desc"><?php print _txt('fd.ef.authn.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php print ($e
            ? $this->Form->input('require_authn', array('onClick' => 'authn_update_gadgets()')) . ' ' .
            $this->Form->label('require_authn', _txt('fd.ef.authn'))
            : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['require_authn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </div>
      </li>
    </ul>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('request_vetting', _txt('fd.ef.vet')) : _txt('fd.ef.vet')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.vet.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('request_vetting') . 
                     $this->Form->label('request_vetting', _txt('fd.ef.vet'))
                   : ($co_enrollment_flows[0]['CoEnrollmentFlow']['request_vetting'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
    </div>
  </li>
  <li style="display:none">
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('duplicate_mode', _txt('fd.ef.dupe')) : _txt('fd.ef.dupe')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.dupe.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['duplicate_mode'])
          ? $co_enrollment_flows[0]['CoEnrollmentFlow']['duplicate_mode']
          : EnrollmentDupeModeEnum::Duplicate);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('duplicate_mode',
            $cm_texts[ $cm_lang ]['en.enrollment.dupe'],
            $attrs);

          if($this->Form->isFieldError('duplicate_mode')) {
            print $this->Form->error('duplicate_mode');
          }
        } else {
          print _txt('en.enrollment.dupe', null, $co_enrollment_flows[0]['CoEnrollmentFlow']['duplicate_mode']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('notify_from', _txt('fd.ef.efn')) : _txt('fd.ef.efn')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.efn.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('notify_from')
                   : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_from'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('notification_co_group_id', _txt('fd.ef.nogr')) : _txt('fd.ef.nogr')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.nogr.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        if(!empty($co_groups)) {
          $attrs = array();
          $attrs['value'] = (isset($co_enrollment_flows[0]['CoEnrollmentFlow']['notification_co_group_id'])
                             ? $co_enrollment_flows[0]['CoEnrollmentFlow']['notification_co_group_id']
                             : null);
          $attrs['empty'] = true;

          if($e) {
            print $this->Form->select('notification_co_group_id',
                                      $co_groups,
                                      $attrs);

            if($this->Form->isFieldError('notification_co_group_id')) {
              print $this->Form->error('notification_co_group_id');
            }
          } else {
            print $co_groups[ $co_enrollment_flows[0]['CoEnrollmentFlow']['notification_co_group_id'] ];
          }
        }
      ?>
    </div>
  </li>
  <div id="approvalnotifydiv" class="subfield" style="display:none">
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.ef.noap'); ?>
        </div>
        <div class="field-desc"><?php print _txt('fd.ef.noap.desc'); ?></div>
      </div>
      <div class="field-info checkbox">
        <?php print ($e
                     ? $this->Form->input('notify_on_approval', array('onChange' => 'fields_update_gadgets();')) . ' ' .
                       $this->Form->label('notify_on_approval', _txt('fd.ef.noap'))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_on_approval'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
      <ul class="field-children">
        <li>
          <div class="field-name">
            <div class="field-title"><?php print _txt('fd.ef.amt'); ?></div>
            <div class="field-desc"><?php print _txt('fd.ef.amt.desc'); ?></div>
          </div>
          <div class="field-info">
            <?php
              global $cm_lang, $cm_texts;
              $attrs = array();
              $attrs['value'] = (!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_template_id'])
                ? $co_enrollment_flows[0]['CoEnrollmentFlow']['approval_template_id']
                : null);
              $attrs['empty'] = true;
              $attrs['onchange'] = "fields_update_gadgets();";

              if($e) {
                print $this->Form->select('approval_template_id',
                  (!empty($vv_message_templates[MessageTemplateEnum::EnrollmentApproval])
                    ? $vv_message_templates[MessageTemplateEnum::EnrollmentApproval]
                    : array()),
                  $attrs);

                if($this->Form->isFieldError('approval_template_id')) {
                  print $this->Form->error('approval_template_id');
                }
              } elseif(!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_template_id'])) {
                print filter_var($vv_message_templates[MessageTemplateEnum::EnrollmentApproval][ $co_enrollment_flows[0]['CoEnrollmentFlow']['approval_template_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </div>
        </li>
        <li style="display:none">
          <div class="field-name">
            <div class="field-title">
              <?php print ($e ? $this->Form->label('approval_subject',_txt('fd.ef.asub')) : _txt('fd.ef.asub')); ?>
            </div>
            <div class="field-desc"><?php print _txt('fd.ef.asub.desc'); ?></div>
          </div>
          <div class="field-info">
            <?php print ($e
              ? $this->Form->input('approval_subject',
                array('default' => _txt('em.approval.subject.ef')))
              : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_subject'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
          </div>
        </li>
        <li class="field-stack" style="display:none">
          <div class="field-name">
            <div class="field-title">
              <?php print ($e ? $this->Form->label('approval_body', _txt('fd.ef.abody')) : _txt('fd.ef.abody')); ?>
            </div>
            <div class="field-desc"><?php print _txt('fd.ef.abody.desc'); ?></div>
          </div>
          <div class="field-info">
            <?php print ($e
              ? $this->Form->input('approval_body',
                array('default' => _txt('em.approval.body.ef')))
              : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_body'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
          </div>
        </li>
        <li>
          <div class="field-name">
            <div class="field-title"><?php print _txt('fd.ef.dmt'); ?></div>
            <div class="field-desc"><?php print _txt('fd.ef.dmt.desc'); ?></div>
          </div>
          <div class="field-info">
            <?php
              global $cm_lang, $cm_texts;
              $attrs = array();
              $attrs['value'] = (!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['denial_template_id'])
                ? $co_enrollment_flows[0]['CoEnrollmentFlow']['denial_template_id']
                : null);
              $attrs['empty'] = true;

              if($e) {
                print $this->Form->select('denial_template_id',
                  (!empty($vv_message_templates[MessageTemplateEnum::EnrollmentApproval])
                    ? $vv_message_templates[MessageTemplateEnum::EnrollmentApproval]
                    : array()),
                  $attrs);

                if($this->Form->isFieldError('denial_template_id')) {
                  print $this->Form->error('denial_template_id');
                }
              } elseif(!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['denial_template_id'])) {
                print filter_var($vv_message_templates[MessageTemplateEnum::EnrollmentApproval][ $co_enrollment_flows[0]['CoEnrollmentFlow']['denial_template_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
              }
            ?>
          </div>
        </li>
      </ul>
    </li>
  </div>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.ef.nof'); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.nof.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('notify_on_finalize', array('onChange' => 'fields_update_gadgets();')) . ' ' .
                     $this->Form->label('notify_on_finalize', _txt('fd.ef.nof'))
                   : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_on_finalize'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
    <ul class="field-children">
      <li style="display:none">
        <div class="field-name">
          <div class="field-title"><?php print _txt('fd.ef.fmt'); ?></div>
          <div class="field-desc"><?php print _txt('fd.ef.fmt.desc'); ?></div>
        </div>
        <div class="field-info">
          <?php
            global $cm_lang, $cm_texts;
            $attrs = array();
            $attrs['value'] = (!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['finalization_template_id'])
              ? $co_enrollment_flows[0]['CoEnrollmentFlow']['finalization_template_id']
              : null);
            $attrs['empty'] = false;

            if($e) {
              print $this->Form->select('finalization_template_id',
                (!empty($vv_message_templates[MessageTemplateEnum::EnrollmentFinalization])
                  ? $vv_message_templates[MessageTemplateEnum::EnrollmentFinalization]
                  : array()),
                $attrs);

              if($this->Form->isFieldError('finalization_template_id')) {
                print $this->Form->error('finalization_template_id');
              }
            } elseif(!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['finalization_template_id'])) {
              print filter_var($vv_message_templates[MessageTemplateEnum::EnrollmentFinalization][ $co_enrollment_flows[0]['CoEnrollmentFlow']['finalization_template_id'] ],FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </div>
      </li>
    </ul>
  </li>
  <li class="field-stack">
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('introduction_text', _txt('fd.ef.intro')) : _txt('fd.ef.intro')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.intro.start.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('introduction_text')
                   : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['introduction_text'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li class="field-stack">
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('introduction_text_pa', _txt('fd.ef.intro')) : _txt('fd.ef.intro')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.intro.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
        ? $this->Form->input('introduction_text_pa')
        : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['introduction_text_pa'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li class="field-stack">
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('conclusion_text', _txt('fd.ef.concl')) : _txt('fd.ef.concl')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.concl.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('conclusion_text')
                   : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['conclusion_text'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if($vv_tandc_count > 0): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('t_and_c_mode', _txt('fd.ef.tandc')) : _txt('fd.ef.tandc')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.tandc.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_enrollment_flows)
                           ? $co_enrollment_flows[0]['CoEnrollmentFlow']['t_and_c_mode']
                           : TAndCEnrollmentModeEnum::ExplicitConsent);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('t_and_c_mode',
                                    $cm_texts[ $cm_lang ]['en.tandc.mode.enroll'],
                                    $attrs);

          if($this->Form->isFieldError('t_and_c_mode')) {
            print $this->Form->error('t_and_c_mode');
          }
        } else {
          print _txt('en.tandc.mode.enroll', null, $co_enrollment_flows[0]['CoEnrollmentFlow']['t_and_c_mode']);
        }
      ?>
    </div>
  </li>
  <?php endif; // vv_tandc_count ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('redirect_on_submit', _txt('fd.ef.rd.submit')) : _txt('fd.ef.rd.submit')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.rd.submit.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('redirect_on_submit', array('size' => 60))
                   : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['redirect_on_submit'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('redirect_on_confirm', _txt('fd.ef.rd.confirm')) : _txt('fd.ef.rd.confirm')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.rd.confirm.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('redirect_on_confirm', array('size' => 60))
        : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['redirect_on_confirm'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('redirect_on_finalize', _txt('fd.ef.rd.finalize')) : _txt('fd.ef.rd.finalize')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.rd.finalize.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('redirect_on_finalize', array('size' => 60))
        : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['redirect_on_finalize'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li class="field-stack">
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('return_url_allowlist', _txt('fd.ef.allowlist')) : _txt('fd.ef.allowlist')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.allowlist.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('return_url_allowlist')
                   : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['return_url_allowlist'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if(isset($vv_attributes_from_env) && $vv_attributes_from_env): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('ignore_authoritative', _txt('fd.ea.ignauth')) : _txt('fd.ea.ignauth')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ea.ignauth.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        print ($e
               ? $this->Form->input('ignore_authoritative',
                                    array('default' => false))
               : ($co_enrollment_flows[0]['CoEnrollmentFlow']['ignore_authoritative']
                  ? _txt('fd.yes') : _txt('fd.no')));
      ?>
    </div>
  </li>
  <?php endif; ?>
  <?php if(!empty($vv_authenticators) && !empty($co_enrollment_flows[0]['CoEnrollmentFlow']['id'])): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('establish_authenticators', _txt('fd.ef.authenticators')) : _txt('fd.ef.authenticators')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.authenticators.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php
        print ($e
               ? $this->Form->input('establish_authenticators',
                                    array('onChange' => 'fields_update_gadgets();',
                                          'default' => false))
               : ($co_enrollment_flows[0]['CoEnrollmentFlow']['establish_authenticators']
                  ? _txt('fd.yes') : _txt('fd.no')));
      ?>
    </div>
    <div id="authenticators" style="display:none">
    <ul class="field-children">
      <?php 
        // We need to make sure fields match to Cake's index number, unless we
        // have a new authenticator. This is similar logic to LdapProvisioner.
        
        $newid = count($vv_authenticators);
        
        foreach($vv_authenticators as $authid => $authname): 
      ?>
      <li>
        <div class="field-name">
          <div class="field-title"><?php print filter_var($authname,FILTER_SANITIZE_SPECIAL_CHARS); ?></div>
        </div>
        <div class="field-info">
          <?php
            if($e) {
              // Do we have a current record for this authenticator?
              $cur = Hash::extract($co_enrollment_flows[0]['CoEnrollmentAuthenticator'],
                                   '{n}[authenticator_id='.$authid.']');
              $checked = false;
              
              $xindex = -1;
              
              if(!empty($cur[0]['id'])) {
                $xindex = $cur[0]['id'];
              } else {
                $xindex = $newid;
                $newid++;
              }
              
              $xprefix = 'CoEnrollmentAuthenticator.' . $xindex;
              
              if(!empty($cur)) {
                print $this->Form->hidden($xprefix . '.id', array('default' => $cur[0]['id']));
              }
              
              print $this->Form->hidden($xprefix . '.co_enrollment_flow_id', 
                                        array('default' => $co_enrollment_flows[0]['CoEnrollmentFlow']['id']));
              
              print $this->Form->hidden($xprefix . '.authenticator_id', 
                                        array('default' => $authid));
              
              global $cm_lang, $cm_texts;
              $attrs = array();
              $attrs['value'] = (isset($cur[0]['required'])
                                 ? $cur[0]['required']
                                 : RequiredEnum::NotPermitted);
              $attrs['empty'] = false;
              
              print $this->Form->select($xprefix . '.required',
                                        $cm_texts[ $cm_lang ]['en.required'],
                                        $attrs);
              
              if($this->Form->isFieldError($xprefix . '.required')) {
                print $this->Form->error($xprefix . '.required');
              }
            }
          ?>
        </div>
      </li>
    <?php endforeach; // vv_authenticators ?>
    </ul>
    </div>
  </li>
  <?php endif; // vv_authenticators ?>
  <?php if(!empty($vv_clusters) && !empty($co_enrollment_flows[0]['CoEnrollmentFlow']['id'])): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('establish_cluster_accounts', _txt('fd.ef.clusters')) : _txt('fd.ef.clusters')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ef.clusters.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php
        print ($e
               ? $this->Form->input('establish_cluster_accounts',
                                    array('onChange' => 'fields_update_gadgets();',
                                          'default' => false))
               : ($co_enrollment_flows[0]['CoEnrollmentFlow']['establish_cluster_accounts']
                  ? _txt('fd.yes') : _txt('fd.no')));
      ?>
    </div>
    <div id="clusters" style="display:none">
    <ul class="field-children">
      <?php 
        // We need to make sure fields match to Cake's index number, unless we
        // have a new cluster. This is similar logic to authenticators, above.
        
        $newid = count($vv_clusters);
        
        foreach($vv_clusters as $clusterid => $clustername): 
      ?>
      <li>
        <div class="field-name">
          <div class="field-title"><?php print filter_var($clustername,FILTER_SANITIZE_SPECIAL_CHARS); ?></div>
        </div>
        <div class="field-info">
          <?php
            if($e) {
              // Do we have a current record for this cluster?
              $cur = Hash::extract($co_enrollment_flows[0]['CoEnrollmentCluster'],
                                   '{n}[cluster_id='.$clusterid.']');
              $checked = false;
              
              $xindex = -1;
              
              if(!empty($cur[0]['id'])) {
                $xindex = $cur[0]['id'];
              } else {
                $xindex = $newid;
                $newid++;
              }
              
              $xprefix = 'CoEnrollmentCluster.' . $xindex;
              
              if(!empty($cur)) {
                print $this->Form->hidden($xprefix . '.id', array('default' => $cur[0]['id']));
              }
              
              print $this->Form->hidden($xprefix . '.co_enrollment_flow_id', 
                                        array('default' => $co_enrollment_flows[0]['CoEnrollmentFlow']['id']));
              
              print $this->Form->hidden($xprefix . '.cluster_id', 
                                        array('default' => $clusterid));
              
              print $this->Form->checkbox($xprefix . '.enabled',
                                          array('default' => isset($cur[0]['enabled']) ? $cur[0]['enabled'] : false));
            }
          ?>
        </div>
      </li>
    <?php endforeach; // vv_clusters ?>
    </ul>
    </div>
  </li>
  <?php endif; // vv_clusters ?>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('co_theme_id', _txt('fd.theme'), array('class' => 'fieldTitle'));?>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;

        $attrs = array();
        $attrs['value'] = (isset($co_enrollment_flows[0]['CoEnrollmentFlow']['co_theme_id'])
                           ? $co_enrollment_flows[0]['CoEnrollmentFlow']['co_theme_id']
                           : null);
        $attrs['onchange'] = 'theme_config_fields()';
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('co_theme_id',
                                    $vv_co_themes,
                                    $attrs);

          if($this->Form->isFieldError('co_theme_id')) {
            print $this->Form->error('co_theme_id');
          }
        } else {
          print $vv_co_themes[ $co_enrollment_flows[0]['CoEnrollmentFlow']['co_theme_id'] ];
        }
      ?>
    </div>
    <ul class="field-children">
      <li class="eof-theme-stacking">
          <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label('theme_stacking',_txt('fd.theme.stacking_status')) : _txt('fd.theme.stacking_status')); ?>
              </div>
          </div>
          <div class="field-info">
            <?php
            $attrs = array();
            $attrs['value'] = (isset($co_enrollment_flows) ? $co_enrollment_flows[0]['CoEnrollmentFlow']['theme_stacking'] : SuspendableStatusEnum::Active);
            $attrs['empty'] = false;
            if($e) {
              print $this->Form->select('theme_stacking',
                                        SuspendableStatusEnum::$to_api,
                                        $attrs);
              if($this->Form->isFieldError('theme_stacking')) {
                print $this->Form->error('theme_stacking');
              }
            } else {
              print _txt('en.status.susp', null, $co_enrollment_flows[0]['CoEnrollmentFlow']['theme_stacking']);
            }
            ?>
          </div>
      </li>
    </ul>
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
<?php
  print $this->element("changelog");
