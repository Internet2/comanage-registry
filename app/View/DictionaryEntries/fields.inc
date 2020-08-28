<?php
/**
 * COmanage Registry Dictionary Entry Fields
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.0.0
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
  $args['controller'] = 'dictionaries';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.dictionaries.pl'), $args);
  
  $args = array();
  $args['controller'] = 'dictionaries';
  $args['action'] = 'edit';
  $args[] = $vv_dict_id;
  $this->Html->addCrumb($vv_dict_name, $args);
  
  $args = array();
  $args['controller'] = 'dictionary_entries';
  $args['action'] = 'index';
  $args['dictionary'] = $vv_dict_id;
  $this->Html->addCrumb(_txt('ct.dictionary_entries.pl'), $args);
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.dictionary_entries.1')));
  $this->Html->addCrumb($crumbTxt);
    
  print $this->Form->hidden('dictionary_id', array('default' => $vv_dict_id)) . "\n";
?>
<ul id="<?php print $this->action; ?>_dictionary_entry" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print $this->Form->label('value', _txt('fd.value')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('value') : filter_var($dictionary_entries[0]['DictionaryEntry']['value'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('code', _txt('fd.code')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('code') : filter_var($dictionary_entries[0]['DictionaryEntry']['code'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('ordr', _txt('fd.order')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('ordr') : filter_var($dictionary_entries[0]['DictionaryEntry']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
