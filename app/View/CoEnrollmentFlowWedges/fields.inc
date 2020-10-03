<?php
/**
 * COmanage Registry CO Enrollment Flow Wedges Fields
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
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'edit';
  $args[] = $vv_ef_id;
  $this->Html->addCrumb($vv_ef_name, $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'index';
  $args['coef'] = $vv_ef_id;
  $this->Html->addCrumb(_txt('ct.co_enrollment_flow_wedges.pl'), $args);

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
    print $this->Form->hidden('co_enrollment_flow_id', array('default' => $vv_ef_id)) . "\n";
  }
  
  $l = 1;
?>
<ul id="<?php print $this->action; ?>_co_enrollment_flow_wedge" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('description', _txt('fd.desc')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description') : filter_var($co_enrollment_flow_wedges[0]['CoEnrollmentFlowWedge']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e && $this->action == 'add' ? $this->Form->label('plugin', _txt('fd.plugin')) : _txt('fd.plugin')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.plugin.warn', array(_txt('ct.co_enrollment_flow_wedges.1'))); ?></div>
    </div>
    <div class="field-info">
      <span class="field-info-prefix">
      <?php
        switch($this->action) {
          case 'add':
            print $this->Form->input('plugin');
            break;
          case 'edit':
            print filter_var($co_enrollment_flow_wedges[0]['CoEnrollmentFlowWedge']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            // Send the value as hidden since it can't be changed (but is required by the model)
            print $this->Form->hidden('plugin', array('default' => filter_var($co_enrollment_flow_wedges[0]['CoEnrollmentFlowWedge']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
            break;
          default:
            print filter_var($co_enrollment_flow_wedges[0]['CoEnrollmentFlowWedge']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            break;
        }
      ?>
      </span>

      <?php
        if($this->action == 'edit') {
          // And render a link to the plugin target

          $pl = Inflector::underscore(filter_var($co_enrollment_flow_wedges[0]['CoEnrollmentFlowWedge']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS));
          $plmodel = filter_var($co_enrollment_flow_wedges[0]['CoEnrollmentFlowWedge']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);

          print ' ' . $this->Html->link(_txt('op.config'),
                                             array(
                                               'plugin' => $pl,
                                               'controller' => Inflector::pluralize($pl),
                                               'action' => 'edit',
                                               $co_enrollment_flow_wedges[0][$plmodel]['id']
                                             ),
                                             array('class' => 'configurebutton'));
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_enrollment_flow_wedges) ? $co_enrollment_flow_wedges[0]['CoEnrollmentFlowWedge']['status'] : SuspendableStatusEnum::Active);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.susp'],
                                    $attrs);

          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status', null, $co_enrollment_flow_wedges[0]['CoEnrollmentFlowWedge']['status']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('ordr', _txt('fd.order')) : _txt('fd.order')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('ordr')
                   : filter_var($co_enrollment_flow_wedges[0]['CoEnrollmentFlowWedge']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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