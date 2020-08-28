<!--
/**
 * COmanage Registry Dictionary Identifier Validator Plugin Fields
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
  $args['controller'] = 'co_identifier_validators';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_identifier_validators.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_identifier_validators';
  $args['action'] = 'edit';
  $args[] = $vv_ivid;
  $this->Html->addCrumb(_txt('ct.co_identifier_validators.1'), $args);  
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.dictionary_identifier_validators.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_identifier_validator_id', array('default' => $vv_ivid)) . "\n";
?>
<ul id="<?php print $this->action; ?>_dictionary_identifier_validator" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.dictionaries.1'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $attrs = array();
          $attrs['value'] = (isset($dictionary_identifier_validators)
                             ? $dictionary_identifier_validators[0]['DictionaryIdentifierValidator']['dictionary_id']
                             : null);
          $attrs['empty'] = true;
          
          print $this->Form->select('dictionary_id',
                                    $vv_available_dictionaries,
                                    $attrs);
          
          if($this->Form->isFieldError('dictionary_id')) {
            print $this->Form->error('dictionary_id');
          }
        } else {
          if(!empty($dictionary_identifier_validators[0]['DictionaryIdentifierValidator']['dictionary_id'])) {
            print $vv_available_dictionaries[ $dictionary_identifier_validators[0]['DictionaryIdentifierValidator']['dictionary_id'] ];
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.dictidentifier.mode'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.dictidentifier.mode.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($dictionary_identifier_validators[0]['DictionaryIdentifierValidator']['mode'])
                           ? $dictionary_identifier_validators[0]['DictionaryIdentifierValidator']['mode']
                           : ComparisonEnum::Contains);
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('mode',
                                    array(
                                      ComparisonEnum::ContainsInsensitive => _txt('en.comparison', null, ComparisonEnum::ContainsInsensitive),
                                      ComparisonEnum::EqualsInsensitive   => _txt('en.comparison', null, ComparisonEnum::EqualsInsensitive),
                                    ),
                                    $attrs);

          if($this->Form->isFieldError('mode')) {
            print $this->Form->error('mode');
          }
        } else {
          print _txt('en.comparison', null, $dictionary_identifier_validators[0]['DictionaryIdentifierValidator']['mode']);
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
