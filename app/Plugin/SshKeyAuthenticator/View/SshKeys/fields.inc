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

  // As of v3.3.0 (CO-1826) this template is only used for view and not for add

  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$permissions['view']) {
    return(false);
  }
  
  // Add breadcrumbs
  print $this->element("coCrumb", array('authenticator' => 'SshKey'));
?>
<?php if($this->action == 'view'): ?>
<ul id="<?php print $this->action; ?>_ssh_key" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print _txt('pl.sshkeyauthenticator.fd.comment'); ?>
    </div>
    <div class="field-info">
      <?php print (filter_var($ssh_keys[0]['SshKey']['comment'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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

        
        print filter_var($cm_texts[ $cm_lang ]['pl.sshkeyauthenticator.en.sshkey.type'][ $ssh_keys[0]['SshKey']['type'] ],FILTER_SANITIZE_SPECIAL_CHARS);
      ?>
    </div>
  </li>
  <li>
    <div class="field-name vtop">
      <?php print _txt('pl.sshkeyauthenticator.fd.skey'); ?>
    </div>
    <div class="field-info">
      <?php print (filter_var($ssh_keys[0]['SshKey']['skey'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
</ul>
<?php 
  endif; 
  print $this->element("changelog");
?>
