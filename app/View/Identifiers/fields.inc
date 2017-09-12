<?php
/**
 * COmanage Registry Identifier Fields
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
  print $this->Form->hidden('co_person_id', array('default' => $vv_pid['copersonid'])) . "\n";
  
  // Add breadcrumbs
  print $this->element("coCrumb", array('mvpa' => 'identifiers'));
?>
<?php if(!empty($identifiers[0]['CoProvisioningTarget']['id'])): ?>
<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php print _txt('in.id.prov', array($identifiers[0]['CoProvisioningTarget']['description'])); ?>
</div>
<?php endif; // provisioning target ?>

<ul id="<?php print $this->action; ?>_identifier" class="fields form-list">
  <li>
    <div class="field-name">
      <?php
        if($e) {
          print $this->Form->label('identifier', _txt('fd.identifier.identifier'));
        } else {
          print _txt('fd.identifier.identifier');
        }
      ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php print ($e ? $this->Form->input('identifier', array('type' => 'text','class' => 'focusFirst'))
                      : filter_var($identifiers[0]['Identifier']['identifier'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <?php
        if($e) {
          print $this->Form->label('type', _txt('fd.type'));
        } else {
          print _txt('fd.type');
        }
      ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (!empty($identifiers[0]['Identifier']['type']) ? $identifiers[0]['Identifier']['type'] : "");
        $attrs['empty'] = false;
        
        if($e) {
          if(!empty($identifiers[0]['CoProvisioningTarget']['id'])) {
            // Don't allow the type to be changed, but send the type as hidden since
            // it is required by the model
            
            print filter_var($vv_available_types[ $identifiers[0]['Identifier']['type'] ],FILTER_SANITIZE_SPECIAL_CHARS);
            print $this->Form->hidden('type',
                                      array('default' =>
                                            filter_var($identifiers[0]['Identifier']['type'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";
          } else {
            print $this->Form->select('type',
                                      $vv_available_types,
                                      $attrs);
  
            if($this->Form->isFieldError('type')) {
              print $this->Form->error('type');
            }
          }
        } else {
          print filter_var($vv_available_types[ $identifiers[0]['Identifier']['type'] ],FILTER_SANITIZE_SPECIAL_CHARS);;
        }
        
        if(!empty($identifiers[0]['CoProvisioningTarget']['description'])) {
          print " (" . $this->Html->link(filter_var($identifiers[0]['CoProvisioningTarget']['description'],FILTER_SANITIZE_SPECIAL_CHARS),
                                         array(
                                           'controller' => 'co_provisioning_targets',
                                           'action'     => 'edit',
                                           $identifiers[0]['CoProvisioningTarget']['id']
                                         ))
                     . ")";
        }
      ?>
    </div>
  </li>
  <?php if(!empty($vv_pid['orgidentityid'])): ?>
    <li>
      <div class="field-name">
        <?php print _txt('fd.identifier.login'); ?><br />
      </div>
      <div class="field-info">
        <?php
          if($e) {
            print $this->Form->input('login');
            print "\n<em>" . $this->Form->label('login', _txt('fd.identifier.login.desc')) . "</em>\n";
          } else {
            print _txt($identifiers[0]['Identifier']['login'] ? 'fd.yes' : 'fd.no');
          }
        ?>
      </div>
    </li>
  <?php else: // org identity
      print $this->Form->hidden('login', array('default' => false)) . "\n";
    endif; // org identity ?>

  <li>
    <div class="field-name">
      <?php
        if($e) {
          print $this->Form->label('status', _txt('fd.status'));
        } else {
          print _txt('fd.status');
        }
      ?>
      <span class="required">*</span>
    </div>
    <div class="field-info">
      <?php
        global $cm_lang, $cm_texts;
        $attrs = array();
        $attrs['value'] = (isset($identifiers) ? $identifiers[0]['Identifier']['status'] : StatusEnum::Active);
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('status',
                                    $cm_texts[ $cm_lang ]['en.status'],
                                    $attrs);

          if($this->Form->isFieldError('status')) {
            print $this->Form->error('status');
          }
        } else {
          print _txt('en.status', null, $identifiers[0]['Identifier']['status']);
        }
      ?>
    </div>
  </li>
  <?php if(!empty($identifiers[0]['SourceIdentifier']['id'])): ?>
  <li>
    <div class="field-name">
      <?php print _txt('fd.source'); ?><br />
    </div>
    <div class="field-info">
      <?php print $this->Html->link(_txt('ct.identifiers.1'),
                                    array('controller' => 'identifiers',
                                          'action'     => 'view',
                                          $identifiers[0]['SourceIdentifier']['id']));
      ?>,
      <?php print $this->Html->link(_txt('ct.org_identities.1'),
                                    array('controller' => 'org_identities',
                                          'action'     => 'view',
                                          $identifiers[0]['SourceIdentifier']['org_identity_id']));
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
