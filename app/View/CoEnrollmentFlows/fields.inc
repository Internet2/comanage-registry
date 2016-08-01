<?php
/**
 * COmanage Registry CO Enrollment Configuration Fields
 *
 * Copyright (C) 2011-16 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-16 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
    
    var confirm = document.getElementById('CoEnrollmentFlowVerifyEmail').checked;
    var authn = document.getElementById('CoEnrollmentFlowRequireAuthn').checked;
    
    if(confirm) {
      $("#CoEnrollmentFlowInvitationValidity").closest("tr").show('fade');
      $("#CoEnrollmentFlowVerificationSubject").closest("tr").show('fade');
      $("#CoEnrollmentFlowVerificationBody").closest("tr").show('fade');
      $("#CoEnrollmentFlowRequireAuthn").closest("tr").show('fade');
      
      if(authn) {
        $("#CoEnrollmentFlowDuplicateMode").closest("tr").show('fade');
      } else {
        $("#CoEnrollmentFlowDuplicateMode").closest("tr").hide('fade');
      }
    } else {
      $("#CoEnrollmentFlowInvitationValidity").closest("tr").hide('fade');
      $("#CoEnrollmentFlowVerificationSubject").closest("tr").hide('fade');
      $("#CoEnrollmentFlowVerificationBody").closest("tr").hide('fade');
      $("#CoEnrollmentFlowRequireAuthn").closest("tr").hide('fade');
      $("#CoEnrollmentFlowDuplicateMode").closest("tr").hide('fade');
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

    if(authz == '<?php print EnrollmentAuthzEnum::CoGroupMember ?>') {
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
      if(authz == '<?php print EnrollmentAuthzEnum::None ?>') {
        // If no authz is required, then T&C must be ignored
        if (document.getElementById('CoEnrollmentFlowTAndCMode')) {
          document.getElementById('CoEnrollmentFlowTAndCMode').value = "<?php print TAndCEnrollmentModeEnum::Ignore ?>";
        }
      }
      if (document.getElementById('CoEnrollmentFlowAuthzCoGroupId')) {
        document.getElementById('CoEnrollmentFlowAuthzCoGroupId').value = "";
      }
      if (document.getElementById('CoEnrollmentFlowAuthzCouId')) {
        document.getElementById('CoEnrollmentFlowAuthzCouId').value = "";
      }
    }
  }
  
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    if(document.getElementById('CoEnrollmentFlowNotifyOnApproval').checked) {
      $("#CoEnrollmentFlowApprovalSubject").closest("tr").show('fade');
      $("#CoEnrollmentFlowApprovalBody").closest("tr").show('fade');
    } else {
      $("#CoEnrollmentFlowApprovalSubject").closest("tr").hide('fade');
      $("#CoEnrollmentFlowApprovalBody").closest("tr").hide('fade');
    }
  }
  
  function js_local_onload() {
    approver_update_gadgets();
    authn_update_gadgets();
    authz_update_gadgets();
    fields_update_gadgets();
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['name'])); ?>
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
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('org_identity_mode', _txt('fd.ef.orgid')) : _txt('fd.ef.orgid')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ef.orgid.desc'); ?></span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (!empty($co_enrollment_flows[0]['CoEnrollmentFlow']['org_identity_mode'])
                             ? $co_enrollment_flows[0]['CoEnrollmentFlow']['org_identity_mode']
                             : EnrollmentOrgIdentityModeEnum::None);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('org_identity_mode',
                                      $cm_texts[ $cm_lang ]['en.enrollment.orgid'],
                                      $attrs);
            
            if($this->Form->isFieldError('org_identity_mode')) {
              print $this->Form->error('org_identity_mode');
            }
          } else {
            print _txt('en.enrollment.orgid', null, $co_enrollment_flows[0]['CoEnrollmentFlow']['org_identity_mode']);
          }
        ?>
      </td>
    </tr>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_required'])); ?>
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
        <?php print ($e
                     ? $this->Form->input('verify_email', array('onClick' => 'authn_update_gadgets()')) . ' ' .
                       $this->Form->label('verify_email', _txt('fd.ef.ce'))
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['verify_email'])); ?>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['invitation_validity'])); ?>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['verification_subject'])); ?>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['verification_body'])); ?>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['require_authn'])); ?>
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
          $attrs['value'] = (isset($co_enrollment_flows)
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_from'])); ?>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_on_approval'])); ?>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_subject'])); ?>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_body'])); ?>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['introduction_text'])); ?>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['conclusion_text'])); ?>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['redirect_on_submit'])); ?>
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
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['redirect_on_confirm'])); ?>
      </td>
    </tr>
    <?php if(isset($vv_attributes_from_env) && $vv_attributes_from_env): ?>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('ignore_authoritative', _txt('fd.ea.ignauth')) : _txt('fd.ea.ignauth')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.ea.ignauth.desc'); ?></span>
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
