<?php
/**
 * COmanage Registry HTTP Server Fields
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
  $args['co'] = $http_servers[0]['Server']['co_id'];
  $this->Html->addCrumb(_txt('ct.servers.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'servers';
  $args['action'] = 'edit';
  $args[] = $http_servers[0]['Server']['id'];
  $this->Html->addCrumb($http_servers[0]['Server']['description'], $args);
  
  $this->Html->addCrumb(_txt('op.config-a', array($http_servers[0]['Server']['description'])));
  
  print $this->Form->hidden('server_id', array('default' => $http_servers[0]['Server']['id'])) . "\n";
?>
<script type="text/javascript">
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    
    if(document.getElementById('HttpServerSslVerifyPeer').checked) {
      $("#HttpServerSslVerifyHost").closest("li").show('fade');
    } else {
      $("#HttpServerSslVerifyHost").closest("li").hide('fade');
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }
  <!-- JS specific to these fields -->
</script>
<ul id="<?php print $this->action; ?>_http_server" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('serverurl',_txt('fd.server.url')); ?><span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('serverurl', array('class' => 'focusFirst')) : filter_var($http_servers[0]['HttpServer']['serverurl'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('username',_txt('fd.server.username')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('username') : filter_var($http_servers[0]['HttpServer']['username'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('password',_txt('fd.password')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('password') : filter_var($http_servers[0]['HttpServer']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('ssl_verify_peer', _txt('fd.server.http.ssl_verify_peer')) : _txt('fd.server.http.ssl_verify_peer')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.server.http.ssl_verify_peer.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php
        print ($e
               ? $this->Form->input('ssl_verify_peer',
                                    array('onChange' => 'fields_update_gadgets();',
                                          'default' => true))
               : ($http_servers[0]['HttpServer']['ssl_verify_peer']
                  ? _txt('fd.yes') : _txt('fd.no')));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('ssl_verify_host', _txt('fd.server.http.ssl_verify_host')) : _txt('fd.server.http.ssl_verify_host')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.server.http.ssl_verify_host.desc'); ?></div>
    </div>
    <div class="field-info checkbox">
      <?php
        print ($e
               ? $this->Form->input('ssl_verify_host',
                                    array('default' => true))
               : ($http_servers[0]['HttpServer']['ssl_verify_host']
                  ? _txt('fd.yes') : _txt('fd.no')));
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