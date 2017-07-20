<?php
/**
 * COmanage Registry API Users Fields
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
 * @since         COmanage Registry v0.8.4
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
  $this->Html->addCrumb(_txt('ct.api_users.pl'), array('controller' => 'api_users', 'action' => 'index'));
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.api_users.1')));
  $this->Html->addCrumb($crumbTxt);

?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <?php print _txt('ap.note.privs'); ?>
</div>

<ul id="<?php print $this->action; ?>_api_users" class="fields form-list">
  <li>
    <div class="field-name vtop">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('username', _txt('fd.username.api')) : _txt('fd.username.api')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
        ? $this->Form->input('username', array('class' => 'focusFirst'))
        : filter_var($api_users[0]['ApiUser']['username'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      <span class="ui-icon ui-icon-info co-info"></span>
      <span class="desc"><?php print _txt('ap.note.username'); ?></span>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('password', _txt('fd.password')) : _txt('fd.password')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->password('password')
        : filter_var($api_users[0]['ApiUser']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label, array('onClick' => 'on_submit()')); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
