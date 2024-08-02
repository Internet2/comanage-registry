<?php
/**
 * COmanage Registry Duplicate Check Enroller Plugin Plugin Fields
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
 * @since         COmanage Registry v4.3.0
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
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flows.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flows';
  $args['action'] = 'edit';
  $args[] = $duplicate_check_enrollers[0]['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $this->Html->addCrumb($duplicate_check_enrollers[0]['CoEnrollmentFlowWedge']['CoEnrollmentFlow']['name'], $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'index';
  $args['coef'] = $duplicate_check_enrollers[0]['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $args[] = $duplicate_check_enrollers[0]['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flow_wedges.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'edit';
  $args[] = $duplicate_check_enrollers[0]['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb($duplicate_check_enrollers[0]['CoEnrollmentFlowWedge']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);

  if($this->action != 'view') {
    // Populate cross references
    print $this->Form->hidden('co_enrollment_flow_wedge_id', array('default' => $duplicate_check_enrollers[0]["DuplicateCheckEnroller"]["co_enrollment_flow_wedge_id"])) . PHP_EOL;
  }
?>


<ul id="<?php print $this->action; ?>_duplicate_check_enrollers" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php
        print ($e ? $this->Form->label('env_remote_user', _txt('fd.duplicate_check_enrollers.env_remote_user'))
          : _txt('fd.duplicate_check_enrollers.env_remote_user'));
        ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.duplicate_check_enrollers.env_remote_user.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      print ($e ? $this->Form->text('env_remote_user', array('class' => 'focusFirst'))
        : filter_var($duplicate_check_enrollers[0]['DuplicateCheckEnroller']['env_remote_user'],FILTER_SANITIZE_STRING));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
      <?php
      if($e) {
        print $this->Form->label('type', _txt('fd.duplicate_check_enrollers.identifier_type'));
      } else {
        print _txt('fd.duplicate_check_enrollers.identifier_type');
      }
      ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.duplicate_check_enrollers.identifier_type.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      $attrs = array();
      $attrs['value'] = (!empty($duplicate_check_enrollers[0]['DuplicateCheckEnroller']['identifier_type']) ? $duplicate_check_enrollers[0]['DuplicateCheckEnroller']['identifier_type'] : "");
      $attrs['empty'] = false;

      if($e) {
        if(!empty($identifiers[0]['CoProvisioningTarget']['id'])) {
          // Don't allow the type to be changed, but send the type as hidden since
          // it is required by the model

          print filter_var($vv_available_types[ $duplicate_check_enrollers[0]['DuplicateCheckEnroller']['identifier_type'] ],FILTER_SANITIZE_SPECIAL_CHARS);
          print $this->Form->hidden('identifier_type',
                                    array('default' =>
                                            filter_var($duplicate_check_enrollers[0]['DuplicateCheckEnroller']['identifier_type'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
        } else {
          print $this->Form->select('identifier_type',
                                    $vv_available_types,
                                    $attrs);

          if($this->Form->isFieldError('identifier_type')) {
            print $this->Form->error('identifier_type');
          }
        }
      } else {
        print filter_var($vv_available_types[ $duplicate_check_enrollers[0]['DuplicateCheckEnroller']['identifier_type'] ],FILTER_SANITIZE_SPECIAL_CHARS);;
      }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php
          print ($e ? $this->Form->label('redirect_url', _txt('fd.duplicate_check_enrollers.redirect_url'))
                    : _txt('fd.duplicate_check_enrollers.redirect_url'));
        ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.duplicate_check_enrollers.redirect_url.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      print ($e ? $this->Form->text(
        'redirect_url',
        array(
          'size' => 60,
          'maxlength' => 1024,
        )
      )
                : filter_var($duplicate_check_enrollers[0]['DuplicateCheckEnroller']['redirect_url'],FILTER_SANITIZE_URL));
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

