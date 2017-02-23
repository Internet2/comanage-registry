<?php
/**
 * COmanage Registry CO Message Templates Fields
 *
 * Copyright (C) 2016 SCG
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
 * @copyright     Copyright (C) 2016 SCG
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
<table id="<?php print $this->action; ?>_co_message_templates" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.desc'); ?></b><span class="required">*</span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('description', array('size' => '60', 'class' => 'focusFirst'))
                        : filter_var($co_message_templates[0]['CoMessageTemplate']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.mt.context'); ?></b>
      </td>
      <td>
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
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.cc'); ?></b><br />
        <em><?php print _txt('fd.cc.desc'); ?></em>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('cc')
                        : filter_var($co_message_templates[0]['CoMessageTemplate']['cc'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.bcc'); ?></b><br />
        <em><?php print _txt('fd.bcc.desc'); ?></em>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('bcc')
                        : filter_var($co_message_templates[0]['CoMessageTemplate']['bcc'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.mt.sub'); ?></strong><span class="required">*</span><br />
        <span class="descr"><?php print _txt('fd.mt.sub.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('message_subject')
                     : filter_var($co_message_templates[0]['CoMessageTemplate']['message_subject'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <strong class="fieldTitle"><?php print _txt('fd.mt.body'); ?></strong><span class="required">*</span><br />
        <span class="descr"><?php print _txt('fd.mt.body.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('message_body')
                     : filter_var($co_message_templates[0]['CoMessageTemplate']['message_body'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.status'); ?></b><span class="required">*</span>
      </td>
      <td>
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
      </td>
    </tr>
    <?php if($e): ?>
    <tr>
      <td></td>
      <td>
        <?php
          print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
          print $this->Form->button(_txt('op.reset'), array('type'=>'reset'));
        ?>
      </td>
    </tr>
    <?php endif; // $e ?>
  </tbody>
</table>
<?php
  print $this->element("changelog");
