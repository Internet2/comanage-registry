<!--
/**
 * COmanage Registry Namespace Assigner Settings Fields
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

  $crumbTxt = _txt('ct.namespace_assigner_settings.pl');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_id', array('default' => $namespace_assigner_settings[0]['NamespaceAssignerSetting']['co_id'])) . "\n";
?>
<?php if(empty($vv_servers)): ?>
<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php print _txt('er.server.none', array(_txt('ct.http_servers.1'))); ?>
</div>
<?php else: // vv_servers ?>
<ul id="<?php print $this->action; ?>_namespace_assigner_settings" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('server_id', _txt('fd.server')) : _txt('fd.server')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($namespace_assigner_settings[0]['NamespaceAssignerSetting']['server_id'])
                           ? $namespace_assigner_settings[0]['NamespaceAssignerSetting']['server_id']
                           : null);
        // Since required=true, this will render blank by default but not allow save until a server is selected
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('server_id',
                                    $vv_servers,
                                    $attrs);

          if($this->Form->isFieldError('server_id')) {
            print $this->Form->error('server_id');
          }
        } else {
          if(!empty($namespace_assigner_settings[0]['NamespaceAssignerSetting']['server_id'])) {
            print filter_var($vv_servers[ $namespace_assigner_settings[0]['NamespaceAssignerSetting']['server_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <span class="field-name">
      <?php
      if($e) {
        print $this->Form->label('name_type', _txt('pl.namespaceassigner.type.name'));
      } else {
        print _txt('fd.type');
      }
      ?>
      <span class="required">*</span>
    </span>
    <span class="field-info">
      <?php
      $attrs = array();
      $attrs['value'] = (isset($namespace_assigner_settings) ? $namespace_assigner_settings[0]['NamespaceAssignerSetting']['name_type'] : "");
      $attrs['empty'] = true;

      print $this->Form->select('name_type',
        $vv_available_name_types,
        $attrs);

      if($this->Form->isFieldError('name_type')) {
        print $this->Form->error('name_type');
      }
      ?>
    </span>
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
<?php endif; // vv_servers ?>