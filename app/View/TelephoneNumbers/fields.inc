<!--
/**
 * COmanage Registry Telephone Number Fields
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
                           isset($redirect) ? $redirect : array('controller' => 'telephone_numbers', 'action' => 'index'),
                           array('class' => 'cancelbutton'));

    
  echo '
    <br />
    <br />
    ';
  
  // Migrate the target person from a named argument to a form field
  if(isset($this->request->params['named']['copersonroleid']))
    echo $this->Form->hidden('co_person_role_id', array('default' => Sanitize::html($this->request->params['named']['copersonroleid']))) . "\n";
  elseif(isset($this->request->data['TelephoneNumber']['co_person_role_id']))
    echo $this->Form->hidden('co_person_role_id', array('default' => Sanitize::html($this->request->data['TelephoneNumber']['co_person_role_id']))) . "\n";
  elseif(isset($this->request->params['named']['orgidentityid']))
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->params['named']['orgidentityid']))) . "\n";
  elseif(isset($this->request->data['TelephoneNumber']['org_identity_id']))
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->data['TelephoneNumber']['org_identity_id']))) . "\n";
    
  if(isset($this->params['named']['co']))
    echo $this->Form->hidden('co_id', array('default' => Sanitize::html($this->params['named']['co']))) . "\n";
?>
<table id="<?php echo $this->action; ?>_telephone_number" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.telephone_number.number'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('number') : Sanitize::html($telephone_numbers[0]['TelephoneNumber']['number'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.type'); ?>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($telephone_numbers) ? $telephone_numbers[0]['TelephoneNumber']['type'] : "O");
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('type',
                                      $cm_texts[ $cm_lang ]['en.contact'],
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
