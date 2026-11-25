<?php

/**
 * COmanage Registry SLIP Proofings Vouching View
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;
    
  print $this->element("pageTitleAndButtons", $params);

  if(!empty($this->plugin)) {
    if(file_exists(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc")) {
      include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc");
    } elseif(file_exists(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc")) {
      include(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc");
    }
  } else {
    if(file_exists(APP . "View/" . $model . "/tabs.inc")) {
      include(APP . "View/" . $model . "/tabs.inc");
    }
  }

  if(!empty($vv_done)) {
    // Render a completed message (and a flash result) and finish.
    print '<p>' . _txt('pl.slipplugin.result.vouching.completed') . '</p>';
    return;
  }

  $l = 1;
?>

<p><?php print _txt('pl.slipplugin.vouch.desc'); ?></p>

<ul id="<?php print $this->action; ?>-slip_vouching-requests" class="fields form-list">
  <li class="slip-claimant">
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.co_person_id'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        print filter_var(
          generateCn($slip_proofings[0]['ClaimantCoPerson']['PrimaryName']),
          FILTER_SANITIZE_SPECIAL_CHARS
        );
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.registrar_co_person_id'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        print filter_var(
          generateCn($slip_proofings[0]['RegistrarCoPerson']['PrimaryName']),
          FILTER_SANITIZE_SPECIAL_CHARS
        );
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.voucher_co_person_id'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        print filter_var(
          generateCn($slip_proofings[0]['VoucherCoPerson']['PrimaryName']),
          FILTER_SANITIZE_SPECIAL_CHARS
        );
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.vouch.id'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print $slip_proofings[0]['SlipProofing']['id']; ?>
    </div>
  </li>
</ul>

<h2><?php print _txt('ct.slip_identity_documents.pl'); ?></h2>
<ul id="<?php print $this->action; ?>-slip_vouching-identity-documents" class="fields form-list">
  <?php foreach($slip_proofings[0]['SlipIdentityDocument'] as $i => $s): ?>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.slipplugin.attachnumber', [$i+1]); ?>
        </div>
      </div>
      <div class="field-info">
        <table class="slip-table">
          <tr>
            <th>
              <?php print _txt('fd.identity_documents.document_type'); ?>
            </th>
            <td>
              <?php
                if(!empty($s['IdentityDocument']['document_type'])) {
                  print _txt('en.id.type', null, $s['IdentityDocument']['document_type']);
                }
              ?>
            </td>
          </tr>
          <tr>
            <th>
              <?php print _txt('fd.identity_documents.issuing_authority'); ?>
            </th>
            <td>
              <?php
                if(!empty($s['IdentityDocument']['issuing_authority'])) {
                  print filter_var($s['IdentityDocument']['issuing_authority'], FILTER_SANITIZE_SPECIAL_CHARS);
                }
              ?>
            </td>
          </tr>
          <tr>
            <th>
              <?php print _txt('fd.identity_documents.subject'); ?>
            </th>
            <td>
              <?php
                if(!empty($s['IdentityDocument']['subject'])) {
                  print filter_var($s['IdentityDocument']['subject'], FILTER_SANITIZE_SPECIAL_CHARS);
                }
              ?>
            </td>
          </tr>
          <tr>
            <th>
              <?php print _txt('fd.identity_documents.document_identifier'); ?>
            </th>
            <td>
              <?php
                if(!empty($s['IdentityDocument']['document_identifier'])) {
                  print filter_var($s['IdentityDocument']['document_identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
                }
              ?>
            </td>
          </tr>
          <tr>
            <th>
              <?php print _txt('fd.valid_from'); ?>
            </th>
            <td>
              <?php
                if(!empty($s['IdentityDocument']['valid_from'])) {
                  print $this->Time->format($s['IdentityDocument']['valid_from'], "%c $vv_tz", false, $vv_tz);
                }
              ?>
            </td>
          </tr>
          <tr>
            <th>
              <?php print _txt('fd.valid_through'); ?>
            </th>
            <td>
              <?php
                if(!empty($s['IdentityDocument']['valid_through'])) {
                  print $this->Time->format($s['IdentityDocument']['valid_through'], "%c $vv_tz", false, $vv_tz);
                }
              ?>
            </td>
          </tr>
          <tr>
            <th>
              <?php print _txt('fd.identity_documents.verification_method'); ?>
            </th>
            <td>
              <?php
                if(!empty($s['IdentityDocument']['verification_method'])) {
                  print _txt('en.id.verification', null, $s['IdentityDocument']['verification_method']);
                }
              ?>
            </td>
          </tr>
          <tr>
            <th>
              <?php print _txt('fd.identity_documents.verifier_identifier'); ?>
            </th>
            <td>
              <?php
                if(!empty($s['IdentityDocument']['verifier_identifier'])) {
                  print filter_var($s['IdentityDocument']['verifier_identifier'], FILTER_SANITIZE_SPECIAL_CHARS);
                }
              ?>
            </td>
          </tr>
          <tr>
            <th>
              <?php print _txt('fd.identity_documents.verifier_comment'); ?>
            </th>
            <td>
              <?php
                if(!empty($s['IdentityDocument']['verifier_comment'])) {
                  print filter_var($s['IdentityDocument']['verifier_comment'], FILTER_SANITIZE_SPECIAL_CHARS);
                }
              ?>
            </td>
          </tr>
        </table>
      </div>
    </li>
  <?php endforeach; ?>
  <?php if(count($slip_proofings[0]['SlipAttachment']) == 0): ?>
    <li><?php print _txt('pl.slipplugin.attach.none'); ?></li>
  <?php endif; ?>
</ul>

<h2><?php print _txt('ct.slip_attachments.pl'); ?></h2>
<?php
  $imageMimeTypes = array(
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
  );
?>
<ul id="<?php print $this->action; ?>-slip_vouching-requests-attachments" class="fields form-list">
  <?php foreach($slip_proofings[0]['SlipAttachment'] as $i => $attachment): ?>
    <li>
      <div class="field-name">
        <div class="field-title">
          <?php print _txt('pl.slipplugin.upload.num',[$i+1]); ?>
        </div>
      </div>
      <div class="field-info">
        <?php if(in_array($attachment['mime_type'], $imageMimeTypes, true)): ?>
          <img src="data:<?php print filter_var($attachment['mime_type'], FILTER_SANITIZE_SPECIAL_CHARS); ?>;base64,<?php echo base64_encode($attachment['file_contents']); ?>"
            class="slip-image" alt="<?php print _txt('ct.slip_attachments.1'); ?>" />
        <?php endif; ?>
        <?php print filter_var($attachment['description'], FILTER_SANITIZE_SPECIAL_CHARS); ?>
      </div>
    </li>
  <?php endforeach; ?>
  <?php if(count($slip_proofings[0]['SlipAttachment']) == 0): ?>
    <li><?php print _txt('pl.slipplugin.upload.none'); ?></li>
  <?php endif; ?>
</ul>

<div class="co-info-topbox warn-level-a co-info-vouch">
  <em class="material-icons">warning</em>
  <div class="co-info-topbox-text">
    <?php print _txt('pl.slipplugin.vouch.confirm'); ?>
  </div>
</div>

<div id="slip-actions">
<?php
  print $this->Html->link(
    '<span class="material-icons">check_circle_outline</span>' . _txt('op.confirm'),
    array('plugin'      => 'slip_plugin',
          'controller'  => 'slip_proofings',
          'action'      => 'vouch',
          $slip_proofings[0]['SlipProofing']['id'],
          'op'          => 'confirm'),
    array('class' => 'btn btn-large btn-tertiary approve-button spin', 'escape' => false)
  );

  print $this->Html->link(
    '<span class="material-icons">cancel</span>' . _txt('pl.slipplugin.reject'),
    array('plugin'      => 'slip_plugin',
          'controller'  => 'slip_proofings',
          'action'      => 'vouch',
          $slip_proofings[0]['SlipProofing']['id'],
          'op'          => 'reject'),
    array('class' => 'btn btn-large btn-secondary deny-button spin', 'escape' => false)
  );
?>
</div>

<?php print $this->Form->end();
