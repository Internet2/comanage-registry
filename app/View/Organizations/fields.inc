<!--
  /*
   * COmanage Gears Organization Fields
   *
   * Version: $Revision: 81 $
   * Date: $Date: 2011-07-17 19:53:10 -0400 (Sun, 17 Jul 2011) $
   *
   * Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
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
    
  if(!$e)
  {
    echo $this->Html->link(_txt('op.back'),
                           array('controller' => 'organizations', 'action' => 'index'),
                           array('class' => 'backbutton')) . '
    <br />
    <br />
    ';
  }
?>
<table id="<?php echo $this->action; ?>_organization" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.name'); ?><font class="required">*</font>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('name') : Sanitize::html($organizations[0]['Organization']['name'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.domain'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('domain') : Sanitize::html($organizations[0]['Organization']['domain'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.directory'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('directory') : Sanitize::html($organizations[0]['Organization']['directory'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.searchbase'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('search_base') : Sanitize::html($organizations[0]['Organization']['search_base'])); ?>
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
            print $this->Html->link(
              _txt('op.cancel'),
              array('controller' => 'organizations', 'action' => 'index'),
              array('class' => 'cancelbutton')
            );
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>