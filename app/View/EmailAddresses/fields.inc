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
    
  if($e)
    echo $this->Html->link(_txt('op.cancel'),
                           isset($redirect) ? $redirect : array('controller' => 'email_addresses', 'action' => 'index'),
                           array('class' => 'cancelbutton'));
    
  echo '
    <br />
    <br />
    ';
  
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
?>
<table id="<?php echo $this->action; ?>_email_address" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.email_address.mail'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('mail') : Sanitize::html($email_addresses[0]['EmailAddress']['mail'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.type'); ?>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
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
          
          print (isset($email_addresses[0]['EmailAddress']['verified'])
                 && $email_addresses[0]['EmailAddress']['verified']
                 ? _txt('fd.yes') : _txt('fd.no'));
        ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><font class="required"><?php echo _txt('fd.req'); ?></font></i><br />
      </td>
      <td>
        <?php
          if($e)
            echo $this->Form->submit($submit_label);
        ?>
      </td>
    </tr>
  </tbody>
</table>
