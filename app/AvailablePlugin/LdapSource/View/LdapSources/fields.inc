<!--
/**
 * COmanage Registry LDAP Source Plugin Fields
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
  $this->Html->addCrumb($ldap_sources[0]['OrgIdentitySource']['description'], $args);

  $crumbTxt = _txt('op.config');
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('org_identity_source_id', array('default' => $vv_oisid)) . "\n";

?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('pl.ldapsource.info'); ?>
  </div>
</div>
<ul id="<?php print $this->action; ?>_ldap_source_target" class="fields form-list form-list-admin">
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.ldapsource.serverurl'); ?> 
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.serverurl.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('serverurl', array('size' => 50)) : filter_var($ldap_sources[0]['LdapSource']['serverurl'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title"><?php print _txt('pl.ldapsource.binddn'); ?></div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.binddn.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('binddn', array('size' => 50)) : filter_var($ldap_sources[0]['LdapSource']['binddn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title"><?php print _txt('pl.ldapsource.password'); ?></div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.password.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('password') : filter_var($ldap_sources[0]['LdapSource']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.ldapsource.basedn'); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.basedn.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('basedn', array('size' => 50)) : filter_var($ldap_sources[0]['LdapSource']['basedn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.ldapsource.key_attribute'); ?>
          <span class="required">*</span>
        </div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.key_attribute.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('key_attribute') : filter_var($ldap_sources[0]['LdapSource']['key_attribute'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title"><?php print _txt('pl.ldapsource.search_filter'); ?></div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.search_filter.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('search_filter', array('size' => 50)) : filter_var($ldap_sources[0]['LdapSource']['search_filter'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title"><?php print _txt('pl.ldapsource.uidattr'); ?></div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.uidattr.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('uid_attr', array('size' => 50)) : filter_var($ldap_sources[0]['LdapSource']['uid_attr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title"><?php print _txt('pl.ldapsource.uidattr_login'); ?></div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.uidattr_login.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('uid_attr_login') : _txt($ldap_sources[0]['LdapSource']['uid_attr'] ? 'fd.yes' : 'fd.no')); ?>
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
