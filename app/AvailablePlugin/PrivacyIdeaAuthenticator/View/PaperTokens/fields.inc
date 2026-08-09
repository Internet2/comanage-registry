<!--
/**
 * COmanage Registry PrivacyIDEA Authenticator Plugin Paper Token Fields
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
 * @since         COmanage Registry v4.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
  // Determine if fields are editable
  $e = false;

  print $this->Form->hidden('privacy_idea_authenticator_id',
                            array('default' => $vv_authenticator['PrivacyIdeaAuthenticator']['id'])) . "\n";
  print $this->Form->hidden('co_person_id', array('default' => $vv_co_person['CoPerson']['id'])) . "\n";

  if(!empty($vv_token_info['otps'])) {
    print $this->Form->hidden('otps', array('default' => $vv_token_info['otps']));
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

<?php if(!empty($vv_token_info['otps'])): ?>
  <p>
    <?php print _txt('pl.privacyideaauthenticator.paper.intro'); ?>
  </p>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <?php print _txt('pl.privacyideaauthenticator.paper.caution'); ?>
  </div>
  <div class="co-info-topbox warn-level-a">
    <em class="material-icons error">warning</em>
    <?php print _txt('pl.privacyideaauthenticator.paper.caution'); ?>
  </div>
  <style>
    #add_paper_token {
      width: 200px;
    }
    #add_paper_token td,
    #add_paper_token th {
     padding: 0.5em;
     text-align: center;
    }
    #add_paper_token td:first-child,
    #add_paper_token th:first-child {
     width: 50px;
    }
    @media print {
      #top-menu, #banner, #co-footer, #navigation, #customFooter, #cm-print-button {
        display: none;
      }
    }
  </style>
    <div class="table-container">
      <table id="<?php print $this->action; ?>_paper_token" class="common-table">
       <thead>
       <tr>
         <th>#</th>
         <th>OTP</th>
       </tr>
       </thead>
       <tbody>
         <?php foreach($vv_token_info['otps'] as $i => $otp): ?>
           <tr>
             <td><?php print $i+1; ?></td>
             <td><?php print $otp; ?></td>
           </tr>
         <?php endforeach; ?>

        </tbody>
       </table>
    </div>
  <button type="button" onclick="window.print();" id="cm-print-button" class="btn btn-primary btn-lg">print backup codes</button>
<?php elseif($this->action == 'view'): ?>
<ul id="<?php print $this->action; ?>_paper_token" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print _txt('pl.privacyideaauthenticator.fd.serial'); ?>
    </div>
    <div class="field-info">
      <?php
        print filter_var($paper_tokens[0]['PaperToken']['serial'],FILTER_SANITIZE_SPECIAL_CHARS);
      ?>
    </div>
  </li>
</ul>
<?php endif; // vv_otps, view ?>
<script>
  window.onbeforeunload = function(e) {
    // A value must be returned in order to prompt the user, but for security reasons we cannot control the actual prompt
    e.preventDefault();
    e.returnValue = "Before you leave this page, please confirm that you've copied your backup codes. YOU WILL NOT SEE THEM AGAIN.";
  }
</script>
