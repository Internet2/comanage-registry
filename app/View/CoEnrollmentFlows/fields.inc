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
    } else {
      document.getElementById('approvergroupdiv').style.display = "none";
      document.getElementById('CoEnrollmentFlowApproverCoGroupId').value = "";
    }
  }
  
  function authn_update_gadgets() {
    // Hide and show accordingly.
    
    var confirm = document.getElementById('CoEnrollmentFlowEmailVerificationMode').value;
    var authn = document.getElementById('CoEnrollmentFlowRequireAuthn').checked;
    
    if(confirm != '<?php print VerificationModeEnum::None; ?>') {
      $("#CoEnrollmentFlowInvitationValidity").closest("tr").show('fade');
      fields_update_gadgets();
      $("#CoEnrollmentFlowRequireAuthn").closest("tr").show('fade');
      
      if(authn) {
        $("#CoEnrollmentFlowDuplicateMode").closest("tr").show('fade');
      } else {
        $("#CoEnrollmentFlowDuplicateMode").closest("tr").hide('fade');
      }
    } else {
      $("#CoEnrollmentFlowInvitationValidity").closest("tr").hide('fade');
      $("#CoEnrollmentFlowVerificationTemplateId").closest("tr").hide('fade');
      $("#CoEnrollmentFlowVerificationSubject").closest("tr").hide('fade');
      $("#CoEnrollmentFlowVerificationBody").closest("tr").hide('fade');
      $("#CoEnrollmentFlowRequireAuthn").closest("tr").hide('fade');
      $("#CoEnrollmentFlowDuplicateMode").closest("tr").hide('fade');
    }
    
    tandc_update_gadgets();
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
    
    tandc_update_gadgets();
  }
  
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    
    // If a validation template is selected, hide the subject and body fields
    var vtemplate = document.getElementById('CoEnrollmentFlowVerificationTemplateId').value;
    
    if(vtemplate) {
      $("#CoEnrollmentFlowVerificationSubject").closest("tr").hide('fade');
      $("#CoEnrollmentFlowVerificationBody").closest("tr").hide('fade');
    } else {
      $("#CoEnrollmentFlowVerificationSubject").closest("tr").show('fade');
      $("#CoEnrollmentFlowVerificationBody").closest("tr").show('fade');
    }
    
    // If notify on approval is enabled, show the relevant options
    
    if(document.getElementById('CoEnrollmentFlowNotifyOnApproval').checked) {
      // Hide subject and body if a template is selected
      var atemplate = document.getElementById('CoEnrollmentFlowApprovalTemplateId').value;
      
      $("#CoEnrollmentFlowApprovalTemplateId").closest("tr").show('fade');
      
      if(atemplate) {
        $("#CoEnrollmentFlowApprovalSubject").closest("tr").hide('fade');
        $("#CoEnrollmentFlowApprovalBody").closest("tr").hide('fade');
      } else {
        $("#CoEnrollmentFlowApprovalSubject").closest("tr").show('fade');
        $("#CoEnrollmentFlowApprovalBody").closest("tr").show('fade');
      }
    } else {
      $("#CoEnrollmentFlowApprovalTemplateId").closest("tr").hide('fade');
      $("#CoEnrollmentFlowApprovalSubject").closest("tr").hide('fade');
      $("#CoEnrollmentFlowApprovalBody").closest("tr").hide('fade');
    }
    
    // If notify on finalize is enabled, show the template selector
    
    if(document.getElementById('CoEnrollmentFlowNotifyOnFinalize').checked) {
      $("#CoEnrollmentFlowFinalizationTemplateId").closest("tr").show('fade');
    } else {
      $("#CoEnrollmentFlowFinalizationTemplateId").closest("tr").hide('fade');
    }
  }
  
  function tandc_update_gadgets() {
    // T&C require some form of authentication
    var confirm = document.getElementById('CoEnrollmentFlowEmailVerificationMode').value;
    var authn = document.getElementById('CoEnrollmentFlowRequireAuthn').checked;
    var authz = document.getElementById('CoEnrollmentFlowAuthzLevel').value;
    
    if((confirm != '<?php print VerificationModeEnum::None; ?>' && authn)
       || authz != '<?php print EnrollmentAuthzEnum::None ?>') {
      $("#CoEnrollmentFlowTAndCMode").closest("tr").show('fade');
    } else {
      $("#CoEnrollmentFlowTAndCMode").closest("tr").hide('fade');
    }
  }
  
  function js_local_onload() {
    approver_update_gadgets();
    fields_update_gadgets();
    authn_update_gadgets();
    authz_update_gadgets();
  }
</script>
<table id="<?php print $this->action; ?>_co_enrollment_configuration" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('name',_txt('fd.name')) : _txt('fd.name')); ?>
        </strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('name', array('default' => _txt('fd.untitled'),'class' => 'focusFirst'))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('status',_txt('fd.status')) : _txt('fd.status')); ?>
        </strong><span class="required">*</span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (isset($co_enrollment_flows) ? $co_enrollment_flows[0]['CoEnrollmentFlow']['status'] : EnrollmentFlowStatusEnum::Active);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('status',
                                      $cm_texts[ $cm_lang ]['en.status.ef'],
                                      $attrs);
            
            if($this->Form->isFieldError('status')) {
              print $this->Form->error('status');
            }
          } else {
            print _txt('en.status', null, $co_enrollment_flows[0]['CoEnrollmentFlow']['status']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('authz_level', _txt('fd.ef.authz')) : _txt('fd.ef.authz')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.authz.desc'); ?></span>
      </td>
      <td>
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
        <div id="authzgroupdiv" style="display:none">
          <?php
            if(!empty($co_groups)) {
              $attrs = array();
              $attrs['value'] = (isset($co_enrollment_flows)
                                 ? $co_enrollment_flows[0]['CoEnrollmentFlow']['authz_co_group_id']
                                 : null);
              $attrs['empty'] = true;
              
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
        <div id="authzcoudiv" style="display:none">
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
      </td>
    </tr>
    <?php if(!empty($vv_co_pipelines)): ?>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('co_pipeline_id',_txt('fd.pipeline')) : _txt('fd.pipeline')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.pipeline.desc.ef'); ?></span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['co_pipeline_id'])
                             ? $co_enrollment_flows[0]['CoEnrollmentFlow']['co_pipeline_id']
                             : '');
          $attrs['empty'] = true;
          
          if($e) {
            print $this->Form->select('co_pipeline_id',
                                      $vv_co_pipelines,
                                      $attrs);
            
            if($this->Form->isFieldError('co_pipeline_id')) {
              print $this->Form->error('co_pipeline_id');
            }
          } else {
            print $vv_co_pipelines[ $co_enrollment_flows[0]['CoEnrollmentFlow']['co_pipeline_id'] ];
          }
        ?>
      </td>
    </tr>
    <?php endif; // vv_co_pipelines ?>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('match_policy', _txt('fd.ef.match')) : _txt('fd.ef.match')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.match.desc'); ?></span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (isset($co_enrollment_flows) ? $co_enrollment_flows[0]['CoEnrollmentFlow']['match_policy'] : EnrollmentMatchPolicyEnum::Advisory);
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
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print _txt('fd.ef.appr'); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.appr.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('approval_required', array('onClick' => 'approver_update_gadgets()')) . ' ' .
                       $this->Form->label('approval_required', _txt('fd.ef.appr'))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_required'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        <div id="approvergroupdiv" style="display:none">
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
                
                print '<br /><span class="descr">' . _txt('fd.ef.appr.gr') . '</span>';
              } else {
                print $co_groups[ $co_enrollment_flows[0]['CoEnrollmentFlow']['approver_co_group_id'] ];
              }
            }
          ?>
        </div>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print _txt('fd.ef.ce'); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.ce.desc'); ?></span>
      </td>
      <td>
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
      </td>
    </tr>
    <?php $ll = $l; // use an alternate index for indented rows ?>
    <tr class="line<?php print ($ll % 2); $ll++; ?>">
      <td class="indented">
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('invitation_validity', _txt('fd.ef.invval')) :_txt('fd.ef.invval')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.invval.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('invitation_validity',
                                          array('default' => DEF_INV_VALIDITY))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['invitation_validity'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($ll % 2); $ll++; ?>">
      <td class="indented">
        <strong class="fieldTitle"><?php print _txt('fd.ef.vmt'); ?></strong><br />
        <span class="descr"><?php print _txt('fd.ef.vmt.desc'); ?></span>
      </td>
      <td>
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
      </td>
    </tr>
    <tr class="line<?php print ($ll % 2); $ll++; ?>">
      <td class="indented">
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('verification_subject', _txt('fd.ef.vsub')) : _txt('fd.ef.vsub')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.vsub.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('verification_subject',
                                          array('default' => _txt('em.invite.subject.ef')))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['verification_subject'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($ll % 2); $ll++; ?>">
      <td class="indented">
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('verification_body', _txt('fd.ef.vbody')) : _txt('fd.ef.vbody')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.vbody.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('verification_body',
                                          array('default' => _txt('em.invite.body.ef')))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['verification_body'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>    <tr class="line<?php print ($ll % 2); $ll++; ?>">
      <td class="indented">
        <strong class="fieldTitle">
          <?php print _txt('fd.ef.authn'); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.authn.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('require_authn', array('onClick' => 'authn_update_gadgets()')) . ' ' .
                       $this->Form->label('require_authn', _txt('fd.ef.authn'))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['require_authn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($ll % 2); $ll++; ?>" style="display:none">
      <td class="indented">
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('duplicate_mode', _txt('fd.ef.dupe')) : _txt('fd.ef.dupe')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.dupe.desc'); ?></span>
      </td>
      <td>
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
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('notify_from', _txt('fd.ef.efn')) : _txt('fd.ef.efn')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.efn.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('notify_from')
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_from'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('notification_co_group_id', _txt('fd.ef.nogr')) : _txt('fd.ef.nogr')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.nogr.desc'); ?></span>
      </td>
      <td>
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
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print _txt('fd.ef.noap'); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.noap.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('notify_on_approval', array('onChange' => 'fields_update_gadgets();')) . ' ' .
                       $this->Form->label('notify_on_approval', _txt('fd.ef.noap'))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_on_approval'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($ll % 2); $ll++; ?>">
      <td class="indented">
        <strong class="fieldTitle"><?php print _txt('fd.ef.amt'); ?></strong><br />
        <span class="descr"><?php print _txt('fd.ef.amt.desc'); ?></span>
      </td>
      <td>
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
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>" style="display:none">
      <td class="indented">
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('approval_subject',_txt('fd.ef.asub')) : _txt('fd.ef.asub')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.asub.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('approval_subject',
                                          array('default' => _txt('em.approval.subject.ef')))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_subject'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>" style="display:none">
      <td class="indented">
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('approval_body', _txt('fd.ef.abody')) : _txt('fd.ef.abody')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.abody.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('approval_body',
                                          array('default' => _txt('em.approval.body.ef')))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_body'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print _txt('fd.ef.nof'); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.nof.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('notify_on_finalize', array('onChange' => 'fields_update_gadgets();')) . ' ' .
                       $this->Form->label('notify_on_finalize', _txt('fd.ef.nof'))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_on_finalize'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($ll % 2); $ll++; ?>" style="display:none">
      <td class="indented">
        <strong class="fieldTitle"><?php print _txt('fd.ef.fmt'); ?></strong><br />
        <span class="descr"><?php print _txt('fd.ef.fmt.desc'); ?></span>
      </td>
      <td>
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
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('introduction_text', _txt('fd.ef.intro')) : _txt('fd.ef.intro')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.intro.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('introduction_text')
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['introduction_text'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('conclusion_text', _txt('fd.ef.concl')) : _txt('fd.ef.concl')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.concl.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('conclusion_text')
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['conclusion_text'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('t_and_c_mode', _txt('fd.ef.tandc')) : _txt('fd.ef.tandc')); ?>
        </strong><span class="required">*</span><br />
        <span class="descr"><?php print _txt('fd.ef.tandc.desc'); ?></span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['t_and_c_mode'])
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
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('redirect_on_submit', _txt('fd.ef.rd.submit')) : _txt('fd.ef.rd.submit')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.rd.submit.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('redirect_on_submit', array('size' => 60))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['redirect_on_submit'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('redirect_on_confirm', _txt('fd.ef.rd.confirm')) : _txt('fd.ef.rd.confirm')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.rd.confirm.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('redirect_on_confirm', array('size' => 60))
                     : filter_var($co_enrollment_flows[0]['CoEnrollmentFlow']['redirect_on_confirm'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <?php if(isset($vv_attributes_from_env) && $vv_attributes_from_env): ?>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('ignore_authoritative', _txt('fd.ea.ignauth')) : _txt('fd.ea.ignauth')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ea.ignauth.flow.desc'); ?></span>
      </td>
      <td>
        <?php
          print ($e
                 ? $this->Form->input('ignore_authoritative',
                                      array('default' => false))
                 : ($co_enrollment_flows[0]['CoEnrollmentFlow']['ignore_authoritative']
                    ? _txt('fd.yes') : _txt('fd.no')));
        ?>
      </td>
    </tr>
    <?php endif; ?>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print $this->Form->label('co_theme_id', _txt('fd.theme'), array('class' => 'fieldTitle'));?><br />
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          $attrs = array();
          $attrs['value'] = (isset($co_enrollment_flows[0]['CoEnrollmentFlow']['co_theme_id'])
                             ? $co_enrollment_flows[0]['CoEnrollmentFlow']['co_theme_id']
                             : null);
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
      </td>
    </tr>
    
    <?php
    if($e) {
      print '<tr><td></td><td>';
      print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
      print $this->Form->button(_txt('op.reset'), array('type'=>'reset'));
      print '</td></tr>';
    }
    ?>
  </tbody>
</table>
<?php
  print $this->element("changelog");
