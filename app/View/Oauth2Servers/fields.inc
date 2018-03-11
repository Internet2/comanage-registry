<?php
/**
 * COmanage Registry OAuth2 Server Fields
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
  $args['co'] = $oauth2_servers[0]['Server']['co_id'];
  $this->Html->addCrumb(_txt('ct.servers.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'servers';
  $args['action'] = 'edit';
  $args[] = $oauth2_servers[0]['Server']['id'];
  $this->Html->addCrumb($oauth2_servers[0]['Server']['description'], $args);
  
  $this->Html->addCrumb(_txt('op.config-a', array($oauth2_servers[0]['Server']['description'])));
  
  print $this->Form->hidden('server_id', array('default' => $oauth2_servers[0]['Server']['id'])) . "\n";
?>
<ul id="<?php print $this->action; ?>_oauth2_server" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.server.oauth2.redirect'); ?>
      </div>
    </div>
    <div class="field-info">
      <input type="text" readonly="true" size="60" value="<?php print $this->Html->url($vv_redirect_uri, true); ?>" />
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('serverurl',_txt('fd.server.url')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('serverurl', array('class' => 'focusFirst')) : filter_var($oauth2_servers[0]['Oauth2Server']['serverurl'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('clientid',_txt('fd.server.oauth2.clientid')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('clientid') : filter_var($oauth2_servers[0]['Oauth2Server']['clientid'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('client_secret',_txt('fd.server.oauth2.client_secret')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('client_secret') : filter_var($oauth2_servers[0]['Oauth2Server']['client_secret'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('access_grant_type', _txt('fd.server.oauth2.grant_type')) : _txt('fd.server.oauth2.grant_type')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($oauth2_servers) ? $oauth2_servers[0]['Oauth2Server']['access_grant_type'] : Oauth2GrantEnum::AuthorizationCode);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('access_grant_type',
                                    $cm_texts[ $cm_lang ]['en.server.oauth2.grant'],
                                    $attrs);

          if($this->Form->isFieldError('access_grant_type')) {
            print $this->Form->error('access_grant_type');
          }
        } else {
          print _txt('en.server.oauth2.grant', null, $oauth2_servers[0]['Oauth2Server']['access_grant_type']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('scope',_txt('fd.server.oauth2.scope')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('scope') : filter_var($oauth2_servers[0]['Oauth2Server']['scope'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('fd.server.oauth2.access_token'); ?>
        <div class="field-desc"><?php print _txt('fd.server.oauth2.access_token.desc'); ?></div>
      </div>
    </div>
    <div class="field-info">
      <?php
        print _txt(!empty($oauth2_servers[0]['Oauth2Server']['refresh_token']) ? 'fd.set' : 'fd.set.not');
        
        if(!empty($oauth2_servers[0]['Oauth2Server']['serverurl'])) {
          print $this->Html->link(_txt('op.server.oauth2.token'),
                                  array(
                                    'action' => 'token',
                                    $oauth2_servers[0]['Oauth2Server']['id']
                                  ),
                                  array('class' => 'runbutton'));
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