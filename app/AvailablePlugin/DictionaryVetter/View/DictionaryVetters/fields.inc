<!--
/**
 * COmanage Registry Dictionary Vetters Plugin Fields
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
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
  $args['controller'] = 'vetting_steps';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.vetting_steps.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'vetting_steps';
  $args['action'] = 'edit';
  $args[] = $dictionary_vetters[0]['VettingStep']['id'];
  $this->Html->addCrumb($dictionary_vetters[0]['VettingStep']['description'], $args);
  
  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('vetting_step_id', array('default' => $dictionary_vetters[0]['VettingStep']['id'])) . "\n";
?>
<ul id="<?php print $this->action; ?>_dictionary_vetter" class="fields form-list form-list-admin">
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
          $attrs['value'] = (isset($dictionary_vetters)
                             ? $dictionary_vetters[0]['DictionaryVetter']['dictionary_id']
                             : null);
          $attrs['empty'] = true;

          print $this->Form->select('dictionary_id',
                                    $vv_available_dictionaries,
                                    $attrs);

          if($this->Form->isFieldError('dictionary_id')) {
            print $this->Form->error('dictionary_id');
          }
        } else {
          if(!empty($dictionary_vetters[0]['DictionaryVetter']['dictionary_id'])) {
            print $vv_available_dictionaries[ $dictionary_vetters[0]['DictionaryVetter']['dictionary_id'] ];
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('check_identity_documents', _txt('pl.dictionaryvetter.check_identity_documents')) : _txt('pl.dictionaryvetter.check_identity_documents')); ?>
      </div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('check_identity_documents') .
                     $this->Form->label('check_identity_documents', _txt('pl.dictionaryvetter.check_identity_documents'))
                   : ($co_enrollment_flows[0]['CoEnrollmentFlow']['check_identity_documents'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('check_organizations', _txt('pl.dictionaryvetter.check_organizations')) : _txt('pl.dictionaryvetter.check_organizations')); ?>
      </div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('check_organizations') .
                     $this->Form->label('check_organizations', _txt('pl.dictionaryvetter.check_organizations'))
                   : ($co_enrollment_flows[0]['CoEnrollmentFlow']['check_organizations'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
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
