<!--
/**
 * COmanage Registry CO Enrollment Configuration Fields
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);
    
  if($e)
  {
    echo $this->Html->link(_txt('op.cancel'),
                            array('controller' => 'co_enrollment_flows', 'action' => 'index', 'co' => $cur_co['Co']['id']),
                            array('class' => 'cancelbutton'));
    
    if($this->action == "edit" && $permissions['edit'])
      echo $this->Html->link(_txt('op.edit.ea'),
                              array('controller' => 'co_enrollment_attributes',
                                    'action' => 'index',
                                    'coef' => $co_enrollment_flows[0]['CoEnrollmentFlow']['id']),
                              array('class' => 'linkbutton'));
  }

  echo '
    <br />
    <br />
    ';
  
  echo $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  
  function authz_update_gadgets() {
    // Hide and show accordingly. Reset sub-popups to prevent superfluous data from
    // being saved. (ie: we don't need a group ID if not setting authz to 'CG')
    
    document.getElementById('authzcoudiv').style.display = "none";
    document.getElementById('authzgroupdiv').style.display = "none";
    
    var authz = document.getElementById('CoEnrollmentFlowAuthzLevel').value;
    
    // We set the values they way they're done below (as opposed to blanking them
    // out first, like for the divs) so that changing between UA and UP does not
    // blank out the selected COU.
    
    if(authz == 'CG') {
      document.getElementById('authzgroupdiv').style.display = "block";
      document.getElementById('CoEnrollmentFlowAuthzCouId').value = "";
    } else if(authz == 'UA' || authz == 'UP') {
      document.getElementById('authzcoudiv').style.display = "block";
      document.getElementById('CoEnrollmentFlowAuthzCoGroupId').value = "";
    } else {
      document.getElementById('CoEnrollmentFlowAuthzCoGroupId').value = "";
      document.getElementById('CoEnrollmentFlowAuthzCouId').value = "";
    }
  }
  
  function js_local_onload() {
    authz_update_gadgets();
  }
</script>
<table id="<?php print $this->action; ?>_co_enrollment_configuration" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.name'); ?></b>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('name', array('default' => _txt('fd.untitled')))
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['name'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.status'); ?></b><font class="required">*</font>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($co_enrollment_flows) ? $co_enrollment_flows[0]['CoEnrollmentFlow']['status'] : StatusEnum::Active);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('status',
                                      $cm_texts[ $cm_lang ]['en.status'],
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
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ef.authz'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.authz.desc'); ?></font>
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
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.ef.match'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.match.desc'); ?></font>
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
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ef.appr'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.appr.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('approval_required')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['approval_required'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.ef.ce'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.ce.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('verify_email')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['verify_email'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ef.authn'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.authn.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('require_authn')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['require_authn'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.ef.epx'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.epx.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('early_provisioning_exec')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['early_provisioning_exec'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ef.px'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.px.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('provisioning_exec')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['provisioning_exec'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.ef.noep'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.noep.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('notify_on_early_provision')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_on_early_provision'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ef.nop'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.nop.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('notify_on_provision')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_on_provision'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.ef.noa'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.noa.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('notify_on_active')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_on_active'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.ef.efn'); ?></b><br />
        <font class="desc"><?php print _txt('fd.ef.efn.desc'); ?></font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('notify_from')
                     : Sanitize::html($co_enrollment_flows[0]['CoEnrollmentFlow']['notify_from'])); ?>
      </td>
    </tr>
  </tbody>
</table>
<?php
  if($e)
    print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
?>
