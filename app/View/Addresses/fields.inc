<!--
/**
 * COmanage Registry Address Fields
 *
 * Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */-->
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
                           isset($redirect) ? $redirect : array('controller' => 'addresses', 'action' => 'index'),
                           array('class' => 'cancelbutton'));
    
  echo '
    <br />
    <br />
    ';
  
  // Migrate the target person from a named argument to a form field
  if(isset($this->request->params['named']['copersonroleid']))
    echo $this->Form->hidden('co_person_role_id', array('default' => Sanitize::html($this->request->params['named']['copersonroleid']))) . "\n";
  elseif(isset($this->request->data['Address']['co_person_role_id']))
    echo $this->Form->hidden('co_person_role_id', array('default' => Sanitize::html($this->request->data['Address']['co_person_role_id']))) . "\n";
  elseif(isset($this->request->params['named']['orgidentityid']))
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->params['named']['orgidentityid']))) . "\n";
  elseif(isset($this->request->data['Address']['org_identity_id']))
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->data['Address']['org_identity_id']))) . "\n";
    
  if(isset($this->request->params['named']['co']))
    echo $this->Form->hidden('co_id', array('default' => Sanitize::html($this->request->params['named']['co']))) . "\n";
?>
<table id="<?php echo $this->action; ?>_address" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.address.line1'); ?><font class="required">*</font>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('line1') : Sanitize::html($addresses[0]['Address']['line1'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.address.line2'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('line2') : Sanitize::html($addresses[0]['Address']['line2'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.address.locality'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('locality') : Sanitize::html($addresses[0]['Address']['locality'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.address.state'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('state') : Sanitize::html($addresses[0]['Address']['state'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.address.postal_code'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('postal_code') : Sanitize::html($addresses[0]['Address']['postal_code'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.address.country'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('country') : Sanitize::html($addresses[0]['Address']['country'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.type'); ?>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($addresses) ? $addresses[0]['Address']['type'] : "O");
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('type',
                                      $cm_texts[ $cm_lang ]['en.contact'],
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
