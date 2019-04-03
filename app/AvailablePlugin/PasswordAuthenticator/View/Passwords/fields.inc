<!--
/**
 * COmanage Registry Password Authenticator Plugin Password Fields
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
  
  if($this->action == "manage" && $permissions['manage'])
    $e = true;

  print $this->Form->hidden('password_authenticator_id',
                            array('default' => $vv_authenticator['PasswordAuthenticator']['id'])) . "\n";
  print $this->Form->hidden('co_person_id', array('default' => $vv_co_person['CoPerson']['id'])) . "\n";
  
  // For the moment this is not configurable and we only support SSHA (salted SHA 1).
  // If we support more than one format concurrently, we'll need to do some clever
  // rewriting (maybe just override manage()?) to write multiple records for one password.
  // XXX maybe login in PasswordAuthenticator handles this instead?
  //print $this->Form->hidden('password_type', array('default' => PasswordEncodingEnum::SSHA)) . "\n";
  
  // Add breadcrumbs
  print $this->element("coCrumb", array('authenticator' => 'Password'));
?>

<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php
    $maxlen = isset($vv_authenticator['PasswordAuthenticator']['max_length'])
              ? $vv_authenticator['PasswordAuthenticator']['max_length']
              : 64;
    $minlen = isset($vv_authenticator['PasswordAuthenticator']['min_length'])
              ? $vv_authenticator['PasswordAuthenticator']['min_length']
              : 8;
  
    print _txt('pl.passwordauthenticator.info', array($minlen, $maxlen));
  ?>
</div>

<ul id="<?php print $this->action; ?>_password" class="fields form-list form-list-admin">
  <!-- If we are editing our own password and one already exists, we must provide it
       (otherwise we're probably an admin -->
  <?php if(!empty($vv_current)
           && ($vv_co_person['CoPerson']['id'] == $this->Session->read('Auth.User.co_person_id'))): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.passwordauthenticator.password.current'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('passwordc', array('type' => 'password')) : ""); ?>
    </div>
  </li>
  <?php endif; // vv_current ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.passwordauthenticator.password.new'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('password') : ""); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.passwordauthenticator.password.again'); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('password2', array('type' => 'password')) : ""); ?>
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
