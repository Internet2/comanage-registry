<?php
/**
 * COmanage Registry SSH Keys Fields
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
 * @package       registry
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if($this->action == "add" && $permissions['add'])
    $e = true;
  
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);
  
  print $this->Form->hidden('ssh_key_authenticator_id',
                            array('default' => $vv_authenticator['SshKeyAuthenticator']['id'])) . "\n";
  print $this->Form->hidden('co_person_id', array('default' => $vv_co_person['CoPerson']['id'])) . "\n";
  
  // Add breadcrumbs
  print $this->element("coCrumb", array('authenticator' => 'SshKey'));
?>
<!-- As of v3.2.0 (CO-1616), we no longer allow manual editing of SSH Keys -->
<?php if($this->action == 'view'): ?>
<ul id="<?php print $this->action; ?>_ssh_key" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print _txt('pl.sshkeyauthenticator.fd.comment'); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('comment', array('class' => 'focusFirst')) : filter_var($ssh_keys[0]['SshKey']['comment'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('pl.sshkeyauthenticator.fd.type'); ?>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs['value'] = (isset($ssh_keys) ? $ssh_keys[0]['SshKey']['type'] : SshKeyTypeEnum::DSA);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('type',
                                    $cm_texts[ $cm_lang ]['pl.sshkeyauthenticator.fd.type'],
                                    $attrs);

          if($this->Form->isFieldError('type')) {
            print $this->Form->error('type');
          }
        } else {
          print filter_var($cm_texts[ $cm_lang ]['pl.sshkeyauthenticator.en.sshkey.type'][ $ssh_keys[0]['SshKey']['type'] ],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <?php print _txt('pl.sshkeyauthenticator.fd.skey'); ?>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('skey')
                   : filter_var($ssh_keys[0]['SshKey']['skey'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
<?php elseif($this->action == 'add' && $e): // view ?>
  <ul id="<?php print $this->action; ?>_ssh_key_upload" class="fields form-list">
    <li>
      <div class="field-name">
        <?php print _txt('op.upload.new', array(_txt('ct.ssh_keys.1'))); ?>
      </div>
      <div class="field-info">
        <?php
          // Close the previous form
          // XXX do we still need this?
          // XXX Shouldn't do this; instead, pass this page through canvas to avoid a global form wrapper and
          // then define each form separately in this file
          print $this->Form->end();

          // Start a new form (this will get closed by Standard/add/edit.ctp)
          print $this->Form->create('SshKey', array('type' => 'file',
                                                    'url' => array('action' => 'addKeyFile')));
          
          // Since we started a new form, we need to re-insert the hidden values

          print $this->Form->hidden('authenticator_id',
                                    array('default' => $vv_authenticator['SshKeyAuthenticator']['authenticator_id'])) . "\n";
          print $this->Form->hidden('ssh_key_authenticator_id',
                                    array('default' => $vv_authenticator['SshKeyAuthenticator']['id'])) . "\n";
          print $this->Form->hidden('co_person_id', array('default' => $vv_co_person['CoPerson']['id'])) . "\n";
          
          print $this->Form->file('SshKey.keyFile');
        ?>
      </div>
    </li>
    <li class="fields-submit">
      <div class="field-name"></div>
      <div class="field-info">
        <?php print $this->Form->submit(_txt('op.upload')); ?>
      </div>
    </li>
  </ul>
<?php endif; // view/add ?>

<?php
  print $this->element("changelog");
