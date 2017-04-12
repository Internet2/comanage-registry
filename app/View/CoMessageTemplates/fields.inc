<?php
/**
 * COmanage Registry CO Message Templates Fields
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
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
  $args['controller'] = 'co_message_templates';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_message_templates.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_message_templates.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  $l = 1;
?>
<ul id="<?php print $this->action; ?>_co_message_templates" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.desc'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description', array('size' => '60', 'class' => 'focusFirst'))
                      : filter_var($co_message_templates[0]['CoMessageTemplate']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.mt.context'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_message_templates[0]['CoMessageTemplate']['context'])
                           ? $co_message_templates[0]['CoMessageTemplate']['context']
                           : null);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('context',
                                    $cm_texts[ $cm_lang ]['en.mt.context'],
                                    $attrs);
          
          if($this->Form->isFieldError('context')) {
            print $this->Form->error('context');
          }
        } else {
          if(!empty($co_message_templates[0]['CoMessageTemplate']['context'])) {
            print _txt('en.mt.context', null, $co_message_templates[0]['CoMessageTemplate']['context']);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.cc'); ?></div>
      <div class="field-desc"><?php print _txt('fd.cc.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('cc')
                      : filter_var($co_message_templates[0]['CoMessageTemplate']['cc'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.bcc'); ?></div>
      <div class="field-desc"><?php print _txt('fd.bcc.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('bcc')
                      : filter_var($co_message_templates[0]['CoMessageTemplate']['bcc'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.mt.sub'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.mt.sub.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('message_subject')
                   : filter_var($co_message_templates[0]['CoMessageTemplate']['message_subject'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li class="field-stack">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.mt.body'); ?>
        <span class="required">*</span>
      </div>
      <span class="descr"><?php print _txt('fd.mt.body.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('message_body')
                   : filter_var($co_message_templates[0]['CoMessageTemplate']['message_body'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.status'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_message_templates[0]['CoMessageTemplate']['status'])
                           ? $co_message_templates[0]['CoMessageTemplate']['status']
                           : SuspendableStatusEnum::Active);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.susp'],
                                    $attrs);
          
          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          if(!empty($co_message_templates[0]['CoMessageTemplate']['status'])) {
            print _txt('en.status', null, $co_message_templates[0]['CoMessageTemplate']['status']);
          }
        }
      ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name"></div>
      <div class="field-info">
        <?php
          print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
        ?>
      </div>
    </li>
  <?php endif; // $e ?>
</ul>
<?php
  print $this->element("changelog");
