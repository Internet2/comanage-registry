<!--
/**
 * COmanage Registry CO Group Member Fields
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
  // Determine if fields are editable or viewable
  $e = ($this->action == 'edit' && $permissions['edit']);
  $v = ($this->action == 'view' && $permissions['view']);
  
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$v)
    return(false);
    
  if($e)
    echo $this->Html->link(_txt('op.cancel'),
                            array('controller' => 'co_groups',
                                  'action' => 'edit',
                                  $this->request->data['CoGroupMember']['co_group_id'],
                                  'co' => $cur_co['Co']['id']),
                            array('class' => 'cancelbutton'));

  if($e)
  {
    // Populate the references
    echo $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])). "\n";
    echo $this->Form->hidden('id', array('default' => $this->request->data['CoGroupMember']['id'])). "\n";
    echo $this->Form->hidden('co_group_id', array('default' => $this->request->data['CoGroupMember']['co_group_id'])). "\n";
    echo $this->Form->hidden('co_person_id', array('default' => $this->request->data['CoGroupMember']['co_person_id'])). "\n";
  }

  echo '
    <br />
    <br />
    ';
?>
<table id="<?php echo $this->action; ?>_co_group_member" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.group.mem'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('member') : ($co_group_members[0]['CoGroupMember']['member'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.group.own'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('owner') : ($co_group_members[0]['CoGroupMember']['owner'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
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
