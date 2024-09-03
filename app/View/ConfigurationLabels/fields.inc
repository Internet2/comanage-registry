<?php
/**
 * COmanage Registry Labels Fields
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
 * @since         COmanage Registry v4.4.0
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
  $this->Html->addCrumb(_txt('ct.configuration_labels.pl'), array('controller' => 'configuration_labels', 'action' => 'index', 'co' => $cur_co['Co']['id']));
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.configuration_labels.1')));
  $this->Html->addCrumb($crumbTxt);

  // Inject the CO ID
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id']));

?>

  <script type="text/javascript">
    function togglePicker(e) {
      if(e.code == 'ArrowDown') {
        $('#ConfigurationLabelColor')[0].click()
      }
    }

    $(function() {
      const colorInput = document.getElementById("ConfigurationLabelColor");
      colorInput.addEventListener("keyup", togglePicker);

      Coloris({
        el: '.coloris',
        swatches: [
          '#264653',
          '#2a9d8f',
          '#e9c46a',
          '#f4a261',
          '#e76f51',
          '#d62828',
          '#023e8a',
          '#0077b6',
          '#0096c7',
          '#00b4d8',
          '#48cae4'
        ],
        closeButton: true,
        defaultColor: '#000000'
      });
    });
  </script>

<ul id="<?php print $this->action; ?>_labels" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('label', _txt('fd.label')) : _txt('fd.label')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.configuration_label.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        print ($e ? $this->Form->input('label', array(
                                                    'type' => 'text',
                                                    'class'     => 'focusFirst',
                                                    'maxlength' => 64,
                                                  )
                                      )
                  : filter_var($configuration_labels[0]['ConfigurationLabel']['label'],FILTER_SANITIZE_SPECIAL_CHARS));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ( $e ? $this->Form->label('color', _txt('fd.color')) : _txt('fd.color'));?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.color.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print $e ? $this->Form->input('color', array(
        'type'      => 'text',
        'div'       => array('class' => 'circle'),
        'class'     => 'coloris label-color-field',
        'maxlength' => 9,
      )) : $configuration_labels[0]['ConfigurationLabel']['color']; ?>
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
  print $this->element('changelog');