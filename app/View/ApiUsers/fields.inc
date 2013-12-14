<!--
/**
 * COmanage Registry API Users Fields
 *
 * Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 201 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9
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
    return false;
?>
<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 
0 .7em;"> 
  <p>
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;">
</span>
    <strong><?php print _txt('ap.note.privs'); ?></strong>
  </p>
</div>
<table id="<?php print $this->action; ?>_api_users" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <b><?php print _txt('fd.username.api'); ?></b></b><font class="required">*</font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->input('username')
                     : Sanitize::html($api_users[0]['ApiUser']['username'])); ?>
      </td>
      <td>
        <span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
        <i><?php print _txt('ap.note.username'); ?></i>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <b><?php print _txt('fd.password'); ?></b><font class="required">*</font>
      </td>
      <td>
        <?php print ($e
                     ? $this->Form->password('password')
                     : Sanitize::html($api_users[0]['ApiUser']['password'])); ?>
      </td>
    </tr>
    <tr>
      <td>
        <i><font class="required"><?php echo _txt('fd.req'); ?></font></i><br />
      </td>
      <td>
        <?php
          if($e) {
            print $this->Form->submit($submit_label, array('onClick' => 'on_submit()'));
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
