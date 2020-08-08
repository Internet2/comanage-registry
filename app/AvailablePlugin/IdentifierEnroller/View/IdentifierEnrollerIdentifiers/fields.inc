<?php
/**
 * COmanage Registry Identifier Enroller Identifiers Fields
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
  $args[] = $vv_identifier_enroller['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $this->Html->addCrumb($vv_identifier_enroller['CoEnrollmentFlowWedge']['CoEnrollmentFlow']['name'], $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'index';
  $args['coef'] = $vv_identifier_enroller['CoEnrollmentFlowWedge']['co_enrollment_flow_id'];
  $args[] = $vv_identifier_enroller['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb(_txt('ct.co_enrollment_flow_wedges.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_enrollment_flow_wedges';
  $args['action'] = 'edit';
  $args[] = $vv_identifier_enroller['CoEnrollmentFlowWedge']['id'];
  $this->Html->addCrumb($vv_identifier_enroller['CoEnrollmentFlowWedge']['description'], $args);

  $crumbTxt = _txt('ct.identifier_enroller_identifiers.pl');
  $this->Html->addCrumb($crumbTxt);

  if($this->action != 'view') {
    // Populate cross references
    print $this->Form->hidden('identifier_enroller_id', array('default' => $vv_identifier_enroller['IdentifierEnroller']['id'])) . "\n";
  }
?>
<ul id="<?php print $this->action; ?>_identifier_enroller_identifier" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('label', _txt('fd.label')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.identifierenroller.label.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('label') : filter_var($identifier_enroller_identifiers[0]['IdentifierEnrollerIdentifier']['label'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('description', _txt('fd.desc')); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.identifierenroller.description.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description') : filter_var($identifier_enroller_identifiers[0]['IdentifierEnrollerIdentifier']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.identifierenroller.idtype'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($identifier_enroller_identifiers[0]['IdentifierEnrollerIdentifier']['identifier_type'])
                           ? $identifier_enroller_identifiers[0]['IdentifierEnrollerIdentifier']['identifier_type']
                           : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('identifier_type',
                                    $vv_identifier_types,
                                    $attrs);

          if($this->Form->isFieldError('identifier_type')) {
            print $this->Form->error('identifier_type');
          }
        } else {
          if(!empty($identifier_enroller_identifiers[0]['IdentifierEnrollerIdentifier']['identifier_type'])) {
            print $vv_identifier_types[ $identifier_enroller_identifiers[0]['IdentifierEnrollerIdentifier']['identifier_type'] ];
          }
        }
      ?>
    </div>
  </li>  
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('default_env', _txt('fd.ea.default_env')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.ea.default_env.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('default_env') : filter_var($identifier_enroller_identifiers[0]['IdentifierEnrollerIdentifier']['default_env'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
                   : filter_var($identifier_enroller_identifiers[0]['IdentifierEnrollerIdentifier']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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