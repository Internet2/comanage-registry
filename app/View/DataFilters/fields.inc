<?php
/**
 * COmanage Registry Data Filters Fields
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
 * @since         COmanage Registry v3.3.0
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
  $args['controller'] = 'data_filters';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.data_filters.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.data_filters.1')));
  $this->Html->addCrumb($crumbTxt);
  
  // Populate CO ID
  print $this->Form->hidden('DataFilter.co_id', array('default' => $cur_co['Co']['id'])). "\n";
  
  $l = 1;
?>
  
<?php if(empty($plugins)): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('er.plugin.none', array(_txt('ct.data_filters.pl'))); ?>
  </div>
<?php else: /* plugins */ ?>
  <ul id="<?php print $this->action; ?>_data_filter" class="fields form-list form-list-admin">
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.desc'); ?>
          <span class="required">*</span>
        </div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('description') : filter_var($data_filters[0]['DataFilter']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.plugin'); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('fd.plugin.warn', array(_txt('ct.data_filters.1'))); ?></div>
      </div>
      <div class="field-info">
        <?php
          switch($this->action) {
            case 'add':
              print $this->Form->input('plugin');
              break;
            case 'edit':
              print $this->Form->input('plugin', array('disabled' => true));
              // We need to re-include the value as hidden since it can't be changed (but is required by the model)
              print $this->Form->hidden('plugin',
                                        array('default' => filter_var($data_filters[0]['DataFilter']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
              break;
            default:
              print filter_var($data_filters[0]['DataFilter']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
              break;
          }

          if($this->action == 'edit') {
            // And render a link to the plugin target
            
            $plmodel = filter_var($data_filters[0]['DataFilter']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            $pl = Inflector::underscore($plmodel);
            $plm = Inflector::tableize($plmodel);

            print $this->Html->link(_txt('op.config'),
                                    array(
                                      'plugin' => $pl,
                                      'controller' => $plm,
                                      'action' => 'edit',
                                      $data_filters[0][$plmodel]['id']
                                    ),
                                    array('class' => 'configurebutton'));
          }
        ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('fd.status'); ?>
          <span class="required">*</span>
        </div>
      </div>
      <div class="field-info">
        <?php
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = (isset($data_filters[0]['DataFilter']['status'])
                             ? $data_filters[0]['DataFilter']['status']
                             : SuspendableStatusEnum::Active);
          $attrs['empty'] = false;

          if($e) {
            print $this->Form->select('status',
                                      $cm_texts[ $cm_lang ]['en.status.susp'],
                                      $attrs);

            if($this->Form->isFieldError('status')) {
              print $this->Form->error('status');
            }
          } else {
            print _txt('en.status', null, $data_filters[0]['DataFilter']['status']);
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
<?php endif; /* plugins */
  print $this->element("changelog");