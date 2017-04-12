<?php
/**
 * COmanage Registry CO Extended Attributes Fields
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
 * @since         COmanage Registry v0.1
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
  $args['controller'] = 'co_extended_attributes';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_extended_attributes.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_extended_attributes.1')));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<ul id="<?php print $this->action; ?>_co_extended_attribute" class="fields form-list">
  <li>
    <div class="field-name vtop">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('name', _txt('fd.name')) : _txt('fd.name')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : filter_var($co_extended_attributes[0]['CoExtendedAttribute']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      <div class="field-desc"><?php print _txt('fd.lan.desc'); ?></div>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('display_name', _txt('fd.name.d')) : _txt('fd.name.d')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('display_name') : filter_var($co_extended_attributes[0]['CoExtendedAttribute']['display_name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <div class="field-title">
        <?php print ($e && $this->action=='add' ? $this->Form->label('type', _txt('fd.type')) : _txt('fd.type')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs['value'] = (isset($co_extended_attributes) ? $co_extended_attributes[0]['CoExtendedAttribute']['type'] : "");
        $attrs['empty'] = false;

        if($e && $this->action=='add') {
          print $this->Form->select('type',
                                    $cm_texts[ $cm_lang ]['en.extattr'],
                                    $attrs);

          if($this->Form->isFieldError('type')) {
            print $this->Form->error('type');
          }
        } else {
          print _txt('en.extattr', null, $co_extended_attributes[0]['CoExtendedAttribute']['type']);
        }

        // Emit data type to make validation work
        if($e && $this->action=='edit')
          print $this->Form->hidden('type');
      ?>
      <div class="field-desc">
        <span class="ui-icon ui-icon-info co-info"></span>
        <?php print _txt('fd.type.warn'); ?>
      </div>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.index'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('indx') . ' ' . $this->Form->label('indx', _txt('fd.index'))
                   : ($co_extended_attributes[0]['CoExtendedAttribute']['type'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
