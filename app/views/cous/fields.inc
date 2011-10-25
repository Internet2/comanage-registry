<!--
  /*
   * COmanage Gears COU Common Fields
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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
    echo $html->link(_txt('op.cancel'),
                     array('controller' => 'cous', 'action' => 'index'),
                     array('class' => 'cancelbutton'));
  else
    echo $this->Html->link(_txt('op.back'),
                           array('controller' => 'cous', 'action' => 'index'),
                           array('class' => 'backbutton'));
    
  echo '
    <br />
    <br />
    ';

  if(isset($this->params['named']['co']))
    echo $this->Form->hidden('co_id', array('default' => Sanitize::html($this->params['named']['co']))) . "\n";
?>
<table id="<?php echo $this->action; ?>_cou" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.name'); ?><font class="required">*</font>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('name') : Sanitize::html($cous[0]['Cou']['name'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.desc'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('description') : Sanitize::html($cous[0]['Cou']['description'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.parent'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->select('parent_id', $this->viewVars['parent_options']) : Sanitize::html($cous[0]['Cou']['parent_id'])); ?>
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
