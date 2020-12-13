<?php
/**
 * COmanage Registry Server Fields
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
 * @since         COmanage Registry v3.2.0
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
  $args['controller'] = 'servers';
  $args['action'] = 'index';
  if(isset($cur_co)) {
    $args['co'] = $cur_co['Co']['id'];
  }
  $this->Html->addCrumb(_txt('ct.servers.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.servers.1')));
  $this->Html->addCrumb($crumbTxt);
    
  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>

<ul id="<?php print $this->action; ?>_server" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.desc'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description') : filter_var($servers[0]['Server']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('status', _txt('fd.status')) : _txt('fd.status')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($servers) ? $servers[0]['Server']['status'] : SuspendableStatusEnum::Active);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status.susp'],
                                    $attrs);

          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status', null, $servers[0]['Server']['status']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('server_type', _txt('fd.type')) : _txt('fd.type')); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('fd.server.warn'); ?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        
        switch($this->action) {
          case 'add':
            $attrs = array();
            $attrs['value'] = (isset($servers) ? $servers[0]['Server']['server_type'] : "");
            $attrs['empty'] = true;
            
            print $this->Form->select('server_type',
                                      $cm_texts[ $cm_lang ]['en.server'],
                                      $attrs);

            if($this->Form->isFieldError('server')) {
              print $this->Form->error('server');
            }
            break;
          case 'edit':
            // Send the value as hidden since it can't be changed (but is required by the model)
            print $this->Form->hidden('server_type', array('default' => filter_var($servers[0]['Server']['server_type']))) . "\n";
            // Fall through
          default:
            print _txt('en.server', null, $servers[0]['Server']['server_type']);
            break;
        }
        
        if($this->action == 'edit') {
          // Link to the configuration
          
          $smodel = $vv_server_type_models[ $servers[0]['Server']['server_type'] ];
          
          print $this->Html->link(_txt('op.config'),
                                  array(
                                    'controller' => Inflector::tableize($smodel),
                                    'action' => 'edit',
                                    $servers[0][$smodel]['id']
                                  ),
                                  array('class' => 'configurebutton'));
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
