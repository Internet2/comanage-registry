<?php
/**
 * COmanage Registry CO Group Nestings Fields
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
 * @since         COmanage Registry v3.3.0
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
  $args['controller'] = 'co_groups';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_groups.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_groups';
  $args['action'] = 'edit';
  $args[] = $vv_parent_group['CoGroup']['id'];
  $this->Html->addCrumb($vv_parent_group['CoGroup']['name'], $args);
  
  $this->Html->addCrumb(_txt('op.'.$this->action.'-a', array(_txt('ct.co_group_nestings.1'))));

  if($this->action != 'view') {
    // Populate cross references
    print $this->Form->hidden('target_co_group_id', array('default' => $vv_parent_group['CoGroup']['id'])) . "\n";
  }
?>
<ul id="<?php print $this->action; ?>_co_group_nestings" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print _txt('fd.co_group.target.1'); ?>
    </div>
    <div class="field-info">
      <?php print filter_var($vv_parent_group['CoGroup']['name'], FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('co_group_id', _txt('ct.co_group_nestings.1')); ?>
    </div>
    <div class="field-info">
      <?php
        if(!empty($vv_available_groups)) {
          $attrs = array();
          // Since we don't support edit, we don't set $attrs['value'] to the current value
          $attrs['empty'] = false;

          print $this->Form->select('co_group_id',
                                    $vv_available_groups,
                                    $attrs);

          if($this->Form->isFieldError('co_group_id')) {
            print $this->Form->error('co_group_id');
          }
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