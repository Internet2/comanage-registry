<!--
/**
 * COmanage Registry PrivacyIDEA Authenticator Plugin Fields
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
 * @since         COmanage Registry v4.0.0
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
<ul id="<?php print $this->action; ?>_privacy_idea_authenticator" class="fields form-list form-list-admin">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('server_id', _txt('fd.server')) : _txt('fd.server')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['server_id'])
                           ? $privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['server_id']
                           : null);
        // Since required=true, this will render blank by default but not allow save until a server is selected
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('server_id',
                                    $vv_servers,
                                    $attrs);

          if($this->Form->isFieldError('server_id')) {
            print $this->Form->error('server_id');
          }
        } else {
          if(!empty($privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['server_id'])) {
            print filter_var($vv_servers[ $privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['server_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('validation_server_id', _txt('pl.privacyideaauthenticator.fd.validation_server')) : _txt('pl.privacyideaauthenticator.fd.validation_server')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['validation_server_id'])
                           ? $privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['validation_server_id']
                           : null);
        // Since required=true, this will render blank by default but not allow save until a server is selected
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('validation_server_id',
                                    $vv_servers,
                                    $attrs);

          if($this->Form->isFieldError('validation_server_id')) {
            print $this->Form->error('validation_server_id');
          }
        } else {
          if(!empty($privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['validation_server_id'])) {
            print filter_var($vv_servers[ $privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['validation_server_id'] ], FILTER_SANITIZE_SPECIAL_CHARS);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.privacyideaauthenticator.fd.realm'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('realm') : filter_var($privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['realm'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print $this->Form->label('token_type',_txt('pl.privacyideaauthenticator.fd.token_type'),array('class' => 'fieldTitle'));?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;

        $attrs = array();
        $attrs['value'] = (isset($privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['token_type'])
                           ? $privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['token_type']
                           : null);
        $attrs['empty'] = true;
        
        if($e) {
          print $this->Form->select('token_type',
                                    $cm_texts[ $cm_lang ]['en.privacyideaauthenticator.token_type'],
                                    $attrs);

          if($this->Form->isFieldError('token_type')) {
            print $this->Form->error('token_type');
          }
        } else {
          print _txt('en.privacyideaauthenticator.token_type', null, $privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['token_type']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('identifier_type', _txt('pl.privacyideaauthenticator.fd.identifier_type')) : _txt('pl.privacyideaauthenticator.fd.identifier_type')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (!empty($privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['identifier_type']) 
                           ? $privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['identifier_type']
                           : null);
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('identifier_type',
                                    $vv_identifier_types,
                                    $attrs);

          if($this->Form->isFieldError('identifier_type')) {
            print $this->Form->error('identifier_type');
          }
        } else {
          print _txt('en.identifier.type', null, filter_var($privacy_idea_authenticators[0]['PrivacyIdeaAuthenticator']['identifier_type'],FILTER_SANITIZE_SPECIAL_CHARS));
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
