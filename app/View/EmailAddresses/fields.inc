<!--
/**
 * COmanage Registry Email Address Fields
 *
 * Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-13 University Corporation for Advanced Internet Development, Inc.
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
  
  // Migrate the target person from a named argument to a form field
  if(isset($this->request->params['named']['copersonid']))
    echo $this->Form->hidden('co_person_id', array('default' => Sanitize::html($this->request->params['named']['copersonid']))) . "\n";
  elseif(isset($this->request->data['EmailAddress']['co_person_id']))
    echo $this->Form->hidden('co_person_id', array('default' => Sanitize::html($this->request->data['EmailAddress']['co_person_id']))) . "\n";
  elseif(isset($this->request->params['named']['orgidentityid']))
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->params['named']['orgidentityid']))) . "\n";
  elseif(isset($this->request->data['EmailAddress']['org_identity_id']))
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->data['EmailAddress']['org_identity_id']))) . "\n";
    
  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  // Is the current email address verified?
  $isVerified = (isset($email_addresses[0]['EmailAddress']['verified'])
                  && $email_addresses[0]['EmailAddress']['verified']);
?>
<table id="<?php echo $this->action; ?>_email_address" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php print _txt('fd.email_address.mail'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('mail') : Sanitize::html($email_addresses[0]['EmailAddress']['mail'])); ?>
      </td>
      <?php if($e && $isVerified): ?>
      <td>
        <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
        <i><?php print _txt('fd.email_address.verified.warn'); ?></i>
      </td>
      <?php endif; ?>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.type'); ?>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts, $contact_t;
          $attrs['value'] = (isset($email_addresses) ? $email_addresses[0]['EmailAddress']['type'] : "O");
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('type',
                                      $cm_texts[ $cm_lang ]['en.contact.mail'],
                                      $attrs);
            
            if($this->Form->isFieldError('type')) {
              print $this->Form->error('type');
            }
          } else {
            print Sanitize::html($contact_t[ $email_addresses[0]['EmailAddress']['type'] ]);
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
            print '<button class="checkbutton" title="Verify" type="button"
                    onclick="javascript:js_confirm_verification(\'' 
                    . _jtxt(Sanitize::html($email_addresses[0]['EmailAddress']['mail'])) 
                    . '\', \'' 
                    . $this->Html->url($url)
                    . '\');">'
                    . "Verify"
                    . '</button>' . "\n";
          }
        ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><font class="required"><?php echo _txt('fd.req'); ?></font></i><br />
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
