<?php
/**
 * COmanage Registry SLIP Authenticator Binding Confirmation View
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.slip_bindings.pl'));

  $this->Html->addCrumb($title_for_layout);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;
    
  print $this->element("pageTitleAndButtons", $params);

  if(!empty($this->plugin)) {
    if(file_exists(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc")) {
      include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc");
    } elseif(file_exists(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc")) {
      include(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc");
    }
  } else {
    if(file_exists(APP . "View/" . $model . "/tabs.inc")) {
      include(APP . "View/" . $model . "/tabs.inc");
    }
  }

  $submit_label = _txt('op.confirm');
?>
<?php if(empty($slip_binding)
         || empty($slip_binding['SlipBinding']['code'])
         || ($slip_binding['SlipBinding']['status'] != SlipBindingStatusEnum::PendingVerification)): ?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('er.slipplugin.bind', array($this->request->pass[0])); ?>
  </div>
</div>
<?php else: // slip_binding ?>
<?php
  print $this->Form->create(
    $req,
    array(
      'inputDefaults' => array(
        'label' => false,
        'div' => false
      )
    )
  );
  
  // Set up the hidden values

  print $this->Form->hidden('slip_binding_id',
                            array('default' => $slip_binding['SlipBinding']['id'])). "\n";
  // We populate slip_proofing_id only as a convenience for redirect construction
  print $this->Form->hidden('slip_proofing_id',
                            array('default' => $slip_binding['SlipBinding']['slip_proofing_id'])). "\n";
?>
<ul id="<?php print $this->action; ?>_slip_bindings" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('code', _txt('pl.slipplugin.authenticated_identifier')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print filter_var($slip_binding['SlipBinding']['authenticated_identifier'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('code', _txt('pl.slipplugin.code')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print $this->Form->input('code'); ?>
    </div>
  </li>
  <li class="fields-submit">
    <div class="field-name">
      <span class="required"><?php print _txt('fd.req'); ?></span>
    </div>
    <div class="field-info">
      <?php print $this->Form->submit($submit_label); ?>
    </div>
  </li>
</ul>
<?php
  print $this->Form->end();
?>
<?php endif; // slip_binding