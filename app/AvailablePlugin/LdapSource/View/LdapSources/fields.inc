<!--
/**
 * COmanage Registry LDAP Source Plugin Fields
 *
 * Copyright (C) 2016 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2016 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
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
  
  print $this->Form->hidden('org_identity_source_id', array('default' => $vv_oisid)) . "\n";

?>
<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php print _txt('pl.ldapsource.info'); ?>
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
        <?php print ($e ? $this->Form->input('serverurl', array('size' => 50)) : Sanitize::html($ldap_sources[0]['LdapSource']['serverurl'])); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title"><?php print _txt('pl.ldapsource.binddn'); ?></div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.binddn.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('binddn', array('size' => 50)) : Sanitize::html($ldap_sources[0]['LdapSource']['binddn'])); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title"><?php print _txt('pl.ldapsource.password'); ?></div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.password.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('password') : Sanitize::html($ldap_sources[0]['LdapSource']['password'])); ?>
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
        <?php print ($e ? $this->Form->input('basedn', array('size' => 50)) : Sanitize::html($ldap_sources[0]['LdapSource']['basedn'])); ?>
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
        <?php print ($e ? $this->Form->input('key_attribute') : Sanitize::html($ldap_sources[0]['LdapSource']['key_attribute'])); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title"><?php print _txt('pl.ldapsource.search_filter'); ?></div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.search_filter.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('search_filter', array('size' => 50)) : Sanitize::html($ldap_sources[0]['LdapSource']['search_filter'])); ?>
      </div>
    </li>
    <li>
      <div class="field-name">
        <div class="field-title"><?php print _txt('pl.ldapsource.uidattr'); ?></div>
        <div class="field-desc"><?php print _txt('pl.ldapsource.uidattr.desc'); ?></div>
      </div>
      <div class="field-info">
        <?php print ($e ? $this->Form->input('uid_attr', array('size' => 50)) : Sanitize::html($ldap_sources[0]['LdapSource']['uid_attr'])); ?>
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
