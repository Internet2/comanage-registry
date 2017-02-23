<!--
/**
 * COmanage Registry LDAP Identifier Validator Plugin Fields
 * XXX unclear why this isn't in /LdapIdentifierValidator*s*/field.inc
 *
 * Copyright (C) 2016 SCG
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
 * @copyright     Copyright (C) 2016 SCG
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
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
    <strong><?php print _txt('pl.ldapsource.info'); ?></strong>
  </p>
</div>
<br />
<table id="<?php print $this->action; ?>_ldap_identifier_validator" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td style="width:50%">
        <strong class="fieldTitle"><?php print _txt('pl.ldapidentifier.serverurl'); ?></strong><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.ldapidentifier.serverurl.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('serverurl', array('size' => 50)) : filter_var($ldap_identifier_validators[0]['LdapIdentifierValidator']['serverurl'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.ldapidentifier.binddn'); ?></strong><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.ldapidentifier.binddn.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('binddn', array('size' => 50)) : filter_var($ldap_identifier_validators[0]['LdapIdentifierValidator']['binddn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.ldapidentifier.password'); ?></strong><br />
        <font class="desc"><?php print _txt('pl.ldapidentifier.password.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('password') : filter_var($ldap_identifier_validators[0]['LdapIdentifierValidator']['password'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.ldapidentifier.basedn'); ?></strong><br />
        <font class="desc"><?php print _txt('pl.ldapidentifier.basedn.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('basedn', array('size' => 50)) : filter_var($ldap_identifier_validators[0]['LdapIdentifierValidator']['basedn'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <strong class="fieldTitle"><?php print _txt('pl.ldapidentifier.filter'); ?></strong><font class="required">*</font><br />
        <font class="desc"><?php print _txt('pl.ldapidentifier.filter.desc'); ?></font>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('filter') : filter_var($ldap_identifier_validators[0]['LdapIdentifierValidator']['filter'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><font class="required"><?php print _txt('fd.req'); ?></font></i><br />
      </td>
      <td>
        <?php
          if($e) {
            print $this->Form->submit($submit_label);
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
