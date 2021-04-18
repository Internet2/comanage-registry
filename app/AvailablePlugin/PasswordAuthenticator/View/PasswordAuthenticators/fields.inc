<!--
/**
 * COmanage Registry Password Authenticator Plugin Fields
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
  
  print $this->Form->hidden('authenticator_id', array('default' => $vv_authid)) . "\n";

?>
<script type="text/javascript">
  function fields_update_gadgets() {
    // Hide or show gadgets according to current state
    var source = document.getElementById('PasswordAuthenticatorPasswordSource').value;
    
    if(source == "<?php print PasswordAuthPasswordSourceEnum::AutoGenerate; ?>") {
      $("#PasswordAuthenticatorMaxLength").closest("li").show('fade');
      $("#PasswordAuthenticatorMinLength").closest("li").hide('fade');
      $("#crypt_checkbox").show('fade');
      $("#crypt_text").hide('fade');
      $("#PasswordAuthenticatorEnableSsr").closest("li").hide('fade');
    } else if(source == "<?php print PasswordAuthPasswordSourceEnum::External; ?>") {
      $("#PasswordAuthenticatorMaxLength").closest("li").hide('fade');
      $("#PasswordAuthenticatorMinLength").closest("li").hide('fade');
      $("#crypt_checkbox").show('fade');
      $("#crypt_text").hide('fade');
      $("#PasswordAuthenticatorEnableSsr").closest("li").hide('fade');
    } else {
      $("#PasswordAuthenticatorMaxLength").closest("li").show('fade');
      $("#PasswordAuthenticatorMinLength").closest("li").show('fade');
      $("#crypt_checkbox").hide('fade');
      $("#crypt_text").show('fade');
      $("#PasswordAuthenticatorEnableSsr").closest("li").show('fade');
    }
    
    var ssr = document.getElementById('PasswordAuthenticatorEnableSsr').checked;
    
    if(ssr && source == "<?php print PasswordAuthPasswordSourceEnum::SelfSelect; ?>") {
      $("#ssr-url").show('fade');
      $("#PasswordAuthenticatorRedirectOnSuccessSsr").closest("li").show('fade');
      $("#PasswordAuthenticatorSsrValidity").closest("li").show('fade');
      $("#PasswordAuthenticatorCoMessageTemplateId").closest("li").show('fade');
    } else {
      $("#ssr-url").hide('fade');
      $("#PasswordAuthenticatorRedirectOnSuccessSsr").closest("li").hide('fade')
      $("#PasswordAuthenticatorSsrValidity").closest("li").hide('fade');
      $("#PasswordAuthenticatorCoMessageTemplateId").closest("li").hide('fade');;
    }
  }
  
  function js_local_onload() {
    fields_update_gadgets();
  }
</script>
<ul id="<?php print $this->action; ?>_password_authenticator" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title"><?php print $this->Form->label('password_source',_txt('pl.passwordauthenticator.password_source'),array('class' => 'fieldTitle'));?></div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;

        $attrs = array();
        $attrs['value'] = (isset($password_authenticators[0]['PasswordAuthenticator']['password_source'])
                           ? $password_authenticators[0]['PasswordAuthenticator']['password_source']
                           : PasswordAuthPasswordSourceEnum::SelfSelect);
        $attrs['empty'] = false;
        $attrs['onChange'] = 'fields_update_gadgets()';
        
        if($e) {
          print $this->Form->select('password_source',
                                    $cm_texts[ $cm_lang ]['en.passwordauthenticator.password_source'],
                                    $attrs);

          if($this->Form->isFieldError('password_source')) {
            print $this->Form->error('password_source');
          }
        } else {
          print _txt('en.passwordauthenticator.password_source', null, $password_authenticators[0]['PasswordAuthenticator']['password_source']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.passwordauthenticator.minlen'); ?></div>
      <div class="field-desc"><?php print _txt('pl.passwordauthenticator.minlen.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('min_length') : filter_var($password_authenticators[0]['PasswordAuthenticator']['min_length'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.passwordauthenticator.maxlen'); ?></div>
      <div class="field-desc"><?php print _txt('pl.passwordauthenticator.maxlen.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('max_length') : filter_var($password_authenticators[0]['PasswordAuthenticator']['max_length'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.passwordauthenticator.hash.crypt'); ?></div>
      <div class="field-desc"><?php print _txt('pl.passwordauthenticator.hash.crypt.desc'); ?></div>
    </div>
    <div class="field-info">
      <div id="crypt_checkbox">
      <?php
        // Crypt format is required for SelfSelect since that's what we use during password change
        if($e) print $this->Form->input('format_crypt_php');
      ?>
      </div>
      <div id="crypt_text">
        <?php print _txt('fd.yes'); ?>
      </div>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.passwordauthenticator.hash.ssha'); ?></div>
      <div class="field-desc"><?php print _txt('pl.passwordauthenticator.hash.ssha.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php if($e) print $this->Form->input('format_sha1_ldap'); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.passwordauthenticator.hash.plain'); ?></div>
      <div class="field-desc"><?php print _txt('pl.passwordauthenticator.hash.plain.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php if($e) print $this->Form->input('format_plaintext'); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.passwordauthenticator.ssr'); ?></div>
      <div class="field-desc"><?php print _txt('pl.passwordauthenticator.ssr.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php if($e) print $this->Form->input('enable_ssr', array('onChange' => 'fields_update_gadgets()')); ?>
    </div>
  </li>
  <li id="ssr-url">
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.passwordauthenticator.ssr.url'); ?></div>
    </div>
    <div class="field-info">
      <?php if(!empty($vv_ssr_initiation_url)) print $this->Html->link($vv_ssr_initiation_url, $vv_ssr_initiation_url); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.passwordauthenticator.ssr.validity'); ?></div>
      <div class="field-desc"><?php print _txt('pl.passwordauthenticator.ssr.validity.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('ssr_validity', array('default' => 10)) : filter_var($password_authenticators[0]['PasswordAuthenticator']['ssr_validity'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.co_message_templates.1'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.passwordauthenticator.ssr.mt.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (!empty($password_authenticators[0]['PasswordAuthenticator']['co_message_template_id'])
                          ? $password_authenticators[0]['PasswordAuthenticator']['co_message_template_id']
                          : null);
        // This should be true, but since we don't validate based on multiple
        // fields yet (required only if SSR is enabled) this will prevent an
        // empty value
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('co_message_template_id',
            (!empty($vv_message_templates[MessageTemplateEnum::Authenticator])
                    ? $vv_message_templates[MessageTemplateEnum::Authenticator]
                    : array()),
            $attrs);
          
          if($this->Form->isFieldError('co_message_template_id')) {
            print $this->Form->error('co_message_template_id');
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('pl.passwordauthenticator.ssr.redirect'); ?></div>
      <div class="field-desc"><?php print _txt('pl.passwordauthenticator.ssr.redirect.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('redirect_on_success_ssr') : filter_var($password_authenticators[0]['PasswordAuthenticator']['redirect_on_success_ssr'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
