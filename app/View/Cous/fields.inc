<?php
/**
 * COmanage Registry COU Fields
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
 * @since         COmanage Registry v0.2
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
  $args['controller'] = 'cous';
  $args['action'] = 'index';
  if(isset($cur_co)) {
    $args['co'] = $cur_co['Co']['id'];
  }
  $this->Html->addCrumb(_txt('ct.cous.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.cous.1')));
  $this->Html->addCrumb($crumbTxt);
    
  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<ul id="<?php print $this->action; ?>_cou" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print $this->Form->label('name',_txt('fd.name')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : filter_var($cous[0]['Cou']['name'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('description',_txt('fd.desc')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description') : filter_var($cous[0]['Cou']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php
      if($e) {
        if (!empty($this->viewVars['parent_options'] )) {
          print $this->Form->label('parent_id', _txt('fd.parent'));
        } else {
          print _txt('fd.parent');
        }
      }
      ?>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          if (!empty($this->viewVars['parent_options'] )) {
            // Print dropdown selector with COU options
            print $this->Form->select('parent_id', $this->viewVars['parent_options']);
          } else {
            // Do not print empty selector
            print _txt('fd.cou.nopar');
          }
        }
        else {
          print filter_var($cous[0]['Cou']['parent_id'],FILTER_SANITIZE_SPECIAL_CHARS);
        }

        if($this->Form->isFieldError('status')) {
          print $this->Form->error('status');
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ?
          $this->Form->label('configuration_labels', _txt('op.list', array(_txt('ct.configuration_labels.pl'))))
          : _txt('op.list', array(_txt('fd.label')))
        );
        ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.cou.configuration_labels.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      print ($e ? $this->Form->input('configuration_labels', array(
                                                              'type' => 'text',
                                                              'maxlength' => 1024,
                                                            )
      )
        : filter_var($cous[0]['Cou']['configuration_labels'],FILTER_SANITIZE_SPECIAL_CHARS));
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
