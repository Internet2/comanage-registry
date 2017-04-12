<?php
/**
 * COmanage Registry CO Extended Types Fields
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
 * @since         COmanage Registry v0.6
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
  $args['controller'] = 'co_extended_types';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_extended_types.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_extended_types.1')));
  $this->Html->addCrumb($crumbTxt);
    
  // Find the attribute
  if($this->action == 'add' && isset($this->request->query['attr'])) {
    $attr = filter_var($this->request->query['attr'],FILTER_SANITIZE_SPECIAL_CHARS);
  } elseif(isset($this->request->data['CoExtendedType']['attribute'])) {
    $attr = $this->request->data['CoExtendedType']['attribute'];
  }
    
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  print $this->Form->hidden('attribute', array('default' => $attr)) . "\n";
  
  $l = 1;
?>
<ul id="<?php print $this->action; ?>_co_extended_type" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.attribute'); ?></div>
    </div>
    <div class="field-info">
      <?php
        if(isset($vv_supported_attrs[ $attr ])) {
          print $vv_supported_attrs[ $attr ];
        } else {
          print $attr;
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('name', _txt('fd.name')) : _txt('fd.name')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.an.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : filter_var($co_extended_types[0]['CoExtendedType']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
      <?php print ($e ? $this->Form->input('display_name') : filter_var($co_extended_types[0]['CoExtendedType']['display_name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.status.et.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_extended_types) ? $co_extended_types[0]['CoExtendedType']['status'] : SuspendableStatusEnum::Active);
        $attrs['empty'] = false;
        if($vv_type_in_use) {
          // A type in use cannot be suspended (see controller for more details)
          $attrs['disabled'] = true;
          
          print $this->Form->hidden('status', array('default' => true)) . "\n";
        }
        
        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.susp'],
                                    $attrs);
          
          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status.susp', null, $co_extended_types[0]['CoExtendedType']['status']);
        }
      ?>
    </div>
  </li>
  <?php if($attr == 'CoPersonRole.affiliation'): // Affiliations can map to eduPersonAffiliations ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('edupersonaffiliation', _txt('fd.affiliation.ep')) : _txt('fd.affiliation.ep')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.affiliation.ep.map.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_extended_types) ? $co_extended_types[0]['CoExtendedType']['edupersonaffiliation'] : "");
        $attrs['empty'] = true;
        
        if($e) {
          print $this->Form->select('edupersonaffiliation',
                                    $cm_texts[ $cm_lang ]['en.co_person_role.affiliation'],
                                    $attrs);
          
          if($this->Form->isFieldError('edupersonaffiliation')) {
            print $this->Form->error('edupersonaffiliation');
          }
        } else {
          print _txt('en.co_person_role.affiliation', null, $co_extended_types[0]['CoExtendedType']['edupersonaffiliation']);
        }
      ?>
    </div>
  </li>
  <?php endif; // affiliation ?>
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