<?php
/**
 * COmanage Registry Email Address Fields
 *
 * Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
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
    print $this->Form->hidden('co_person_id', array('default' => Sanitize::html($this->request->params['named']['copersonid']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->params['named']['copersonid']);
  } elseif(isset($this->request->data['EmailAddress']['co_person_id'])) {
    print $this->Form->hidden('co_person_id', array('default' => Sanitize::html($this->request->data['EmailAddress']['co_person_id']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->data['EmailAddress']['co_person_id']);
  } elseif(isset($this->request->params['named']['orgidentityid'])) {
    print $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->params['named']['orgidentityid']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->params['named']['orgidentityid']);
  } elseif(isset($this->request->data['EmailAddress']['org_identity_id'])) {
    print $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->data['EmailAddress']['org_identity_id']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->data['EmailAddress']['org_identity_id']);
  }

  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  // Is the current email address verified?
  $isVerified = (isset($email_addresses[0]['EmailAddress']['verified'])
                  && $email_addresses[0]['EmailAddress']['verified']);

  // Add breadcrumbs
  print $this->element("coCrumb");
  if(isset($this->request->params['named']['copersonid']) || isset($this->request->data['EmailAddress']['co_person_id'])) {
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

  } elseif(isset($this->request->params['named']['orgidentityid']) || isset($this->request->data['EmailAddress']['org_identity_id'])) {
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
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.email_addresses.1')));
  $this->Html->addCrumb($crumbTxt);

?>
<table id="<?php print $this->action; ?>_email_address" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php print _txt('fd.email_address.mail'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('mail', array('class' => 'focusFirst')) : Sanitize::html($email_addresses[0]['EmailAddress']['mail'])); ?>
      </td>
      <?php if($e && $isVerified): ?>
      <td>
        <span class="ui-icon ui-icon-info co-info"></span>
        <em><?php print _txt('fd.email_address.verified.warn'); ?></em>
      </td>
      <?php endif; ?>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('fd.type'); ?>
      </td>
      <td>
        <?php
          $attrs['value'] = (isset($email_addresses) ? $email_addresses[0]['EmailAddress']['type'] : "");
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('type',
                                      $vv_available_types,
                                      $attrs);
            
            if($this->Form->isFieldError('type')) {
              print $this->Form->error('type');
            }
          } else {
            print Sanitize::html(_txt('en.email_address.type', null, $email_addresses[0]['EmailAddress']['type']));
          }
        ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('fd.email_address.verified'); ?>
      </td>
      <td>
        <?php
          // For an add, verified is by definition no and can't (currently, anyway) be set manually.
          // This will be caught by $email_addresses being null.
          
          if($this->action != 'add') {
            $url = array(
              'controller' => 'co_invites',
              'action' => 'verifyEmailAddress',
              'email_address_id' => $email_addresses[0]['EmailAddress']['id']
            );
            
            if(!empty($cur_co['Co']['id'])) {
              $url['co'] = $cur_co['Co']['id'];
            }
          }
          
          print ($isVerified ? _txt('fd.yes') : _txt('fd.no')) . "\n";
          
          if(!$isVerified && $permissions['verifyEmailAddress']) {
            print '<button class="checkbutton" title="' . _txt('op.verify')
              . '" type="button" onclick="javascript:js_confirm_generic(\''
              . _txt('js.confirm.verify') . '\',\''   // dialog body text
              . $this->Html->url($url) . '\',\''      // redirect URL
              . _txt('op.verify') . '\',\''           // dialog confirm button
              . _txt('op.cancel') . '\',\''           // dialog cancel button
              . _txt('op.verify') . '\',[\''          // dialog title
              . filter_var(_jtxt($email_addresses[0]['EmailAddress']['mail']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
              . '\']);">'
              . _txt('op.verify')
              . '</button>' . "\n";
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
