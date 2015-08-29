<?php
/**
 * COmanage Registry CO Settings Fields
 *
 * Copyright (C) 2014-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.1
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
  $this->Html->addCrumb(_txt('ct.co_settings.pl'));

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  $l = 1;
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  
  function sponsor_update_gadgets() {
    // Hide and show accordingly. Reset sub-popups to prevent superfluous data from
    // being saved. (ie: we don't need a group ID if not setting sponsor to 'CG')
    
    document.getElementById('sponsorgroupdiv').style.display = "none";
    
    var authz = document.getElementById('CoSettingSponsorEligibility').value;
    
    if(authz == 'CG') {
      document.getElementById('sponsorgroupdiv').style.display = "block";
    } else {
      document.getElementById('CoEnrollmentFlowAuthzCoGroupId').value = "";
    }
  }
  
  function js_local_onload() {
    sponsor_update_gadgets();
  }
</script>
<table id="<?php print $this->action; ?>_co_settings" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.xp.disable'); ?></strong><br />
        <span class="descr"><?php print _txt('fd.xp.disable.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('disable_expiration')
                     : (isset($co_settings[0]['CoSetting']['disable_expiration'])
                        && $co_settings[0]['CoSetting']['disable_expiration']
                        ? _txt('fd.yes') : _txt('fd.no')));
        ?>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.nr.enable'); ?></strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('enable_normalization')
                     : (isset($co_settings[0]['CoSetting']['enable_normalization'])
                        && $co_settings[0]['CoSetting']['enable_normalization']
                        ? _txt('fd.yes') : _txt('fd.no')));
        ?>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.de.enable'); ?></strong>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('enable_nsf_demo')
                     : (isset($co_settings[0]['CoSetting']['enable_nsf_demo'])
                        && $co_settings[0]['CoSetting']['enable_nsf_demo']
                        ? _txt('fd.yes') : _txt('fd.no')));
        ?>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.ef.invval'); ?></strong><br />
        <span class="descr"><?php print _txt('fd.ef.invval.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('invitation_validity',
                                          array('default' => DEF_INV_VALIDITY))
                     : Sanitize::html($co_settings[0]['CoSetting']['invitation_validity'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.required.addr'); ?></strong>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          $attrs = array();
          $attrs['value'] = (isset($co_settings[0]['CoSetting']['required_fields_addr'])
                             ? $co_settings[0]['CoSetting']['required_fields_addr']
                             : RequiredAddressFieldsEnum::Street);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('required_fields_addr',
                                      $cm_texts[ $cm_lang ]['en.required.address'],
                                      $attrs);
            
            if($this->Form->isFieldError('required_fields_addr')) {
              print $this->Form->error('required_fields_addr');
            }
          } else {
            print _txt('en.required.address', null, $co_settings[0]['CoSetting']['required_fields_addr']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.required.name'); ?></strong>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          $attrs = array();
          $attrs['value'] = (isset($co_settings[0]['CoSetting']['required_fields_name'])
                             ? $co_settings[0]['CoSetting']['required_fields_name']
                             : RequiredNameFieldsEnum::Given);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('required_fields_name',
                                      $cm_texts[ $cm_lang ]['en.required.name'],
                                      $attrs);
            
            if($this->Form->isFieldError('required_fields_name')) {
              print $this->Form->error('required_fields_name');
            }
          } else {
            print _txt('en.required.name', null, $co_settings[0]['CoSetting']['required_fields_name']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.permitted.name'); ?></strong>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          $attrs = array();
          $attrs['value'] = (isset($co_settings[0]['CoSetting']['permitted_fields_name'])
                             ? $co_settings[0]['CoSetting']['permitted_fields_name']
                             : PermittedNameFieldsEnum::HGMFS);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('permitted_fields_name',
                                      $cm_texts[ $cm_lang ]['en.permitted.name'],
                                      $attrs);
            
            if($this->Form->isFieldError('permitted_fields_name')) {
              print $this->Form->error('permitted_fields_name');
            }
          } else {
            print _txt('en.permitted.name', null, $co_settings[0]['CoSetting']['permitted_fields_name']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.tc.mode.login'); ?></strong><br />
        <span class="descr"><?php print _txt('fd.tc.mode.login.desc'); ?></span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          $attrs = array();
          $attrs['value'] = (isset($co_settings[0]['CoSetting']['t_and_c_login_mode'])
                             ? $co_settings[0]['CoSetting']['t_and_c_login_mode']
                             : "");
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('t_and_c_login_mode',
                                      $cm_texts[ $cm_lang ]['en.tandc.mode.login'],
                                      $attrs);
            
            if($this->Form->isFieldError('t_and_c_login_mode')) {
              print $this->Form->error('t_and_c_login_mode');
            }
          } else {
            print _txt('en.tandc.mode.login', null, $co_settings[0]['CoSetting']['t_and_c_login_mode']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.sponsor.mode'); ?></strong><br />
        <span class="descr"><?php print _txt('fd.sponsor.mode.desc'); ?></span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          $attrs = array();
          $attrs['value'] = (isset($co_settings[0]['CoSetting']['sponsor_eligibility'])
                             ? $co_settings[0]['CoSetting']['sponsor_eligibility']
                             : SponsorEligibilityEnum::CoOrCouAdmin);
          $attrs['empty'] = false;
          $attrs['onChange'] = 'sponsor_update_gadgets()';
          
          if($e) {
            print $this->Form->select('sponsor_eligibility',
                                      $cm_texts[ $cm_lang ]['en.sponsor.eligibility'],
                                      $attrs);
            
            if($this->Form->isFieldError('sponsor_eligibility')) {
              print $this->Form->error('sponsor_eligibility');
            }
          } else {
            print _txt('en.sponsor_eligibility', null, $co_settings[0]['CoSetting']['sponsor_eligibility']);
          }
        ?>
        <div id="sponsorgroupdiv" style="display:none">
          <?php
            if(!empty($vv_co_groups)) {
              $attrs = array();
              $attrs['value'] = (isset($co_settings[0]['CoSetting']['sponsor_co_group_id'])
                                 ? $co_settings[0]['CoSetting']['sponsor_co_group_id']
                                 : null);
              $attrs['empty'] = _txt('op.select.empty');
              
              if($e) {
                print $this->Form->select('sponsor_co_group_id',
                                          $vv_co_groups,
                                          $attrs);
                
                if($this->Form->isFieldError('sponsor_co_group_id')) {
                  print $this->Form->error('sponsor_co_group_id');
                }
              } else {
                print $vv_co_groups[ $co_settings[0]['CoSetting']['sponsor_co_group_id'] ];
              }
            }
          ?>
        </div>
      </td>
    </tr>
    <tr>
      <td>
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
      </td>
      <td>
        <?php
          if($e) {
            print $this->Form->submit($submit_label);
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
