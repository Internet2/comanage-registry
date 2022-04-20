<!--
/**
 * COmanage Registry Sponsor Manager Settings Fields
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
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
  $args['controller'] = 'co_dashboards';
  $args['action'] = 'configuration';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.configuration'), $args);

  $crumbTxt = _txt('ct.sponsor_manager_settings.pl');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $sponsor_manager_settings[0]['SponsorManagerSetting']['co_id'])) . "\n";
?>
<ul id="<?php print $this->action; ?>_sponsor_manager_settings" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('lookahead_window', _txt('pl.sponsormanager.lookahead_window')) : _txt('pl.sponsormanager.lookahead_window')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.sponsormanager.lookahead_window.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('lookahead_window') : filter_var($sponsor_manager_settings[0]['SponsorManagerSetting']['lookahead_window'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('renewal_term', _txt('pl.sponsormanager.renewal_term')) : _txt('pl.sponsormanager.renewal_term')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.sponsormanager.renewal_term.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('renewal_term') : filter_var($sponsor_manager_settings[0]['SponsorManagerSetting']['renewal_term'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('renewal_window', _txt('pl.sponsormanager.renewal_window')) : _txt('pl.sponsormanager.renewal_window')); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.sponsormanager.renewal_window.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('renewal_window') : filter_var($sponsor_manager_settings[0]['SponsorManagerSetting']['renewal_window'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.sponsormanager.identifier_type'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.sponsormanager.identifier_type.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($sponsor_manager_settings[0]['SponsorManagerSetting']['identifier_type'])
                           ? $sponsor_manager_settings[0]['SponsorManagerSetting']['identifier_type']
                           : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('identifier_type',
                                    $vv_identifier_types,
                                    $attrs);

          if($this->Form->isFieldError('identifier_type')) {
            print $this->Form->error('identifier_type');
          }
        } else {
          if(!empty($sponsor_manager_settings[0]['SponsorManagerSetting']['identifier_type'])) {
            print $vv_identifier_types[ $sponsor_manager_settings[0]['SponsorManagerSetting']['identifier_type'] ];
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.sponsormanager.email_type'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.sponsormanager.email_type.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($sponsor_manager_settings[0]['SponsorManagerSetting']['email_type'])
                           ? $sponsor_manager_settings[0]['SponsorManagerSetting']['email_type']
                           : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('email_type',
                                    $vv_email_types,
                                    $attrs);

          if($this->Form->isFieldError('email_type')) {
            print $this->Form->error('email_type');
          }
        } else {
          if(!empty($sponsor_manager_settings[0]['SponsorManagerSetting']['email_type'])) {
            print $vv_email_types[ $sponsor_manager_settings[0]['SponsorManagerSetting']['email_type'] ];
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('show_affiliation', _txt('pl.sponsormanager.show_affiliation')) : _txt('pl.sponsormanager.show_affiliation')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('show_affiliation') : filter_var($sponsor_manager_settings[0]['SponsorManagerSetting']['show_affiliation'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('show_cou', _txt('pl.sponsormanager.show_cou')) : _txt('pl.sponsormanager.show_cou')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('show_cou') : filter_var($sponsor_manager_settings[0]['SponsorManagerSetting']['show_cou'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('show_o', _txt('pl.sponsormanager.show_o')) : _txt('pl.sponsormanager.show_o')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('show_o') : filter_var($sponsor_manager_settings[0]['SponsorManagerSetting']['show_o'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('show_title', _txt('pl.sponsormanager.show_title')) : _txt('pl.sponsormanager.show_title')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('show_title') : filter_var($sponsor_manager_settings[0]['SponsorManagerSetting']['show_title'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
