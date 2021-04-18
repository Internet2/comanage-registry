<?php
/**
 * COmanage Registry Authenticator Fields
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
 * @since         COmanage Registry v3.1.0
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
  $args['controller'] = 'authenticators';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.authenticators.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.authenticators.1')));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<?php if(empty($plugins)): ?>
<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php print _txt('er.plugin.none', array(_txt('ct.authenticators.pl'))); ?>
</div>
<?php else: /* plugins */ ?>
<ul id="<?php print $this->action; ?>_authenticator" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description') : filter_var($authenticators[0]['Authenticator']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e && $this->action == 'add' ? $this->Form->label('plugin', _txt('fd.plugin')) : _txt('fd.plugin')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.plugin.warn', array(_txt('ct.authenticators.1'))); ?></div>
    </div>
    <div class="field-info">
      <?php
        switch($this->action) {
          case 'add':
            print $this->Form->input('plugin');
            break;
          case 'edit':
            print filter_var($authenticators[0]['Authenticator']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            // Send the value as hidden since it can't be changed (but is required by the model)
            print $this->Form->hidden('plugin', array('default' => filter_var($authenticators[0]['Authenticator']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
            break;
          default:
            print filter_var($authenticators[0]['Authenticator']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            break;
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
        $attrs['value'] = (isset($authenticators) ? $authenticators[0]['Authenticator']['status'] : SuspendableStatusEnum::Active);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.susp'],
                                    $attrs);
          
          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status.susp', null, $authenticators[0]['Authenticator']['status']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.au.mt'); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.au.mt.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (!empty($authenticators[0]['Authenticator']['co_message_template_id'])
                          ? $authenticators[0]['Authenticator']['co_message_template_id']
                          : null);
        $attrs['empty'] = true;
        
        if($e) {
          print $this->Form->select('co_message_template_id',
            (!empty($vv_message_templates[MessageTemplateEnum::Authenticator])
                    ? $vv_message_templates[MessageTemplateEnum::Authenticator]
                    : array()),
            $attrs);
          
          if($this->Form->isFieldError('co_message_template_id')) {
            print $this->Form->error('co_message_template_id');
          }
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
<?php endif; /* plugins */
