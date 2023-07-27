<!--
/**
 * COmanage Registry LDAP Identifier Validator Plugin Fields
 * XXX unclear why this isn't in /LdapIdentifierValidator*s*/field.inc
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
  $args['controller'] = 'co_identifier_validators';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_identifier_validators.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_identifier_validators';
  $args['action'] = 'edit';
  $args[] = $vv_ivid;
  $this->Html->addCrumb(_txt('ct.co_identifier_validators.1'), $args);  
  
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.ldap_identifier_validators.1')));
  $this->Html->addCrumb($crumbTxt);
  
  print $this->Form->hidden('co_identifier_validator_id', array('default' => $vv_ivid)) . "\n";

  // XXX lots of duplication across the various LDAP plugins...
?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('pl.ldapsource.info'); ?>
  </div>
</div>
<ul id="<?php print $this->action; ?>_ldap_identifier_validator" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.ldapidentifier.serverurl'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.ldapidentifier.serverurl.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('serverurl', array('size' => 50)) : filter_var($ldap_identifier_validators[0]['LdapIdentifierValidator']['serverurl'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.ldapidentifier.binddn'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.ldapidentifier.binddn.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('binddn', array('size' => 50)) : filter_var($ldap_identifier_validators[0]['LdapIdentifierValidator']['binddn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.ldapidentifier.password'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.ldapidentifier.password.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('password') : filter_var($ldap_identifier_validators[0]['LdapIdentifierValidator']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.ldapidentifier.basedn'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.ldapidentifier.basedn.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('basedn', array('size' => 50)) : filter_var($ldap_identifier_validators[0]['LdapIdentifierValidator']['basedn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.ldapidentifier.filter'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.ldapidentifier.filter.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('filter') : filter_var($ldap_identifier_validators[0]['LdapIdentifierValidator']['filter'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if($e): ?>
    <li>
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
