<?php
/**
 * COmanage Registry Vetting Steps Fields
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
 * @since         COmanage Registry v4.1.0
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
  $args['controller'] = 'vetting_steps';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.vetting_steps.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.vetting_steps.1')));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<?php if(empty($plugins)): ?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <?php print _txt('er.plugin.none', array(_txt('ct.vetting_steps.pl'))); ?>
</div>
<?php else: /* plugins */ ?>
<ul id="<?php print $this->action; ?>_vetting_step" class="fields form-list">
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
      <div class="field-desc"><?php print _txt('fd.plugin.warn', array(_txt('ct.vetting_steps.1'))); ?></div>
    </div>
    <div class="field-info">
      <span class="field-info-prefix">
      <?php
        switch($this->action) {
          case 'add':
            print $this->Form->input('plugin');
            break;
          case 'edit':
            print filter_var($vetting_steps[0]['VettingStep']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            // Send the value as hidden since it can't be changed (but is required by the model)
            print $this->Form->hidden('plugin', array('default' => filter_var($vetting_steps[0]['VettingStep']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
            break;
          default:
            print filter_var($vetting_steps[0]['VettingStep']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            break;
        }
      ?>
      </span>

      <?php
        if($this->action == 'edit') {
          // And render a link to the plugin target

          $pl = Inflector::underscore(filter_var($vetting_steps[0]['VettingStep']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS));
          
          print ' ' . $this->Html->link(_txt('op.config'),
                                             array(
                                               'plugin' => $pl,
                                               'controller' => Inflector::pluralize($pl),
                                               'action' => 'edit',
                                               $vetting_steps[0][ $vetting_steps[0]['VettingStep']['plugin'] ]['id']
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
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        
        $attrs = array();
        $attrs['value'] = (isset($vetting_steps) ? $vetting_steps[0]['VettingStep']['status'] : SuspendableStatusEnum::Active);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.susp'],
                                    $attrs);
          
          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status', null, $vetting_steps[0]['VettingStep']['status']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('review_on_result', _txt('fd.vet.review_on_result')) : _txt('fd.vet.review_on_result')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        
        $attrs = array();
        $attrs['value'] = (isset($vetting_steps) ? $vetting_steps[0]['VettingStep']['review_on_result'] : VettingStatusEnum::PendingManual);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('review_on_result',
                                    array(
                                      VettingStatusEnum::PendingManual => _txt('en.status.vet', null, VettingStatusEnum::PendingManual),
                                      VettingStatusEnum::Failed => _txt('en.status.vet', null, VettingStatusEnum::Failed),
                                      VettingStatusEnum::Passed => _txt('en.status.vet', null, VettingStatusEnum::Passed)
                                    ),
                                    $attrs);
          
          if($this->Form->isFieldError('review_on_result')) {
            print $this->Form->error('review_on_result');
          }
        } else {
          print _txt('en.status.vet', null, $vetting_steps[0]['VettingStep']['review_on_result']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('vetter_co_group_id', _txt('fd.vet.group')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.vet.group.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        if(!empty($vv_available_groups)) {
          $attrs = array();
          $attrs['value'] = (isset($vetting_steps) ? $vetting_steps[0]['VettingStep']['vetter_co_group_id'] : null);
          $attrs['empty'] = true;

          print $this->Form->select('vetter_co_group_id',
                                    $vv_available_groups,
                                    $attrs);

          if($this->Form->isFieldError('vetter_co_group_id')) {
            print $this->Form->error('vetter_co_group_id');
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('ordr', _txt('fd.order')) : _txt('fd.order')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('ordr')
                   : filter_var($vetting_steps[0]['VettingStep']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
