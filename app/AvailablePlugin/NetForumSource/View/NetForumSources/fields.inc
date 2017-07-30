<!--
/**
 * COmanage Registry NetFORUM Source Plugin Fields
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
 * @since         COmanage Registry v2.0.0
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
  $this->Html->addCrumb($net_forum_sources[0]['OrgIdentitySource']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('org_identity_source_id', array('default' => $vv_oisid)) . "\n";

?>
<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php print _txt('pl.netforumsource.info'); ?>
</div>
<ul id="<?php print $this->action; ?>_net_forum_source" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.netforumsource.serverurl'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.netforumsource.serverurl.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('serverurl', array('size' => 50)) : filter_var($net_forum_sources[0]['NetForumSource']['serverurl'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.netforumsource.username'); ?></div>
      <div class="field-desc"><?php print _txt('pl.netforumsource.username.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('username', array('size' => 50)) : filter_var($net_forum_sources[0]['NetForumSource']['username'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.netforumsource.password'); ?></div>
      <div class="field-desc"><?php print _txt('pl.netforumsource.password.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('password') : filter_var($net_forum_sources[0]['NetForumSource']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.netforumsource.querycmts'); ?></div>
      <div class="field-desc"><?php print _txt('pl.netforumsource.querycmts.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('query_committees')
                   : ($net_forum_sources[0]['NetForumSource']['query_committees']
                      ? _txt('fd.yes') : _txt('fd.no'))); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.netforumsource.queryevents'); ?></div>
      <div class="field-desc"><?php print _txt('pl.netforumsource.queryevents.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('query_events')
                   : ($net_forum_sources[0]['NetForumSource']['query_events']
                      ? _txt('fd.yes') : _txt('fd.no'))); ?>
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
