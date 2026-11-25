<?php
/**
 * COmanage Registry SLIP Attacments Add View
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

  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'index',
    'co' => $cur_co['Co']['id']
  );
  $this->Html->addCrumb(_txt('me.population'), $args);

  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'canvas',
    $vv_slip_proofing['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(generateCn($vv_slip_proofing['ClaimantCoPerson']['PrimaryName']), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'index',
    'spid' => $vv_slip_proofing['SlipProofing']['slip_plugin_id'],
    'coperson' => $vv_slip_proofing['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(_txt('ct.slip_proofings.pl'), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'view',
    $vv_slip_proofing['SlipProofing']['id']
  );
  $this->Html->addCrumb((string)$vv_slip_proofing['SlipProofing']['id'], $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_attachments',
    'action' => 'index',
    'sprid' => $vv_slip_proofing['SlipProofing']['id']
  );
  $this->Html->addCrumb(_txt('ct.slip_attachments.pl'), $args);

  $this->Html->addCrumb($title_for_layout);
  
  // Determine if fields are editable
  $e = false;
  
  if($permissions['add'])
    $e = true;
  
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;
    
  print $this->element("pageTitleAndButtons", $params);

  // $submit_label = _txt('op.add');

  $args = array();
  $args['type'] = 'file';
  $args['inputDefaults']['label'] = false;
  $args['inputDefaults']['div'] = false;

  print $this->Form->create($req, $args);

  print $this->Form->hidden('slip_proofing_id',
                             array('default' => $this->request->params['named']['sprid'])) . "\n";
?>

<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('pl.slipplugin.upload.mime.warning'); ?>
  </div>
</div>

<?php if($e): ?>
  <ul id="add_slip_attachment_upload" class="fields form-list">
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print $this->Form->label('description', _txt('fd.description')); ?>
          <span class="required">*</span>
        </div>
      </div>
      <div class="field-info">
        <?php print $this->Form->input('description'); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <?php print _txt('op.upload.new', array(_txt('ct.slip_attachments.1'))); ?>
        <span class="required">*</span>
      </div>
      <div class="field-info">
        <?php
          print $this->Form->file('SlipAttachment.file_contents');
        ?>
      </div>
    </li>
    <li class="fields-submit">
      <div class="field-name"></div>
      <div class="field-info">
        <?php print $this->Form->submit(_txt('op.upload')); ?>
      </div>
    </li>
  </ul>
<?php endif; ?>

<?php print $this->Form->end(); ?>
