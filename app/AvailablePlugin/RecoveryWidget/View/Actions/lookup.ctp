<?php
/**
 * COmanage Registry Recovery Widget Lookup View
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

  $params = array('title' => $vv_title);
  print $this->element("pageTitle", $params);
  
  $options = array(
    'type' => 'post',
    'url' => array(
      'plugin'            => 'recovery_widget',
      'controller'        => 'actions',
      'action'            => 'lookup',
      'recoverywidgetid'  => $this->request->params['named']['recoverywidgetid'],
      'task'              => $this->request->params['named']['task']
    )
  );
  
  print $this->Form->create(false, $options);
?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <span class="co-info-topbox-text">
  <?php 
    // For the password reset action, we pass in a link to direct password change
    if(!empty($vv_authenticator_change_url)) {
      print _txt("pl.recoverywidget.lookup.$vv_task.info", array($this->Html->url($vv_authenticator_change_url)));
    } else {
      print _txt("pl.recoverywidget.lookup.$vv_task.info");
    }
  ?>
  </span>
</div>

<ul id="<?php print $this->action; ?>_recovery" class="fields form-list">
  <li>
    <div class="field-name vtop">
      <div class="field-title">
        <?php print $this->Form->label('q',_txt('pl.recoverywidget.lookup.q')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('q', array('label' => false)); ?>
    </div>
  </li>
  <li class="fields-submit">
    <div class="field-name">
      <span class="required"><?php print _txt('fd.req'); ?></span>
    </div>
    <div class="field-info">
      <?php print $this->Form->submit(_txt('op.search')); ?>
    </div>
  </li>
</ul>
<?php
  print $this->Form->end();