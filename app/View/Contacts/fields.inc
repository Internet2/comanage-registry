<?php
/**
 * COmanage Registry Contacts Fields
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
 * @since         COmanage Registry v4.4.0
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
  print $this->Form->hidden('organization_id', array('default' => $vv_pid['organizationid'])) . "\n";

  // Add breadcrumbs
  print $this->element("coCrumb", array('mvpa' => 'contacts'));
?>
<ul id="<?php print $this->action; ?>_contact" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('given',_txt('fd.contact.given')) : _txt('fd.contact.given')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('given', array('class' => 'focusFirst')) : filter_var($contacts[0]['Contact']['given'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('family',_txt('fd.contact.family')) : _txt('fd.contact.family')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('family') : filter_var($contacts[0]['Contact']['family'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('company',_txt('fd.contact.company')) : _txt('fd.contact.company')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('company') : filter_var($contacts[0]['Contact']['company'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('number',_txt('fd.contact.number')) : _txt('fd.contact.number')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('number') : filter_var($contacts[0]['Contact']['number'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('mail',_txt('fd.contact.mail')) : _txt('fd.contact.mail')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('mail') : filter_var($contacts[0]['Contact']['mail'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('type',_txt('fd.contact.type')) : _txt('fd.contact.type')); ?>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($contacts) ? $contacts[0]['Contact']['type'] : "");
        $attrs['empty'] = true;

        if($e) {
          print $this->Form->select('type',
                                    $vv_available_types,
                                    $attrs);

          if($this->Form->isFieldError('type')) {
            print $this->Form->error('type');
          }
        } else {
          print _txt('en.contact_type', null, $contacts[0]['Contact']['type']);
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
<?php
  print $this->element("changelog");
