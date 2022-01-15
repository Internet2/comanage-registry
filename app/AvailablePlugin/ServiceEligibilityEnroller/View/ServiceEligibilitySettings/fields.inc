<!--
/**
 * COmanage Registry Service Eligibility Settings Fields
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.1.0
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
  $args['controller'] = 'co_dashboards';
  $args['action'] = 'configuration';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.configuration'), $args);

  $crumbTxt = _txt('ct.service_eligibility_settings.pl');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $service_eligibility_settings[0]['ServiceEligibilitySetting']['co_id'])) . "\n";
?>
<ul id="<?php print $this->action; ?>_service_eligibility_settings" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.serviceeligibilityenroller.allow_multiple'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        print $this->Form->input('allow_multiple');
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.serviceeligibilityenroller.require_selection'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        print $this->Form->input('require_selection');
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
