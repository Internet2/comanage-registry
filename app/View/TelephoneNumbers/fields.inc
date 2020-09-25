<?php
/**
 * COmanage Registry Telephone Number Fields
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
  print $this->Form->hidden('organization_id', array('default' => $vv_pid['organizationid'])) . "\n";
  print $this->Form->hidden('org_identity_id', array('default' => $vv_pid['orgidentityid'])) . "\n";
  print $this->Form->hidden('co_person_role_id', array('default' => $vv_pid['copersonroleid'])) . "\n";
  
  if(isset($this->params['named']['co']))
    print $this->Form->hidden('co_id', array('default' => filter_var($this->params['named']['co'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";

  // Add breadcrumbs
  print $this->element("coCrumb", array('mvpa' => 'telephone_numbers'));
?>
<ul id="<?php print $this->action; ?>_telephone_number" class="fields form-list">
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('country_code',_txt('fd.telephone_number.country_code')) : _txt('fd.telephone_number.country_code')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('country_code') : filter_var($telephone_numbers[0]['TelephoneNumber']['country_code'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('area_code',_txt('fd.telephone_number.area_code')) : _txt('fd.telephone_number.area_code')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('area_code') : filter_var($telephone_numbers[0]['TelephoneNumber']['area_code'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('number',_txt('fd.telephone_number.number')) : _txt('fd.telephone_number.number')); ?><span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('number', array('class' => 'focusFirst')) : filter_var($telephone_numbers[0]['TelephoneNumber']['number'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print ($e ? $this->Form->label('extension',_txt('fd.telephone_number.extension')) : _txt('fd.telephone_number.extension')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('extension') : filter_var($telephone_numbers[0]['TelephoneNumber']['extension'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print $this->Form->label('description',_txt('fd.desc')); ?>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('description') : filter_var($telephone_numbers[0]['TelephoneNumber']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php print _txt('fd.type'); ?>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($telephone_numbers) ? $telephone_numbers[0]['TelephoneNumber']['type'] : "");
        $attrs['empty'] = false;
        
        if($e) {
          print $this->Form->select('type',
                                    $vv_available_types,
                                    $attrs);
          
          if($this->Form->isFieldError('type')) {
            print $this->Form->error('type');
          }
        } else {
          print _txt('en.contact', null, $telephone_numbers[0]['TelephoneNumber']['type']);
        }
      ?>
    </div>
  </li>
  <?php if(!empty($telephone_numbers[0]['SourceTelephoneNumber']['id'])): ?>
    <li>
      <div class="field-name">
        <?php print _txt('fd.source'); ?><br />
      </div>
      <div class="field-info">
        <?php print $this->Html->link(_txt('ct.telephone_numbers.1'),
                                      array('controller' => 'telephone_numbers',
                                            'action'     => 'view',
                                            $telephone_numbers[0]['SourceTelephoneNumber']['id']));
        ?>,
        <?php print $this->Html->link(_txt('ct.org_identities.1'),
                                      array('controller' => 'org_identities',
                                            'action'     => 'view',
                                            $telephone_numbers[0]['SourceTelephoneNumber']['org_identity_id']));
        ?>
      </div>
    </li>
  <?php endif; // source id ?>
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
