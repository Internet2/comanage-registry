<?php
/**
 * COmanage Registry Identity Documents Fields
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  if(!$e && !$permissions['view'])
    return false;
  
  // Find the CO Person ID
  $coPersonId = null;
  
  if($this->action == 'add') {
    $coPersonId = filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_SPECIAL_CHARS);
  } else {
    $coPersonId = $identity_documents[0]['IdentityDocument']['co_person_id'];
  }
  
  // Add breadcrumbs
  print $this->element("coCrumb");
  
  // CO Person
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);

  $args = array(
    'controller' => 'co_people',
    'action' => 'canvas',
    $coPersonId
  );
  /* XXX When we carry the display name through all actions, we can uncomment this section;
  if (isset($display_name)) {
    $this->Html->addCrumb($display_name, $args);
  } else { */
    $this->Html->addCrumb(_txt('ct.co_people.1'), $args);
  /*}*/

  $args = array(
    'controller' => 'identity_documents',
    'action' => 'index',
    'copersonid' => $coPersonId
  );
  $this->Html->addCrumb(_txt('ct.identity_documents.pl'), $args);

  $this->Html->addCrumb(_txt('op.' . $this->action));

  // Set up the hidden values
  
  print $this->Form->hidden('co_person_id',
                            array('default' => $coPersonId)). "\n";
  
  $l = 1;
  
  $args = array(
    'enumerables' => array('IdentityDocument.issuing_authority'),
    'typeFields' => array(
      'IdentityDocument.issuing_authority' => 'document_type'
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
<ul id="<?php print $this->action; ?>_identity_documents" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('document_type', _txt('fd.identity_documents.document_type')) : _txt('fd.identity_documents.document_type')); ?>
        <span class="required">*</span>
      </div>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($identity_documents) ? $identity_documents[0]['IdentityDocument']['document_type'] : SuspendableStatusEnum::Active);
        $attrs['onchange'] = "enum_update_gadgets(true);";
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('document_type',
                                    $cm_texts[ $cm_lang ]['en.id.type'],
                                    $attrs);

          if($this->Form->isFieldError('document_type')) {
            print $this->Form->error('document_type');
          }
        } else {
          print _txt('en.id.type', null, $identity_documents[0]['IdentityDocument']['document_type']);
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
                   : filter_var($identity_documents[0]['IdentityDocument']['document_subtype'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
        $args = array(
          'column' => 'issuing_authority',
          'editable' => true
        );
        
        print $this->element('enumerableField', $args);
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
                   : filter_var($identity_documents[0]['IdentityDocument']['subject'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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
                   : filter_var($identity_documents[0]['IdentityDocument']['document_identifier'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
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

          if(!empty($identity_documents[0]['IdentityDocument']['valid_from'])) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($identity_documents[0]['IdentityDocument']['valid_from'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $identity_documents[0]['IdentityDocument']['valid_from'];
            }
          }

          print $this->Form->text('valid_from', $args);
        } else {
          if(!empty($identity_documents[0]['IdentityDocument']['valid_from'])) {
            print $this->Time->format($identity_documents[0]['IdentityDocument']['valid_from'], "%c $vv_tz", false, $vv_tz);
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

          if(!empty($identity_documents[0]['IdentityDocument']['valid_through'])) {
            if(!empty($vv_tz)) {
              // We need to adjust the UTC value to the user's local time
              $args['value'] = $this->Time->format($identity_documents[0]['IdentityDocument']['valid_through'], "%F %T", false, $vv_tz);
            } else {
              $args['value'] = $identity_documents[0]['IdentityDocument']['valid_through'];
            }
          }

          print $this->Form->text('valid_through', $args);
        } else {
          if(!empty($identity_documents[0]['IdentityDocument']['valid_through'])) {
            print $this->Time->format($identity_documents[0]['IdentityDocument']['valid_through'], "%c $vv_tz", false, $vv_tz);
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
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($identity_documents) ? $identity_documents[0]['IdentityDocument']['verification_method'] : IdentityVerificationMethodEnum::None);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('verification_method',
                                    $cm_texts[ $cm_lang ]['en.id.verification'],
                                    $attrs);

          if($this->Form->isFieldError('verification_method')) {
            print $this->Form->error('verification_method');
          }
        } else {
          print _txt('en.id.verification', null, $identity_documents[0]['IdentityDocument']['verification_method']);
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
                   : filter_var($identity_documents[0]['IdentityDocument']['verifier_comment'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <?php if($this->action != 'add'): ?>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('verifier_identifier', _txt('fd.identity_documents.verifier_identifier')) : _txt('fd.identity_documents.verifier_identifier')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($identity_documents[0]['IdentityDocument']['verifier_identifier'])) {
          print filter_var($identity_documents[0]['IdentityDocument']['verifier_identifier'],FILTER_SANITIZE_SPECIAL_CHARS);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print ($e ? $this->Form->label('created', _txt('fd.created')) : _txt('fd.created')); ?>
      </div>
    </div>
    <div class="field-info">
      <?php 
        if(!empty($identity_documents[0]['IdentityDocument']['created'])) {
          print $this->Time->format($identity_documents[0]['IdentityDocument']['created'], "%c $vv_tz", false, $vv_tz);
        }
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
  <?php endif ?>
</ul>
<?php
  print $this->element("changelog");