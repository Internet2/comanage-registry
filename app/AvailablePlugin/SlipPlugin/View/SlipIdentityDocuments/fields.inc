<!--
/**
 * COmanage Registry SLIP Identity Documents Fields
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
  // Determine if fields are editable
  // (Proofing fields are never editable)
  $e = false;
  
 if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
   $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return false;

  // We might get breadcrumb info in two different ways (add vs view)
  $coPersonId = (!empty($slip_identity_documents[0]['IdentityDocument']['CoPerson']['id'])
                 ? $slip_identity_documents[0]['IdentityDocument']['CoPerson']['id']
                 : $vv_slip_proofing['ClaimantCoPerson']['id']);
  $primaryName = (!empty($slip_identity_documents[0]['IdentityDocument']['CoPerson']['PrimaryName'])
                  ? $slip_identity_documents[0]['IdentityDocument']['CoPerson']['PrimaryName']
                  : $vv_slip_proofing['ClaimantCoPerson']['PrimaryName']);
  $slipPluginId = (!empty($slip_identity_documents[0]['SlipProofing']['slip_plugin_id'])
                   ? $slip_identity_documents[0]['SlipProofing']['slip_plugin_id']
                   : $vv_slip_proofing['SlipProofing']['slip_plugin_id']);
  $slipProofingId = (!empty($slip_identity_documents[0]['SlipProofing']['id'])
                     ? $slip_identity_documents[0]['SlipProofing']['id']
                     : $vv_slip_proofing['SlipProofing']['id']);
  
  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'index',
    'co' => $cur_co['Co']['id']
  );
  $this->Html->addCrumb(_txt('me.population'), $args);

  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'canvas',
    $coPersonId
  );
  $this->Html->addCrumb(generateCn($primaryName), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'index',
    'spid' => $slipPluginId,
    'coperson' => $coPersonId
  );
  $this->Html->addCrumb(_txt('ct.slip_proofings.pl'), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'view',
    $slipProofingId
  );
  $this->Html->addCrumb((string)$slipProofingId, $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_identity_documents',
    'action' => 'index',
    'sprid' => $slipProofingId
  );
  $this->Html->addCrumb(_txt('ct.slip_identity_documents.pl'), $args);

  $crumbTxt = _txt('ct.slip_identity_documents.1');
  $this->Html->addCrumb($crumbTxt);
  
  // Set up the hidden values

  if($e && $this->action == "add") {
    // We don't currently support edit, if we do we'll need to update this
    
    print $this->Form->hidden('slip_proofing_id', array('default' => $slipProofingId)). "\n";
  }

  $l = 1;

  $args = array(
    'enumerables' => array('SlipIdentityDocument.issuing_authority'),
    'typeFields' => array(
      'SlipIdentityDocument.issuing_authority' => 'document_type'
    )
  );
  print $this->element('enumerations', $args);
?>
<script type="text/javascript">
  function js_local_onload() {
    enum_update_gadgets(false);
  }
  $(function() {
    validate_date_input('<?php print _txt('er.validation') ?>', '<?php print _txt('er.validation.date') ?>');
  });
</script>
<ul id="<?php print $this->action; ?>_slip_identity_documents" class="fields form-list form-list-admin">
  <?php if($this->action != "add"): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.slipplugin.co_person_id'); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        if(!empty($slip_identity_documents[0]['IdentityDocument']['CoPerson']['PrimaryName'])) {
          print $this->Html->link(
                  generateCn($slip_identity_documents[0]['IdentityDocument']['CoPerson']['PrimaryName']),
                  array(
                    'plugin'     => null,
                    'controller' => 'co_people',
                    'action'     => 'canvas',
                    $slip_identity_documents[0]['IdentityDocument']['CoPerson']['id']
                ));            
        }
      ?>
    </div>
  </li>
  <?php endif; // add ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('document_type', _txt('fd.identity_documents.document_type')) : _txt('fd.identity_documents.document_type')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = !empty($this->request->data['SlipIdentityDocument']['document_type'])
                            ? $this->request->data['SlipIdentityDocument']['document_type']
                            : "";
          $attrs['onchange'] = "enum_update_gadgets(true);";
          $attrs['empty'] = true;

          print $this->Form->select('document_type',
                                    $cm_texts[ $cm_lang ]['en.id.type'],
                                    $attrs);

          if($this->Form->isFieldError('document_type')) {
            print $this->Form->error('document_type');
          }
        } else {
          print _txt('en.id.type', null, $slip_identity_documents[0]['IdentityDocument']['document_type']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('document_subtype', _txt('fd.identity_documents.document_subtype')) : _txt('fd.identity_documents.document_subtype')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.identity_documents.document_subtype.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('document_subtype')
                   : filter_var($slip_identity_documents[0]['IdentityDocument']['document_subtype'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('issuing_authority', _txt('fd.identity_documents.issuing_authority')) : _txt('fd.identity_documents.issuing_authority')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $args = array(
            'column' => 'issuing_authority',
            'editable' => $e
          );

          print $this->element('enumerableField', $args);
        } else {
          print filter_var($slip_identity_documents[0]['IdentityDocument']['issuing_authority'],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('subject', _txt('fd.identity_documents.subject')) : _txt('fd.identity_documents.subject')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.identity_documents.subject.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('subject')
                   : filter_var($slip_identity_documents[0]['IdentityDocument']['subject'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('document_identifier', _txt('fd.identity_documents.document_identifier')) : _txt('fd.identity_documents.document_identifier')); ?>
      </div>
      <div class="field-desc"><?php print _txt('fd.identity_documents.document_identifier.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('document_identifier')
                   : filter_var($slip_identity_documents[0]['IdentityDocument']['document_identifier'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li class="modelbox-data">
    <div class="field-name">
      <div class="field-title">
      <?php
        if($e) {
          print $this->Form->label('valid_from', _txt('fd.valid_from.tz', array($vv_tz)));
        } else {
          print _txt('fd.valid_from');
        }
      ?>
    </div>
      <span class="field-descr"><?php print _txt('fd.valid_from.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $args = array(
            'class' => 'datepicker-f'
          );

          if(!empty($slip_identity_documents[0]['IdentityDocument']['valid_from'])) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($slip_identity_documents[0]['IdentityDocument']['valid_from'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $slip_identity_documents[0]['IdentityDocument']['valid_from'];
            }
          }

          print $this->Form->text('valid_from', $args);
        } else {
          if(!empty($slip_identity_documents[0]['IdentityDocument']['valid_from'])) {
            print $this->Time->format($slip_identity_documents[0]['IdentityDocument']['valid_from'], "%c $vv_tz", false, $vv_tz);
          }
        }
      ?>
    </div>
  </li>
  <li class="modelbox-data">
    <div class="field-name">
      <div class="field-title">
      <?php
        if($e) {
          print $this->Form->label('valid_through', _txt('fd.valid_through.tz', array($vv_tz)));
        } else {
          print _txt('fd.valid_through');
        }
      ?>
    </div>
      <span class="field-descr"><?php print _txt('fd.valid_through.desc'); ?></span>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          $args = array(
            'class' => 'datepicker-u'
          );

          if(!empty($slip_identity_documents[0]['IdentityDocument']['valid_through'])) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($slip_identity_documents[0]['IdentityDocument']['valid_through'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $slip_identity_documents[0]['IdentityDocument']['valid_through'];
            }
          }

          print $this->Form->text('valid_through', $args);
        } else {
          if(!empty($slip_identity_documents[0]['IdentityDocument']['valid_through'])) {
            print $this->Time->format($slip_identity_documents[0]['IdentityDocument']['valid_through'], "%c $vv_tz", false, $vv_tz);
          }
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('verification_method', _txt('fd.identity_documents.verification_method')) : _txt('fd.identity_documents.verification_method')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php
        if($e) {
          global $cm_lang, $cm_texts;
          $attrs = array();
          $attrs['value'] = !empty($this->request->data['SlipProofing']['verification_method'])
                            ? $this->request->data['SlipProofing']['verification_method']
                            : IdentityVerificationMethodEnum::None;
          $attrs['empty'] = false;

          print $this->Form->select('verification_method',
                                    $cm_texts[ $cm_lang ]['en.id.verification'],
                                    $attrs);

          if($this->Form->isFieldError('verification_method')) {
            print $this->Form->error('verification_method');
          }
        } else {
          print _txt('en.id.verification', null, $slip_identity_documents[0]['IdentityDocument']['verification_method']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('verifier_comment', _txt('fd.comment')) : _txt('fd.comment')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php print ($e
                   ? $this->Form->input('verifier_comment')
                   : filter_var($slip_identity_documents[0]['IdentityDocument']['verifier_comment'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if($this->action != 'add'): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('ct.identity_documents.1'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.slipplugin.identity_documents.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php 
        print $this->Html->link(
                  _txt('ct.identity_documents.1'),
                  array(
                    'plugin'     => null,
                    'controller' => 'identity_documents',
                    'action'     => 'view',
                    $slip_identity_documents[0]['IdentityDocument']['id']
                ));        
      ?>
    </div>
  </li>
  <?php endif; // add ?>
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
