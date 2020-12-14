<!--
/**
 * COmanage Registry PrivacyIDEA Authenticator Plugin TOTP Token Fields
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
  
  print $this->Form->hidden('privacy_idea_authenticator_id',
                            array('default' => $vv_authenticator['PrivacyIdeaAuthenticator']['id'])) . "\n";
  print $this->Form->hidden('co_person_id', array('default' => $vv_co_person['CoPerson']['id'])) . "\n";
  
  if(!empty($vv_token_info['qr_data'])) {
    print $this->Form->hidden('qr_data', array('default' => $vv_token_info['qr_data']));
  }
  
  if(!empty($vv_token_info['serial'])) {
    print $this->Form->hidden('serial', array('default' => $vv_token_info['serial']));
  }
  
  if(!empty($vv_on_finish_url)) {
    print $this->Form->hidden('onFinish', array('default' => $vv_on_finish_url));
  }
  
  // Add breadcrumbs
  print $this->element("coCrumb", array('authenticator' => 'PrivacyIdea'));
?>
  
<ul id="<?php print $this->action; ?>_totp_token" class="fields form-list form-list-admin">
<?php if(!empty($vv_token_info['qr_data'])): ?>
  <li>
    <div class="field-info">
      <p>
        <?php print _txt('pl.privacyideaauthenticator.totp.step1'); ?>
      </p>
      <img src="<?php print $vv_token_info['qr_data']; ?>" alt="<?php print _txt('pl.privacyideaauthenticator.alt.google'); ?>">
    </div>
  </li>
  <li>
    <div class="field-info">
      <p>
        <?php print _txt('pl.privacyideaauthenticator.totp.step2'); ?>
      </p>
      <?php print $this->Form->input('totp_value'); ?>
    </div>
  </li>
  <li class="fields-submit">
    <div class="field-name">
      <span class="required"><?php print _txt('fd.req'); ?></span>
    </div>
    <div class="field-info">
      <?php print $this->Form->submit($submit_label); ?>
    </div>
  </li>
<?php elseif($this->action == 'view'): ?>
  <li>
    <div class="field-name">
      <?php print _txt('pl.privacyideaauthenticator.fd.serial'); ?>
    </div>
    <div class="field-info">
      <?php
        print filter_var($totp_tokens[0]['TotpToken']['serial'],FILTER_SANITIZE_SPECIAL_CHARS);
        
        /* It'd be nice to deep link into the admin UI to directly view/manage
           a token, but PrivacyIDEA expects an authorization token with each
           request, which apparently doesn't get passed through even if you're
           logged in in another tab or window. Then, even if you start a new
           session, the UI loses the deep link you were trying to get to.
        if(!empty($vv_server_url)) {
          print $this->Html->link(
            _txt('op.manage'),
            $vv_server_url . "/#!/token/details/" . $totp_tokens[0]['TotpToken']['serial'],
            array('class' => 'linkbutton')
          );
        }*/
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('pl.privacyideaauthenticator.token.confirmed'); ?>
    </div>
    <div class="field-info">
      <?php print $totp_tokens[0]['TotpToken']['confirmed'] ? _txt('fd.yes') : _txt('fd.no'); ?>
    </div>
  </li>
<?php endif; // vv_qr_data, view ?>
</ul>
