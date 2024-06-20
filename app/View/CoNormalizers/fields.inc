<?php
/**
 * COmanage Registry CO Identifier Validator Fields
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>
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
  $args['controller'] = 'co_normalizers';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_normalizers.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_normalizers.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  $l = 1;
?>
<ul id="<?php print $this->action; ?>_co_normalizers" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print _txt('fd.desc'); ?> 
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description', array('size' => '60', 'class' => 'focusFirst'))
                      : filter_var($co_normalizers[0]['CoNormalizer']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e && $this->action == 'add' ? $this->Form->label('plugin', _txt('fd.plugin')) : _txt('fd.plugin')); ?><span class="required">*</span><br />
      </div>
      <div class="field-desc"><?php print _txt('fd.plugin.warn', array(_txt('ct.co_normalizers.1'))); ?></div>
    </div>
    <div class="field-info">
      <?php
        switch($this->action) {
          case 'add':
            $attrs = array();
            // Since this is add, value should always be null...
            $attrs['value'] = (isset($co_normalizers[0]['CoNormalizer']['plugin'])
                               ? $co_normalizers[0]['CoNormalizer']['plugin']
                               : null);
            $attrs['empty'] = false;
            
            print $this->Form->select('plugin',
                                      $vv_plugins,
                                      $attrs);
            
            if($this->Form->isFieldError('plugin')) {
              print $this->Form->error('plugin');
            }
          break;
        case 'edit':
          print filter_var($co_normalizers[0]['CoNormalizer']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
          // Send the value as hidden since it can't be changed (but is required by the model)
          print $this->Form->hidden('plugin', array('default' => filter_var($co_normalizers[0]['CoNormalizer']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
          
          // Create a direct link to configuration if this plugin is instantiated
          $plugin = filter_var($co_normalizers[0]['CoNormalizer']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
          $pl = Inflector::underscore($plugin);
          $plm = Inflector::tableize($plugin);
          
          if($vv_inst_plugins[$plugin]) {
            print $this->Html->link(_txt('op.config'),
              array(
                'plugin' => $pl,
                'controller' => $plm,
                'action' => 'edit',
                $co_normalizers[0][$plugin]['id'],
                'ivid' => $co_normalizers[0]['CoNormalizer']['id']
              ),
              array('class' => 'editbutton')) . "\n";
          }
          break;
        default:
          if(!empty($co_normalizers[0]['CoNormalizer']['plugin'])) {
            print $vv_plugins[ $co_normalizers[0]['CoNormalizer']['plugin'] ];
          }
          break;
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.status'); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($co_normalizers[0]['CoNormalizer']['status'])
                           ? $co_normalizers[0]['CoNormalizer']['status']
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
          if(!empty($co_normalizers[0]['CoNormalizer']['status'])) {
            print _txt('en.status', null, $co_normalizers[0]['CoNormalizer']['status']);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('ordr', _txt('fd.order')) : _txt('fd.order')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.order.es.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
        ? $this->Form->input('ordr')
        : filter_var($co_normalizers[0]['CoNormalizer']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name"></div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; // $e ?>
</ul>
<?php
  print $this->element("changelog");
