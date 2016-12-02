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
  
  // Migrate the target person from a named argument to a form field
  if(isset($this->request->params['named']['copersonid'])) {
    print $this->Form->hidden('co_person_id', array('default' => filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_FULL_SPECIAL_CHARS))) . "\n";
    $idForBreadcrumbs = filter_var($this->request->params['named']['copersonid'],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  } elseif(isset($this->request->data['Identifier']['co_person_id'])) {
    print $this->Form->hidden('co_person_id', array('default' => filter_var($this->request->data['Identifier']['co_person_id'],FILTER_SANITIZE_FULL_SPECIAL_CHARS))) . "\n";
    $idForBreadcrumbs = filter_var($this->request->data['Identifier']['co_person_id'],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  } elseif(isset($this->params['named']['orgidentityid'])){
    print $this->Form->hidden('org_identity_id', array('default' => filter_var($this->request->params['named']['orgidentityid'],FILTER_SANITIZE_FULL_SPECIAL_CHARS))) . "\n";
    $idForBreadcrumbs = filter_var($this->request->params['named']['orgidentityid'],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  } elseif(isset($this->request->data['Identifier']['org_identity_id'])) {
    print $this->Form->hidden('org_identity_id', array('default' => filter_var($this->request->data['Identifier']['org_identity_id'],FILTER_SANITIZE_FULL_SPECIAL_CHARS))) . "\n";
    $idForBreadcrumbs = filter_var($this->request->data['Identifier']['org_identity_id'],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  }

  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
    
  // Are we editing an org identity or a CO Person?
  $isOrgIdentity = true;
  
  if(!empty($this->request->params['named']['copersonid'])
     || !empty($this->request->data['Identifier']['co_person_id'])) {
    $isOrgIdentity = false;
  }

  // Add breadcrumbs
  print $this->element("coCrumb");
  if(!$isOrgIdentity) {
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
?>
<table id="<?php print $this->action; ?>_identifier" class="ui-widget">
  <tbody>
    <tr class="line<?php print $l % 2;$l++; ?>">
      <td>
        <?php print _txt('fd.type'); ?>
      </td>
      <td>
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
      </td>
    </tr>
    <tr class="line<?php print $l % 2;$l++; ?>">
      <td>
        <?php print _txt('fd.identifier.identifier'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('identifier', array('type' => 'text','class' => 'focusFirst'))
                       : filter_var($identifiers[0]['Identifier']['identifier'],FILTER_SANITIZE_FULL_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <?php if($isOrgIdentity): ?>
    <tr class="line<?php print $l % 2;$l++; ?>">
      <td>
        <?php print _txt('fd.identifier.login'); ?><br />
        <em><?php print _txt('fd.identifier.login.desc'); ?></em>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('login') : filter_var($identifiers[0]['Identifier']['login'],FILTER_SANITIZE_FULL_SPECIAL_CHARS)); ?>
      </td>
    </tr>
    <?php else: // $isOrgIdentity ?>
    <?php print $this->Form->hidden('login', array('default' => false)) . "\n"; ?>
    <?php endif; // $isOrgIdentity ?>
    <tr class="line<?php print $l % 2;$l++; ?>">
      <td>
        <?php print _txt('fd.status'); ?>
      </td>
      <td>
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
      </td>
    </tr>
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
