<!--
/**
 * COmanage Registry Fiddle Enrollers Plugin Fields
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
 * @since         COmanage Registry v4.4.0
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
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'edit';
  $args[] = $fiddle_enrollers[0]['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $this->Html->addCrumb($fiddle_enrollers[0]['CoEnrollmentFlowWedge']['CoEnrollmentFlow']['name'], $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'index';
  $args['coef'] = $fiddle_enrollers[0]['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $args[] = $fiddle_enrollers[0]['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flow_wedges.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'edit';
  $args[] = $fiddle_enrollers[0]['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb($fiddle_enrollers[0]['CoEnrollmentFlowWedge']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);

  if($this->action != 'view') {
    // Populate cross references
    print $this->Form->hidden('co_enrollment_flow_wedge_id', array('default' => $fiddle_enrollers[0]['FiddleEnroller']['co_enrollment_flow_wedge_id'])) . "\n";
  }
?>
<ul id="<?php print $this->action; ?>_fiddle_enrollers" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.fiddleenroller.copy_approver_to_manager'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('copy_approver_to_manager')
                   : (isset($fiddle_enrollers[0]['FiddleEnrollers']['copy_approver_to_manager'])
                      && $fiddle_enrollers[0]['FiddleEnrollers']['copy_approver_to_manager']
                      ? _txt('fd.yes') : _txt('fd.no')));
            print ' ' . $this->Form->label('copy_approver_to_manager',_txt('pl.fiddleenroller.copy_approver_to_manager'));
      ?>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.fiddleenroller.copy_approver_to_sponsor'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php print ($e
                   ? $this->Form->input('copy_approver_to_sponsor')
                   : (isset($fiddle_enrollers[0]['FiddleEnrollers']['copy_approver_to_sponsor'])
                      && $fiddle_enrollers[0]['FiddleEnrollers']['copy_approver_to_sponsor']
                      ? _txt('fd.yes') : _txt('fd.no')));
            print ' ' . $this->Form->label('copy_approver_to_sponsor',_txt('pl.fiddleenroller.copy_approver_to_sponsor'));
      ?>
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