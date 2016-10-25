<?php
/**
 * COmanage Registry Identifier Fields
 *
 * Copyright (C) 2010-16 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2011-16 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  $idForBreadcrumbs = '';
  $idType = null;
  
  // Migrate the target person from a named argument to a form field
  if($this->action == 'add' && isset($this->request->params['named']['copersonid'])) {
    $idForBreadcrumbs = Sanitize::html($this->request->params['named']['copersonid']);
    $idType = 'person';
  } elseif(isset($this->request->data['Identifier']['co_person_id'])) {
    $idForBreadcrumbs = Sanitize::html($this->request->data['Identifier']['co_person_id']);
    $idType = 'person';
  } elseif(!empty($identifiers[0]['Identifier']['co_person_id'])) {
    $idForBreadcrumbs = $identifiers[0]['Identifier']['co_person_id'];
    $idType = 'person';
  } elseif($this->action == 'add' && isset($this->params['named']['orgidentityid'])){
    $idForBreadcrumbs = Sanitize::html($this->request->params['named']['orgidentityid']);
    $idType = 'org';
  } elseif(isset($this->request->data['Identifier']['org_identity_id'])) {
    $idForBreadcrumbs = Sanitize::html($this->request->data['Identifier']['org_identity_id']);
    $idType = 'org';
  } elseif(!empty($identifiers[0]['Identifier']['org_identity_id'])) {
    $idForBreadcrumbs = $identifiers[0]['Identifier']['org_identity_id'];
    $idType = 'org';
  }
  
  if($idType == 'org') {
    print $this->Form->hidden('org_identity_id', array('default' => $idForBreadcrumbs)) . "\n";
  } elseif($idType == 'person') {
    print $this->Form->hidden('co_person_id', array('default' => $idForBreadcrumbs)) . "\n";
  }
  
  // Add breadcrumbs
  print $this->element("coCrumb");
  if($idType == 'person') {
    // CO Person History
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_people';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('me.population'), $args);

    $args = array(
      'controller' => 'co_people',
      'action' => 'canvas',
      $idForBreadcrumbs);
    $this->Html->addCrumb(_txt('ct.co_people.1'), $args);
  } else {
    // Org ID History
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'org_identities';
    $args['action'] = 'index';
    if(!$pool_org_identities) {
      $args['co'] = $cur_co['Co']['id'];
    }
    $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);

    $args = array(
      'controller' => 'orgIdentities',
      'action' => 'edit',
      $idForBreadcrumbs);
    $this->Html->addCrumb(_txt('ct.org_identities.1'), $args);
  }
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.identifiers.1')));
  $this->Html->addCrumb($crumbTxt);
  
  $l = 1;

  // hidden fields
  if($idType != 'org') {
    print $this->Form->hidden('login', array('default' => false)) . "\n";
  }
?>

<ul id="<?php print $this->action; ?>_identifier" class="fields form-list">
  <li>
    <span class="field-name">
      <?php
        if($e) {
          print $this->Form->label('identifier', _txt('fd.identifier.identifier'));
        } else {
          print _txt('fd.identifier.identifier');
        }
      ?>
      <span class="required">*</span>
    </span>
    <span class="field-info">
      <?php print ($e ? $this->Form->input('identifier', array('type' => 'text','class' => 'focusFirst'))
                     : Sanitize::html($identifiers[0]['Identifier']['identifier'])); ?>
    </span>
  </li>
  <li>
    <span class="field-name">
      <?php
        if($e) {
          print $this->Form->label('type', _txt('fd.type'));
        } else {
          print _txt('fd.type');
        }
      ?>
      <span class="required">*</span>
    </span>
    <span class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($identifiers) ? $identifiers[0]['Identifier']['type'] : "");
        $attrs['empty'] = false;

        if($e) {
          print $this->Form->select('type',
                                    $vv_available_types,
                                    $attrs);

          if($this->Form->isFieldError('type')) {
            print $this->Form->error('type');
          }
        } else {
          print $vv_available_types[ $identifiers[0]['Identifier']['type'] ];
        }
      ?>
    </span>
  </li>
  <?php if($idType == 'org'): ?>
    <li>
      <span class="field-name">
        <?php print _txt('fd.identifier.login'); ?><br />
      </span>
      <span class="field-info">
        <?php
          if($e) {
            print $this->Form->input('login');
            print "\n<em>" . $this->Form->label('login', _txt('fd.identifier.login.desc')) . "</em>\n";
          } else {
            print _txt($identifiers[0]['Identifier']['login'] ? 'fd.yes' : 'fd.no');
          }
        ?>
      </span>
    </li>
  <?php endif; // org identity ?>

  <li>
    <span class="field-name">
      <?php
        if($e) {
          print $this->Form->label('status', _txt('fd.status'));
        } else {
          print _txt('fd.status');
        }
      ?>
      <span class="required">*</span>
    </span>
    <span class="field-info">
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
    </span>
  </li>
  <?php if(!empty($identifiers[0]['SourceIdentifier']['id'])): ?>
  <li>
    <span class="field-name">
      <?php print _txt('fd.source'); ?><br />
    </span>
    <span class="field-info">
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
    </span>
  </li>
  <?php endif; // source id ?>
  <li class="fields-submit">
    <span class="field-name">
      <span class="required"><?php print _txt('fd.req'); ?></span>
    </span>
    <span class="field-info">
      <?php
        if($e) {
          print $this->Form->submit($submit_label);
        }
      ?>
    </span>
  </li>
</ul>
<?php
  print $this->element("changelog");
