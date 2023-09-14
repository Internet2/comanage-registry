<!--
/**
 * COmanage Registry Env Source Plugin Fields
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
 * @since         COmanage Registry v3.1.0
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
  $args['controller'] = 'org_identity_sources';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.org_identity_sources.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'org_identity_sources';
  $args['action'] = 'edit';
  $args[] = $vv_oisid;
  $this->Html->addCrumb($env_sources[0]['OrgIdentitySource']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('org_identity_source_id', array('default' => $vv_oisid)) . "\n";
?>
<ul id="<?php print $this->action; ?>_env_source" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.envsource.mode.dupe'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (!empty($env_sources[0]['EnvSource']['duplicate_mode']) ? $env_sources[0]['EnvSource']['duplicate_mode'] : "");
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('duplicate_mode',
                                    $cm_texts[ $cm_lang ]['pl.envsource.en.mode.dupe'],
                                    $attrs);

          if($this->Form->isFieldError('duplicate_mode')) {
            print $this->Form->error('duplicate_mode');
          }
        } else {
          print _txt('pl.envsource.en.mode.dupe', null, $env_sources[0]['EnvSource']['duplicate_mode']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('redirect_on_duplicate', _txt('pl.envsource.redirect.dupe')) : _txt('pl.envsource.redirect.dupe')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('redirect_on_duplicate', array('size' => 60))
                   : filter_var($env_sources[0]['EnvSource']['redirect_on_duplicate'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('sp_type', _txt('pl.envsource.sp.type')) : _txt('pl.envsource.sp.type')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
      global $cm_lang, $cm_texts;
      $attrs = array();
      $attrs['value'] = (!empty($env_sources[0]['EnvSource']['sp_type']) ? $env_sources[0]['EnvSource']['sp_type'] : "");
      $attrs['empty'] = false;

      if($e) {
        print $this->Form->select('sp_type',
                                  $cm_texts[ $cm_lang ]['pl.envsource.en.auth.provider'],
                                  $attrs);

        if($this->Form->isFieldError('sp_type')) {
          print $this->Form->error('sp_type');
        }
      } else {
        print _txt('pl.envsource.en.sp.type', null, $env_sources[0]['EnvSource']['sp_type']);
      }
      ?>
    </div>
  </li>
</ul>
<div id="env_attributes" class="field-children table-container">
  <table id="<?php print $this->action; ?>_env_attributes">
    <?php
      $l = 1;
    ?>
    <tbody>
      <tr>
        <th><?php print _txt('fd.attribute'); ?></th>
        <th><?php print _txt('fd.attr.env'); ?></th>
      </tr>
      <?php foreach($vv_available_attributes as $field => $attr): ?>
      <tr class="line<?php print ($l % 2); $l++; ?>">
        <td>
          <?php
            print $attr['label'];
            
            if(isset($attr['required']) && $attr['required']) {
              print "<span class=\"required\">*</span>";
            }
            
            if(!empty($attr['desc'])) {
              print "<br /><span class=\"descr\">" . $attr['desc'] . "</span>";
            }
          ?>
        </td>
        <td>
          <?php
            $args = array();
            $args['size'] = 40;
            
            if(!empty($attr['default'])) {
              $args['default'] = $attr['default'];
            }
            
            if(isset($attr['required']) && $attr['required']) {
              $args['required'] = true;
            }
            
            print $this->Form->input($field, $args);
            
            if(isset($attr['canLogin']) && $attr['canLogin']) {
              $args = array();
              $args['label'] = _txt('fd.identifier.login');
              
              print "<br />" . $this->Form->input($field.'_login', $args);
            }
          ?>
        </td>
      </tr>
      <?php endforeach; // $attrs ?>
      <tr>
        <td><span class="required"><?php print _txt('fd.req'); ?></span></td>
        <td><?php print $this->Form->submit($submit_label); ?></td>
      </tr>
    </tbody>
  </table>
</div>
