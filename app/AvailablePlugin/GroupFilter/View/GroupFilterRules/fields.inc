<?php
/**
 * COmanage Registry Group Filter Rules Fields
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
  $args['controller'] = 'data_filters';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.data_filters.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'data_filters';
  $args['action'] = 'edit';
  $args[] = $vv_datafilter['id'];
  $this->Html->addCrumb($vv_datafilter['description'], $args);
  
  $args = array();
  $args['plugin'] = 'group_filter';
  $args['controller'] = 'group_filter_rules';
  $args['action'] = 'index';
  $args['groupfilter'] = $vv_groupfilter['id'];
  $this->Html->addCrumb(_txt('ct.group_filter_rules.pl'), $args);
  
  if ($this->action == 'edit') {
    $this->Html->addCrumb(_txt('op.edit'));
  }
  if ($this->action == 'view') {
    $this->Html->addCrumb(_txt('op.view'));
  }
  if ($this->action == 'add') {
    $this->Html->addCrumb(_txt('op.add'));
  }
  
  if($this->action != 'view') {
    // Populate cross references
    print $this->Form->hidden('group_filter_id', array('default' => $vv_groupfilter['id'])) . "\n";
  }
  
  $l = 1;
?>
<div class="table-container">
  <table id="<?php print $this->action; ?>_group_filter_rules">
    <tbody>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('pl.groupfilter.name'); ?></b><font class="required">*</font><br />
          <span class="descr"><?php print _txt('pl.groupfilter.name.desc'); ?></span>
        </td>
        <td>
          <?php print ($e
            ? $this->Form->input('name_pattern')
            : filter_var($group_filter_rules[0]['GroupFilterRule']['name_pattern'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <b><?php print _txt('fd.order'); ?></b><br />
          <span class="descr"><?php print _txt('fd.order.es.desc'); ?></span>
        </td>
        <td>
          <?php print ($e
            ? $this->Form->input('ordr')
            : filter_var($group_filter_rules[0]['GroupFilterRule']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </td>
      </tr>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <strong class="fieldTitle"><?php print _txt('fd.required'); ?></strong><font class="required">*</font><br />
        </td>
        <td>
          <?php
            global $cm_lang, $cm_texts;
            
            $attrs = array();
            $attrs['value'] = (isset($group_filter_rules)
                               ? $group_filter_rules[0]['GroupFilterRule']['required']
                               : RequiredEnum::Required);
            $attrs['empty'] = false;
            
            if($e) {
              print $this->Form->select('required',
                                        $cm_texts[ $cm_lang ]['en.required'],
                                        $attrs);
              
              if($this->Form->isFieldError('required')) {
                print $this->Form->error('required');
              }
            } else {
              print _txt('en.required', null, $group_filter_rules[0]['GroupFilterRule']['required']);
            }
          ?>
        </td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td>
          <em class="required"><?php print _txt('fd.req'); ?></em>
        </td>
        <td>
          <?php
            if($e) {
              print $this->Form->submit($submit_label);
            }
          ?>
        </td>
      </tr>
    </tfoot>
  </table>
</div>

<?php
  print $this->element("changelog");