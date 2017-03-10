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

  $idForBreadcrumbs = '';
  $idType = null;
  
  // Migrate the target person from a named argument to a form field
  if($this->action == 'add' && isset($this->request->params['named']['copersonroleid'])) {
    $idForBreadcrumbs = filter_var($this->request->params['named']['copersonroleid'],FILTER_SANITIZE_SPECIAL_CHARS);
    $idType = 'role';
  } elseif(isset($this->request->data['Address']['co_person_role_id'])) {
    $idForBreadcrumbs = filter_var($this->request->data['Address']['co_person_role_id'],FILTER_SANITIZE_SPECIAL_CHARS);
    $idType = 'role';
  } elseif(!empty($addresses[0]['Address']['co_person_role_id'])) {
    $idForBreadcrumbs = filter_var($addresses[0]['Address']['co_person_role_id'],FILTER_SANITIZE_SPECIAL_CHARS);
    $idType = 'role';
  } elseif($this->action == 'add' && isset($this->request->params['named']['orgidentityid'])) {
    $idForBreadcrumbs = filter_var($this->request->params['named']['orgidentityid'],FILTER_SANITIZE_SPECIAL_CHARS);
    $idType = 'org';
  } elseif(isset($this->request->data['Address']['org_identity_id'])) {
    $idForBreadcrumbs = filter_var($this->request->data['Address']['org_identity_id'],FILTER_SANITIZE_SPECIAL_CHARS);
    $idType = 'org';
  } elseif(!empty($addresses[0]['Address']['org_identity_id'])) {
    $idForBreadcrumbs = filter_var($addresses[0]['Address']['org_identity_id'],FILTER_SANITIZE_SPECIAL_CHARS);
    $idType = 'org';
  }
  
  if($idType == 'org') {
    print $this->Form->hidden('org_identity_id', array('default' => $idForBreadcrumbs)) . "\n";
  } elseif($idType == 'role') {
    print $this->Form->hidden('co_person_role_id', array('default' => $idForBreadcrumbs)) . "\n";
  }
  
  if(isset($this->request->params['named']['co']))
    print $this->Form->hidden('co_id', array('default' => filter_var($this->request->params['named']['co'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";

  // Add breadcrumbs
  print $this->element("coCrumb");
  if($idType == 'role') {
    // CO Person History
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_people';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('me.population'), $args);

    $args = array(
      'controller' => 'co_person_roles',
      'action' => 'edit',
      $idForBreadcrumbs);
    $this->Html->addCrumb(_txt('ct.co_person_roles.1'), $args);
  } elseif($idType == 'org') {
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
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.addresses.1')));
  $this->Html->addCrumb($crumbTxt);

  $l = 1;
?>
<table id="<?php print $this->action; ?>_address" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td class="fieldName">
        <?php print _txt('fd.address.street'); ?>
        <?php if(in_array('street', $required_fields)): ?>
        <span class="required">*</span>
        <?php endif; // required_fields ?>
      </td>
      <td class="fieldVal">
        <?php print ($e ? $this->Form->input('street', array('class' => 'focusFirst')) : filter_var($addresses[0]['Address']['street'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td class="fieldName">
        <?php print _txt('fd.address.room'); ?>
        <?php if(in_array('room', $required_fields)): ?>
        <span class="required">*</span>
        <?php endif; // required_fields ?>
      </td>
      <td class="fieldVal">
        <?php print ($e ? $this->Form->input('room') : filter_var($addresses[0]['Address']['room'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td class="fieldName">
        <?php print _txt('fd.address.locality'); ?>
        <?php if(in_array('locality', $required_fields)): ?>
        <span class="required">*</span>
        <?php endif; // required_fields ?>
      </td>
      <td class="fieldVal">
        <?php print ($e ? $this->Form->input('locality') : filter_var($addresses[0]['Address']['locality'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td class="fieldName">
        <?php print _txt('fd.address.state'); ?>
        <?php if(in_array('state', $required_fields)): ?>
        <span class="required">*</span>
        <?php endif; // required_fields ?>
      </td>
      <td class="fieldVal">
        <?php print ($e ? $this->Form->input('state') : filter_var($addresses[0]['Address']['state'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td class="fieldName">
        <?php print _txt('fd.address.postal_code'); ?>
        <?php if(in_array('postal_code', $required_fields)): ?>
        <span class="required">*</span>
        <?php endif; // required_fields ?>
      </td>
      <td class="fieldVal">
        <?php print ($e ? $this->Form->input('postal_code') : filter_var($addresses[0]['Address']['postal_code'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td class="fieldName">
        <?php print _txt('fd.address.country'); ?>
        <?php if(in_array('country', $required_fields)): ?>
        <span class="required">*</span>
        <?php endif; // required_fields ?>
      </td>
      <td class="fieldVal">
        <?php print ($e ? $this->Form->input('country') : filter_var($addresses[0]['Address']['country'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td class="fieldName">
        <?php print _txt('fd.type'); ?>
      </td>
      <td class="fieldVal">
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
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td class="fieldName">
        <?php print _txt('fd.language'); ?>
      </td>
      <td class="fieldVal">
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
      </td>
    </tr>
    <?php if(!empty($addresses[0]['SourceAddress']['id'])): ?>
    <tr class="line<?php print $l % 2;$l++; ?>">
      <td>
        <?php print _txt('fd.source'); ?><br />
      </td>
      <td>
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
      </td>
    </tr>
    <?php endif; // source id ?>
    <tr>
      <td>
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
      </td>
      <td>
        <?php
          if($e) {
            print $this->Form->submit($submit_label);
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
<?php
  print $this->element("changelog");
