<?php
/**
 * COmanage Registry SQL Server Fields
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
    return false;
  
  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'servers';
  $args['action'] = 'index';
  $args['co'] = $sql_servers[0]['Server']['co_id'];
  $this->Html->addCrumb(_txt('ct.servers.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'servers';
  $args['action'] = 'edit';
  $args[] = $sql_servers[0]['Server']['id'];
  $this->Html->addCrumb($sql_servers[0]['Server']['description'], $args);
  
  $this->Html->addCrumb(_txt('op.config-a', array($sql_servers[0]['Server']['description'])));
  
  print $this->Form->hidden('server_id', array('default' => $sql_servers[0]['Server']['id'])) . "\n";
?>
<ul id="<?php print $this->action; ?>_sql_server" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print $this->Form->label('hostname',_txt('fd.server.hostname')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('hostname', array('class' => 'focusFirst')) : filter_var($sql_servers[0]['SqlServer']['hostname'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('type', _txt('fd.type')) : _txt('fd.type')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($sql_servers) ? $sql_servers[0]['SqlServer']['type'] : "");
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('type',
                                    $cm_texts[ $cm_lang ]['en.server.sql'],
                                    $attrs);

          if($this->Form->isFieldError('type')) {
            print $this->Form->error('type');
          }
        } else {
          print _txt('en.server.sql', null, $sql_servers[0]['SqlServer']['type']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('username',_txt('fd.server.username')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('username') : filter_var($sql_servers[0]['SqlServer']['username'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('password',_txt('fd.password')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('password') : filter_var($sql_servers[0]['SqlServer']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('databas',_txt('fd.server.sql.database')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('databas') : filter_var($sql_servers[0]['SqlServer']['databas'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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