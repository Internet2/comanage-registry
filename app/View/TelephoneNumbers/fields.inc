<!--
/**
 * COmanage Registry Telephone Number Fields
 *
 * Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  $idForBreadcrumbs = '';

  // Migrate the target person from a named argument to a form field
  if(isset($this->request->params['named']['copersonroleid'])) {
    echo $this->Form->hidden('co_person_role_id', array('default' => Sanitize::html($this->request->params['named']['copersonroleid']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->params['named']['copersonroleid']);
  } elseif(isset($this->request->data['TelephoneNumber']['co_person_role_id'])) {
    echo $this->Form->hidden('co_person_role_id', array('default' => Sanitize::html($this->request->data['TelephoneNumber']['co_person_role_id']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->data['TelephoneNumber']['co_person_role_id']);
  } elseif(isset($this->request->params['named']['orgidentityid'])) {
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->params['named']['orgidentityid']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->params['named']['orgidentityid']);
  } elseif(isset($this->request->data['TelephoneNumber']['org_identity_id'])) {
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->data['TelephoneNumber']['org_identity_id']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->data['TelephoneNumber']['org_identity_id']);
  }

  if(isset($this->params['named']['co']))
    echo $this->Form->hidden('co_id', array('default' => Sanitize::html($this->params['named']['co']))) . "\n";

  // Add breadcrumbs
  if(isset($this->request->params['named']['copersonroleid']) || isset($this->request->data['TelephoneNumber']['co_person_role_id'])) {
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

  } elseif(isset($this->request->params['named']['orgidentityid']) || isset($this->request->data['TelephoneNumber']['org_identity_id'])) {
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

  if($this->action == "add") {
    $this->Html->addCrumb(_txt('op.add') . ' ' . _txt('ct.telephone_numbers.1'));
  }
  if($this->action == "edit") {
    $this->Html->addCrumb(_txt('op.edit') . ' ' . _txt('ct.telephone_numbers.1'));
  }
  if($this->action == "view") {
    $this->Html->addCrumb(_txt('op.view') . ' ' . _txt('ct.telephone_numbers.1'));
  }

?>
<table id="<?php echo $this->action; ?>_telephone_number" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.telephone_number.number'); ?><span class="required">*</span>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('number', array('class' => 'focusFirst')) : Sanitize::html($telephone_numbers[0]['TelephoneNumber']['number'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.type'); ?>
      </td>
      <td>
        <?php
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
      </td>
    </tr>
    <tr>
      <td>
        <i><span class="required"><?php echo _txt('fd.req'); ?></span></i><br />
      </td>
      <td>
        <?php
          if($e) {
            echo $this->Form->submit($submit_label);
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
