<?php
/**
 * COmanage Registry Attribute Enumeration Fields
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
  $args['controller'] = 'attribute_enumerations';
  $args['action'] = 'index';
  if(!empty($cur_co['Co']['id'])) {
    $args['co'] = $cur_co['Co']['id'];
  }
  $this->Html->addCrumb(_txt('ct.attribute_enumerations.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.attribute_enumerations.1')));
  $this->Html->addCrumb($crumbTxt);
  
  if(!empty($cur_co['Co']['id'])) {
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  }
  
  $l = 1;
?>
<ul id="<?php print $this->action; ?>_attribute_enumeration" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.attribute'); ?><span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $attrs = array();
          $attrs['value'] = (isset($attribute_enumerations)
                             ? $attribute_enumerations[0]['AttributeEnumeration']['attribute']
                             : null);
          $attrs['empty'] = true;
          
          print $this->Form->select('attribute',
                                    $vv_supported_attrs,
                                    $attrs);
          
          if($this->Form->isFieldError('attribute')) {
            print $this->Form->error('attribute');
          }
        } else {
          if(!empty($attribute_enumerations[0]['AttributeEnumeration']['attribute'])) {
            print $attribute_enumerations[0]['AttributeEnumeration']['attribute'];
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.dictionaries.1'); ?><span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $attrs = array();
          $attrs['value'] = (isset($attribute_enumerations)
                             ? $attribute_enumerations[0]['AttributeEnumeration']['dictionary_id']
                             : null);
          $attrs['empty'] = true;
          
          print $this->Form->select('dictionary_id',
                                    $vv_available_dictionaries,
                                    $attrs);
          
          if($this->Form->isFieldError('dictionary_id')) {
            print $this->Form->error('dictionary_id');
          }
        } else {
          if(!empty($attribute_enumerations[0]['AttributeEnumeration']['dictionary_id'])) {
            print $vv_available_dictionaries[ $attribute_enumerations[0]['AttributeEnumeration']['dictionary_id'] ];
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.ae.allow_other'); ?></div>
      <div class="field-desc"><?php print _txt('fd.ae.allow_other.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php
      if($e) {
        $args = array();
        $args['class'] = 'mr-1';
        $args['checked'] = (isset($attribute_enumerations[0]['AttributeEnumeration']['allow_other'])
                            && $attribute_enumerations[0]['AttributeEnumeration']['allow_other']);
        print $this->Form->checkbox('allow_other', $args);
        print $this->Form->label('allow_other', _txt('fd.ae.allow_other'));
      } else {
        print (isset($attribute_enumerations[0]['AttributeEnumeration']['allow_other'])
               && $attribute_enumerations[0]['AttributeEnumeration']['allow_other'])
              ? _txt('fd.yes')
              : _txt('fd.no');
      }
      ?>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.status'); ?><span class="required">*</span></div>
      <div class="field-desc"><?php print _txt('fd.status.ae.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($attribute_enumerations)
                           ? $attribute_enumerations[0]['AttributeEnumeration']['status']
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
          print _txt('en.status.susp', null, $attribute_enumerations[0]['AttributeEnumeration']['status']);
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
<?php
  print $this->element("changelog");
