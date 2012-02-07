<!--
/**
 * COmanage Registry Identifier Fields
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
                     isset($redirect) ? $redirect : array('controller' => 'identifiers', 'action' => 'index'),
                     array('class' => 'cancelbutton'));
  else
    echo $this->Html->link(_txt('op.back'),
                           isset($redirect) ? $redirect : array('controller' => 'identifiers', 'action' => 'index'),
                           array('class' => 'backbutton'));
    
  echo '
    <br />
    <br />
    ';
  
  // Migrate the target person from a named argument to a form field
  if(isset($this->request->params['named']['copersonid']))
    echo $this->Form->hidden('co_person_id', array('default' => Sanitize::html($this->request->params['named']['copersonid']))) . "\n";
  elseif(isset($this->request->data['Identifier']['co_person_id']))
    echo $this->Form->hidden('co_person_id', array('default' => Sanitize::html($this->request->data['Identifier']['co_person_id']))) . "\n";
  elseif(isset($this->params['named']['orgidentityid']))
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->params['named']['orgidentityid']))) . "\n";
  elseif(isset($this->request->data['Identifier']['org_identity_id']))
    echo $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->data['Identifier']['org_identity_id']))) . "\n";
    
  if(isset($cur_co))
    echo $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<table id="<?php echo $this->action; ?>_identifier" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.type'); ?>
      </td>
      <td>
        <?php
          global $identifier_t;
          $attrs['value'] = (isset($identifiers) ? $identifiers[0]['Identifier']['type'] : "");
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('type',
                                      $identifier_t,
                                      $attrs);
            
            if($this->Form->isFieldError('type')) {
              print $this->Form->error('type');
            }
          } else {
            print Sanitize::html($identifier_t[ $identifiers[0]['Identifier']['type'] ]);
          }
        ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.id'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('identifier', array('type' => 'text'))
                       : Sanitize::html($identifiers[0]['Identifier']['identifier'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.login'); ?><br />
        <i><?php echo _txt('fd.login.desc'); ?></i>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('login') : Sanitize::html($identifiers[0]['Identifier']['login'])); ?>
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