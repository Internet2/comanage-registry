<?php
/**
 * COmanage Registry CO Settings Fields
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
 * @since         COmanage Registry v0.9.1
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
<ul id="<?php print $this->action; ?>_co_settings" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.xp.disable'); ?></div>
      <div class="field-desc"><?php print _txt('fd.xp.disable.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('disable_expiration')
                   : (isset($co_settings[0]['CoSetting']['disable_expiration'])
                      && $co_settings[0]['CoSetting']['disable_expiration']
                      ? _txt('fd.yes') : _txt('fd.no')));
            print ' ' . $this->Form->label('disable_expiration',_txt('fd.xp.disable'));
      ?>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.ois.sync.disable'); ?></div>
      <div class="field-desc"><?php print _txt('fd.ois.sync.disable.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('disable_ois_sync')
                   : (isset($co_settings[0]['CoSetting']['disable_ois_sync'])
                      && $co_settings[0]['CoSetting']['disable_ois_sync']
                      ? _txt('fd.yes') : _txt('fd.no')));
            print ' ' . $this->Form->label('disable_ois_sync',_txt('fd.ois.sync.disable'));
      ?>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.nr.enable'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('enable_normalization')
                   : (isset($co_settings[0]['CoSetting']['enable_normalization'])
                      && $co_settings[0]['CoSetting']['enable_normalization']
                      ? _txt('fd.yes') : _txt('fd.no')));
            print ' ' . $this->Form->label('enable_normalization',_txt('fd.nr.enable'));
      ?>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.de.enable'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('enable_nsf_demo')
                   : (isset($co_settings[0]['CoSetting']['enable_nsf_demo'])
                      && $co_settings[0]['CoSetting']['enable_nsf_demo']
                      ? _txt('fd.yes') : _txt('fd.no')));
            print ' ' . $this->Form->label('enable_nsf_demo',_txt('fd.de.enable'));
      ?>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print $this->Form->label('invitation_validity',_txt('fd.ef.invval'),array('class' => 'fieldTitle'));?></div>
      <div class="field-desc"><?php print _txt('fd.ef.invval.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('invitation_validity',
                                        array('default' => DEF_INV_VALIDITY))
                   : filter_var($co_settings[0]['CoSetting']['invitation_validity'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print $this->Form->label('required_fields_addr',_txt('fd.required.addr'),array('class' => 'fieldTitle'));?></div>
    </div>
    <div class="field-info">
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
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print $this->Form->label('required_fields_name',_txt('fd.required.name'),array('class' => 'fieldTitle'));?></div>
    </div>
    <div class="field-info">
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
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print $this->Form->label('permitted_fields_name',_txt('fd.permitted.name'),array('class' => 'fieldTitle'));?></div>
    </div>
    <div class="field-info">
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
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print $this->Form->label('t_and_c_login_mode',_txt('fd.tc.mode.login'),array('class' => 'fieldTitle'));?></div>
      <div class="field-desc"><?php print _txt('fd.tc.mode.login.desc'); ?></div>
    </div>
    <div class="field-info">
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
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print $this->Form->label('sponsor_eligibility',_txt('fd.sponsor.mode'),array('class' => 'fieldTitle'));?></div>
      <div class="field-desc"><?php print _txt('fd.sponsor.mode.desc'); ?></div>
    </div>
    <div class="field-info">
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
        <?php print $this->Form->label('sponsor_co_group_id',_txt('ct.co_groups.1'));?>
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
    </div>
  </li>
  <?php if(!empty($vv_co_pipelines)): ?>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print $this->Form->label('default_co_pipeline_id',_txt('fd.pi.default'),array('class' => 'fieldTitle'));?></div>
      <div class="field-desc"><?php print _txt('fd.pi.default.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($co_settings[0]['CoSetting']['default_co_pipeline_id'])
                           ? $co_settings[0]['CoSetting']['default_co_pipeline_id']
                           : "");
        $attrs['empty'] = true;
        
        if($e) {
          print $this->Form->select('default_co_pipeline_id',
                                    $vv_co_pipelines,
                                    $attrs);
          
          if($this->Form->isFieldError('default_co_pipeline_id')) {
            print $this->Form->error('default_co_pipeline_id');
          }
        } else {
          print $vv_co_pipelines[ $co_settings[0]['CoSetting']['default_co_pipeline_id'] ];
        }
      ?>
    </div>
  </li>
  <?php endif; // co_pipelines ?>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print $this->Form->label('elect_strategy_primary_name',_txt('fd.pi.elect.name.p'),array('class' => 'fieldTitle'));?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        
        $attrs = array();
        $attrs['value'] = (isset($co_settings[0]['CoSetting']['elect_strategy_primary_name'])
                           ? $co_settings[0]['CoSetting']['elect_strategy_primary_name']
                           : ElectStrategyEnum::Manual);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('elect_strategy_primary_name',
                                    $cm_texts[ $cm_lang ]['en.elect.strategy'],
                                    $attrs);
          
          if($this->Form->isFieldError('elect_strategy_primary_name')) {
            print $this->Form->error('elect_strategy_primary_name');
          }
        } else {
          print _txt('en.elect.strategy', null, $co_settings[0]['CoSetting']['elect_strategy_primary_name']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print $this->Form->label('co_theme_id', _txt('fd.theme'), array('class' => 'fieldTitle'));?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        
        $attrs = array();
        $attrs['value'] = (isset($co_settings[0]['CoSetting']['co_theme_id'])
                           ? $co_settings[0]['CoSetting']['co_theme_id']
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
          print $vv_co_themes[ $co_settings[0]['CoSetting']['co_theme_id'] ];
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
