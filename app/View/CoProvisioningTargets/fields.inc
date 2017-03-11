<?php
/**
 * COmanage Registry CO Provisioning Target Fields
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
 * @since         COmanage Registry v0.8
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
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_provisioning_targets';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_provisioning_targets.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_provisioning_targets.1')));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<?php if(empty($plugins)): ?>
<div class="ui-state-highlight ui-corner-all co-info-topbox">
  <p>
    <span class="ui-icon ui-icon-info co-info"></span>
    <strong><?php print _txt('er.plugin.none', array(_txt('ct.co_provisioning_targets.pl'))); ?></strong>
  </p>
</div>
<br />
<?php else: /* plugins */ ?>
<table id="<?php print $this->action; ?>_co_provisioning_target" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('description', _txt('fd.desc')) : _txt('fd.desc')); ?>
        </strong><span class="required">*</span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('description') : filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <strong class="fieldTitle">
          <?php print ($e && $this->action == 'add' ? $this->Form->label('plugin', _txt('fd.plugin')) : _txt('fd.plugin')); ?>
        </strong><span class="required">*</span><br />
        <span class="descr"><?php print _txt('fd.plugin.warn', array(_txt('ct.co_provisioning_targets.1'))); ?></span>
      </td>
      <td>
        <?php
          switch($this->action) {
            case 'add':
              print $this->Form->input('plugin');
              break;
            case 'edit':
              print '<strong>' . filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS) . '</strong>';
              // Send the value as hidden since it can't be changed (but is required by the model)
              print $this->Form->hidden('plugin', array('default' => filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
              break;
            default:
              print filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
              break;
          }
          
          if($this->action == 'edit') {
            // And render a link to the plugin target
            
            $pl = Inflector::underscore(filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS));
            $plmodel = "Co" . filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS) . "Target";
            
            print ' ' . $this->Html->link(_txt('op.config'),
                                               array(
                                                 'plugin' => $pl,
                                                 'controller' => 'co_' . $pl . '_targets',
                                                 'action' => 'edit',
                                                 $co_provisioning_targets[0][$plmodel]['id'],
                                                 'co' => $cur_co['Co']['id']
                                               ),
                                               array('class' => 'configurebutton'));
          }
        ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('provisioning_co_group_id', _txt('fd.prov.group')) : _txt('fd.prov.group')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.prov.group.desc'); ?></span>
      </td>
      <td>
        <?php
          $attrs = array();
          $attrs['value'] = (isset($co_provisioning_targets[0]['CoProvisioningTarget']['provision_co_group_id'])
                             ? $co_provisioning_targets[0]['CoProvisioningTarget']['provision_co_group_id']
                             : null);
          $attrs['empty'] = true;

          if($e) {
            print $this->Form->select('provision_co_group_id',
                                      $vv_available_groups,
                                      $attrs);

            if($this->Form->isFieldError('provision_co_group_id')) {
              print $this->Form->error('provision_co_group_id');
            }
          } else {
            print $vv_available_groups[ $co_provisioning_targets[0]['CoProvisioningTarget']['provision_co_group_id'] ];
          }
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('en.status.prov.desc'); ?></span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($co_provisioning_targets) ? $co_provisioning_targets[0]['CoProvisioningTarget']['status'] : ProvisionerStatusEnum::AutomaticMode);
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('status',
                                      $cm_texts[ $cm_lang ]['en.status.prov'],
                                      $attrs);
            
            if($this->Form->isFieldError('status')) {
              print $this->Form->error('status');
            }
          } else {
            print _txt('en.status', null, $co_provisioning_targets[0]['CoProvisioningTarget']['status']);
          }
        ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <strong class="fieldTitle">
          <?php print ($e ? $this->Form->label('ordr', _txt('fd.order')) : _txt('fd.order')); ?>
        </strong><br />
        <span class="descr"><?php print _txt('fd.order.prov.desc'); ?></span>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('ordr')
                     : filter_var($co_provisioning_targets[0]['CoProvisioningTarget']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr>
      <td>
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
      </td>
      <td>
        <?php
          if($e) {
            print $this->Form->submit($submit_label);
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
<?php endif; /* plugins */
