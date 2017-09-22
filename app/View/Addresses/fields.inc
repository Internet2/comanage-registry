<?php
/**
 * COmanage Registry Address Fields
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // We can emit all attributes because the ones we don't want should be null
  print $this->Form->hidden('co_department_id', array('default' => $vv_pid['codeptid'])) . "\n";
  print $this->Form->hidden('org_identity_id', array('default' => $vv_pid['orgidentityid'])) . "\n";
  print $this->Form->hidden('co_person_role_id', array('default' => $vv_pid['copersonroleid'])) . "\n";
  
  if(isset($this->request->params['named']['co']))
    print $this->Form->hidden('co_id', array('default' => filter_var($this->request->params['named']['co'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";

  // Add breadcrumbs
  print $this->element("coCrumb", array('mvpa' => 'addresses'));
?>
<ul id="<?php print $this->action; ?>_address" class="fields form-list">
  <li>
    <div class="field-name vtop">
      <?php print ($e ? $this->Form->label('street',_txt('fd.address.street')) : _txt('fd.address.street')); ?>
      <?php if(in_array('street', $required_fields)): ?>
      <span class="required">*</span>
      <?php endif; // required_fields ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('street', array('class' => 'focusFirst')) : filter_var($addresses[0]['Address']['street'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('room',_txt('fd.address.room')) : _txt('fd.address.room')); ?>
      <?php if(in_array('room', $required_fields)): ?>
      <span class="required">*</span>
      <?php endif; // required_fields ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('room') : filter_var($addresses[0]['Address']['room'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('locality',_txt('fd.address.locality')) : _txt('fd.address.locality')); ?>
      <?php if(in_array('locality', $required_fields)): ?>
      <span class="required">*</span>
      <?php endif; // required_fields ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('locality') : filter_var($addresses[0]['Address']['locality'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('state',_txt('fd.address.state')) : _txt('fd.address.state')); ?>
      <?php if(in_array('state', $required_fields)): ?>
      <span class="required">*</span>
      <?php endif; // required_fields ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('state') : filter_var($addresses[0]['Address']['state'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('postal_code',_txt('fd.address.postal_code')) : _txt('fd.address.postal_code')); ?>
      <?php if(in_array('postal_code', $required_fields)): ?>
      <span class="required">*</span>
      <?php endif; // required_fields ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('postal_code') : filter_var($addresses[0]['Address']['postal_code'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('country',_txt('fd.address.country')) : _txt('fd.address.country')); ?>
      <?php if(in_array('country', $required_fields)): ?>
      <span class="required">*</span>
      <?php endif; // required_fields ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('country') : filter_var($addresses[0]['Address']['country'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('description',_txt('fd.desc')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description') : filter_var($addresses[0]['Address']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.type'); ?>
    </div>
    <div class="field-info">
      <?php
      $attrs = array();
        $attrs['value'] = (isset($addresses[0]['Address']['type']) ? $addresses[0]['Address']['type'] : "");
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('type',
                                    $vv_available_types,
                                    $attrs);
          
          if($this->Form->isFieldError('type')) {
            print $this->Form->error('type');
          }
        } else {
          print _txt('en.contact', null, $addresses[0]['Address']['type']);
        }
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('language',_txt('fd.language')) : _txt('fd.language')); ?>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        
        $attrs = array();
        $attrs['value'] = (isset($addresses[0]['Address']['language'])
                           ? $addresses[0]['Address']['language']
                           : getPreferredLanguage());
        $attrs['empty'] = true;
        
        if($e) {
          print $this->Form->select('language',
                                    $cm_texts[ $cm_lang ]['en.language'],
                                    $attrs);
          
          if($this->Form->isFieldError('language')) {
            print $this->Form->error('language');
          }
        } else {
          if(!empty($addresses[0]['Address']['language']))
            print _txt('en.language', null, $addresses[0]['Address']['language']);
        }
      ?>
    </div>
  </li>

  <?php if(!empty($addresses[0]['SourceAddress']['id'])): ?>
  <li>
    <div class="field-name">
      <?php print _txt('fd.source'); ?><br />
    </div>
    <div class="field-info">
      <?php print $this->Html->link(_txt('ct.addresses.1'),
                                    array('controller' => 'addresses',
                                          'action'     => 'view',
                                          $addresses[0]['SourceAddress']['id']));
      ?>,
      <?php print $this->Html->link(_txt('ct.org_identities.1'),
                                    array('controller' => 'org_identities',
                                          'action'     => 'view',
                                          $addresses[0]['SourceAddress']['org_identity_id']));
      ?>
    </div>
  </li>
  <?php endif; // source id ?>

  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
      </div>
      <div class="field-info">
        <?php print $this->Form->submit($submit_label); ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
<?php
  print $this->element("changelog");
