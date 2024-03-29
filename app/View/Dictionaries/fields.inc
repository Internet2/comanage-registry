<?php
/**
 * COmanage Registry Dictionary Fields
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
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.dictionaries.1')));
  $this->Html->addCrumb($crumbTxt);
    
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<ul id="<?php print $this->action; ?>_dictionary" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print $this->Form->label('description', _txt('fd.desc')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description') : filter_var($dictionaries[0]['Dictionary']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('mode', _txt('fd.dict.mode')) : _txt('fd.dict.mode')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (!empty($dictionaries[0]['Dictionary']['mode']) ? $dictionaries[0]['Dictionary']['mode'] : DictionaryModeEnum::Standard);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('mode',
                                    $cm_texts[ $cm_lang ]['en.dictionary.mode'],
                                    $attrs);

          if($this->Form->isFieldError('mode')) {
            print $this->Form->error('mode');
          }
        } else {
          print _txt('en.dictionary.mode', null, $dictionaries[0]['Dictionary']['mode']);
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
