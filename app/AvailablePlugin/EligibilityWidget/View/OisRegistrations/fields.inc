<?php
/**
 * COmanage Registry CO Eligibility Widgets Fields
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
$args['controller'] = 'co_dashboards';
$args['action'] = 'index';
$args['co'] = $cur_co['Co']['id'];
$this->Html->addCrumb(_txt('ct.co_dashboards.pl'), $args);

$args = array();
$args['plugin'] = null;
$args['controller'] = 'co_dashboards';
$args['action'] = 'edit';
$args[] = $co_eligibility_widgets[0]["CoDashboardWidget"]["co_dashboard_id"];
$this->Html->addCrumb(_txt('ct.co_dashboards.1'), $args);

$args = array();
$args['plugin'] = null;
$args['controller'] = 'co_dashboard_widgets';
$args['action'] = 'index';
$args['codashboard'] = $co_eligibility_widgets[0]["CoDashboardWidget"]["id"];
$this->Html->addCrumb(_txt('ct.co_dashboard_widgets.1'), $args);


$args = array();
$args['plugin'] = 'eligibility_widget';
$args['controller'] = 'co_eligibility_widgets';
$args['action'] = 'edit';
$args[] = $co_eligibility_widgets[0]["CoEligibilityWidget"]["id"];
$this->Html->addCrumb(filter_var($co_eligibility_widgets[0]["CoDashboardWidget"]["description"],FILTER_SANITIZE_SPECIAL_CHARS), $args);

// Action crumb
if($this->action == "add") {
  $crumbTxt = _txt('op.add');
} elseif ($this->action == 'edit') {
  $crumbTxt = _txt('op.edit');
} else {
  $crumbTxt = _txt('op.view');
}
$this->Html->addCrumb($crumbTxt);

print $this->Form->hidden('co_eligibility_widget_id', array('default' => $co_eligibility_widgets[0]["CoEligibilityWidget"]["id"])) . PHP_EOL;
?>


<ul id="<?php print $this->action; ?>_ois_registration" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('org_identity_source_id', _txt('ct.org_identity_sources.1')) : _txt('ct.org_identity_source.1')); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.ois_registration.ois.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      global $cm_lang, $cm_texts;

      $attrs = array();
      $attrs['value'] = !empty($ois_registrations[0]["OisRegistration"]['org_identity_source_id'])
        ? $ois_registrations[0]["OisRegistration"]['org_identity_source_id']
        : null;
      $attrs['empty'] = true;

      foreach($vv_ois_list as $ois_id => $ois_desc) {
        if(in_array($ois_id, $vv_ois_list_used)
           && $attrs['value'] != $ois_id) {
          unset($vv_ois_list[$ois_id]);
        }
      }

      if($e) {
        print $this->Form->select('org_identity_source_id',
                                  $vv_ois_list,
                                  $attrs);

        if($this->Form->isFieldError('org_identity_source_id')) {
          print $this->Form->error('org_identity_source_id');
        }
      } else {
        print !empty($ois_registrations[0]["OisRegistration"]['org_identity_source_id']) ?
              $vv_ois_list[ $ois_registrations[0]["OisRegistration"]['org_identity_source_id']]
             : "";
      }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('description', _txt('fd.description')) : _txt('fd.description')); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.ois_registration.desc.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          print $this->Form->input('description');
        } elseif(!empty($ois_registrations[0]["OisRegistration"]['description'])) {
          print filter_var($ois_registrations[0]["OisRegistration"]['description'],FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
          print "";
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('ordr', _txt('fd.order')) : _txt('fd.order')); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.ois_registration.order.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
        ? $this->Form->input('ordr')
        : filter_var($ois_registrations[0]['OisRegistraion']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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

