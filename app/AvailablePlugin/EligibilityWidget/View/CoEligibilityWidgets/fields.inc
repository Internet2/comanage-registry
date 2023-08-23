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
  $args[] = $co_eligibility_widgets[0]['CoDashboardWidget']['co_dashboard_id'];
  $this->Html->addCrumb(_txt('ct.co_dashboards.1'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_dashboard_widgets';
  $args['action'] = 'index';
  $args['codashboard'] = $co_eligibility_widgets[0]['CoDashboardWidget']['co_dashboard_id'];
  $this->Html->addCrumb(_txt('ct.co_dashboard_widgets.1'), $args);

  $crumbTxt = _txt('op.' . $this->action . '-a', array(filter_var($co_eligibility_widgets[0]['CoDashboardWidget']['description'],FILTER_SANITIZE_SPECIAL_CHARS)));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_dashboard_widget_id', array('default' => $vv_dwid)) . "\n";
?>


<div id="<?php print $this->action; ?>_co_eligibility_widget" class="explorerContainer">
  <div id="coOrgIdExplorer" class="personExplorer">
    <ul>
      <li id="fields-name" class="fieldGroup">
        <a href="#tabs-name" class="fieldGroupName">
          <em class="material-icons">indeterminate_check_box</em>
          <?php print _txt('ct.eligibility_widgets.1'); ?>
        </a>

        <div id="names-container" class="fields">
          <ul  id="tabs-name-fields" class="fields form-list">

          <li>
            <div class="field-name">
              <div class="field-title">
                <?php print ($e ? $this->Form->label("mode", _txt('pl.eligibilitywidget.fd.mode')) : _txt('pl.eligibilitywidget.fd.mode')); ?>
              </div>
              <div class="field-desc">
                <?php print _txt('pl.eligibilitywidget.fd.mode.desc'); ?>
              </div>
            </div>
            <div class="field-info">
              <?php
              $attrs['value'] = (isset($vv_config['CoEligibilityWidget']["mode"]) ? $vv_config['CoEligibilityWidget']["mode"] : "");
              $attrs['empty'] = false;

              if($e) {
                print $this->Form->select("mode",
                                          RegistrationModeEnum::modes,
                                          $attrs);

                if($this->Form->isFieldError("mode")) {
                  print $this->Form->error("mode");
                }
              } else {
                print _txt('pl.eligibilitywidget.fd.mode', null, filter_var($vv_config["mode"],FILTER_SANITIZE_SPECIAL_CHARS));
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
        </div>
      </li><!-- fields-name -->

      <?php
      if($this->action != "add"
        && !is_null($co_eligibility_widgets[0]["CoEligibilityWidget"]["mode"])) {
        if($co_eligibility_widgets[0]["CoEligibilityWidget"]["mode"] == RegistrationModeEnum::OIS) {
          $args = array(
            'edit'         => $e,
            'model'        => 'CoEligibilityWidget',
            'model_param'  => 'cewid',
            'mvep_model'   => 'OisRegistration',
            'mvep_field'   => 'description'
          );

          print $this->element('mvep', $args);
        } elseif ($co_eligibility_widgets[0]["CoEligibilityWidget"]["mode"] == RegistrationModeEnum::COU) {
//          $args = array(
//            'edit'         => $e,
//            'model'        => 'CoEligibilityWidget',
//            'model_param'  => 'cewid',
//            'mvep_model'   => 'CouRegistration',
//            'mvep_field'   => 'org_identity_source_id'
//          );
//
//          print $this->element('mvep', $args);
          print '<div class="co-info-topbox">';
          print '<em class="material-icons">info</em>';
          print _txt('er.df.context');
          print '</div>' . PHP_EOL;
        }

      }
      ?>

  </div> <!-- personExplorer -->
<!--  --><?php //print $this->element("changelog"); ?>
</div> <!-- explorerContainer -->
