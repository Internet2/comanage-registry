<?php
/**
 * COmanage Registry CO Themes Fields
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
  $args['controller'] = 'co_themes';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_themes.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_themes.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<ul id="<?php print $this->action; ?>_co_themes" class="field form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('name', _txt('fd.name')) : _txt('fd.name')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('name', array('size' => '80','class' => 'focusFirst'))
        : filter_var($co_themes[0]['CoTheme']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.th.hide_title'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('hide_title') . ' ' . $this->Form->label('hide_title', _txt('fd.th.hide_title'))
                      :  ($co_themes[0]['CoTheme']['hide_title'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.th.hide_footer_logo'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('hide_footer_logo') . ' ' .  $this->Form->label('hide_footer_logo', _txt('fd.th.hide_footer_logo'))
                   : ($co_themes[0]['CoTheme']['hide_footer_logo'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
    </div>
  </li>
  <li class="field-stack">
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('header', _txt('fd.th.header')) : _txt('fd.th.header')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('header')
                   : filter_var($co_themes[0]['CoTheme']['header'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li class="field-stack">
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('footer', _txt('fd.th.footer')) : _txt('fd.th.footer')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('footer')
                   : filter_var($co_themes[0]['CoTheme']['footer'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li class="field-stack">
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('css', _txt('fd.th.css')) : _txt('fd.th.css')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('css')
                   : filter_var($co_themes[0]['CoTheme']['css'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
