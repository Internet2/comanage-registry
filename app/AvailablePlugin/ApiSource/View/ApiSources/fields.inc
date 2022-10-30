<!--
/**
 * COmanage Registry API Source Plugin Fields
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
 * @since         COmanage Registry v3.3.0
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
  $this->Html->addCrumb($api_sources[0]['OrgIdentitySource']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('org_identity_source_id', array('default' => $vv_oisid)) . "\n";
?>
<script type="text/javascript">
  <!-- JS specific to these fields -->
  
  function poll_update_gadgets() {
    var pollserver = document.getElementById('ApiSourcePollMode').value;
    
    if(pollserver == '<?php print ApiSourcePollModeEnum::Kafka; ?>') {
      document.getElementById('kafkadiv').style.display = "block";
    } else {
      document.getElementById('kafkadiv').style.display = "none";
    }
  }
  
  function js_local_onload() {
    poll_update_gadgets();
  }
</script>
<ul id="<?php print $this->action; ?>_api_source" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.apisource.mode.push'); ?>
      </div>
    </div>
<?php if(!empty($vv_api_endpoint)): ?>
    <div class="co-info-topbox">
      <i class="material-icons">info</i>
      <?php print _txt('pl.apisource.info', array($vv_api_endpoint)); ?>
    </div>
<?php endif; // $vv_api_endpoint ?>
    <ul class="field-children">
      <li>
        <div class="field-name">
          <div class="field-title">
            <?php print _txt('ct.api_users.1'); ?><br />
            <span class="descr"><?php print _txt('pl.apisource.api_user.desc'); ?></span>
          </div>
        </div>
        <div class="field-info">
          <?php
            $attrs = array();
            $attrs['value'] = (isset($api_sources[0]['ApiSource']['api_user_id'])
                               ? $api_sources[0]['ApiSource']['api_user_id']
                               : "");
            $attrs['empty'] = true;
            
            if($e) {
              print $this->Form->select('api_user_id',
                                        $vv_api_users,
                                        $attrs);

              if($this->Form->isFieldError('api_user_id')) {
                print $this->Form->error('api_user_id');
              }
            } else {
              print filter_var($vv_api_users[ $api_sources[0]['ApiSource']['api_user_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
            }
          ?>
        </div>
      </li>
    </ul>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.apisource.mode.poll'); ?>
      </div>
    </div>
    <ul class="field-children">
      <li>
        <div class="field-name">
          <div class="field-title">
            <?php print ($e ? $this->Form->label('poll_mode', _txt('pl.apisource.mode.poll')) : _txt('pl.apisource.mode.poll')); ?><br />
            <span class="descr"><?php print _txt('pl.apisource.mode.poll.desc'); ?></span>
          </div>
        </div>
        <div class="field-info">
          <?php
            global $cm_lang, $cm_texts;
            $attrs = array();
            $attrs['value'] = (!empty($api_sources[0]['ApiSource']['poll_mode']) ? $api_sources[0]['ApiSource']['poll_mode'] : "");
            $attrs['empty'] = true;
            $attrs['onChange'] = 'poll_update_gadgets()';

            if($e) {
              print $this->Form->select('poll_mode',
                                        $cm_texts[ $cm_lang ]['pl.apisource.en.mode.poll'],
                                        $attrs);

              if($this->Form->isFieldError('poll_mode')) {
                print $this->Form->error('poll_mode');
              }
            } else {
              print _txt('pl.apisource.en.mode.poll', null, $api_sources[0]['ApiSource']['poll_mode']);
            }
          ?>
        </div>
      </li>
      <div id="kafkadiv" class="subfield" style="display:none">
      <li>
        <div class="field-name">
          <?php print ($e ? $this->Form->label('server_id', _txt('fd.server')) : _txt('fd.server')); ?>
          <span class="required">*</span>
        </div>
        <div class="field-info">
          <?php if(empty($vv_servers)): ?>
          <div class="co-info-topbox">
            <i class="material-icons">info</i>
            <?php print _txt('pl.apisource.servers.none'); ?>
          </div>
          <?php else: // vv_servers ?>
          <?php
            $attrs = array();
            $attrs['value'] = (isset($api_sources[0]['ApiSource']['kafka_server_id'])
                               ? $api_sources[0]['ApiSource']['kafka_server_id']
                               : null);
            // Since required=true, this will render blank by default but not allow save until a server is selected
            $attrs['empty'] = true;

            if($e) {
              print $this->Form->select('kafka_server_id',
                                        $vv_servers,
                                        $attrs);

              if($this->Form->isFieldError('kafka_server_id')) {
                print $this->Form->error('kafka_server_id');
              }
            } else {
              if(!empty($api_sources[0]['ApiSource']['kafka_server_id'])) {
                print filter_var($vv_servers[ $api_sources[0]['ApiSource']['kafka_server_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
              }
            }
          ?>
        <?php endif; // vv_servers ?>
        </div>
      </li>
      </div>
    </ul>
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
