<!--
  /*
   * COmanage Gears CoGroups Common Fields
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
  // Determine if fields are editable or viewable
  $dok = false;
  $e = false;
  $v = false;
  
  if(($this->action == "add" && $permissions['add'])
     || ($this->action == "edit" && $permissions['edit'])
     || (($this->action == 'add' || $this->action == 'edit')
         && isset($co_groups[0]['CoGroup']['id'])
         && !empty($permissions['owner'])
         && in_array($co_groups[0]['CoGroup']['id'], $permissions['owner'])))
    $e = true;

  if(($permissions['delete'])
     || (isset($co_groups[0]['CoGroup']['id'])
         && !empty($permissions['owner'])
         && in_array($co_groups[0]['CoGroup']['id'], $permissions['owner'])))
    $dok = true;

  if(($this->action == "view" && $permissions['view'])
     || (isset($co_groups[0]['CoGroup']['id'])
         && !empty($permissions['member'])
         && in_array($co_groups[0]['CoGroup']['id'], $permissions['member']))
     || (isset($co_groups[0]['CoGroup']['open']) && $co_groups[0]['CoGroup']['open']))
    $v = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$v)
    return(false);
    
  if($e)
    echo $html->link(_txt('op.cancel'),
                     array('controller' => 'co_groups', 'action' => 'index', 'co' => $cur_co['Co']['id']),
                     array('class' => 'cancelbutton'));
  else
    echo $this->Html->link(_txt('op.back'),
                           array('controller' => 'co_groups', 'action' => 'index', 'co' => $cur_co['Co']['id']),
                           array('class' => 'backbutton'));
    
  // Populate the reference
  echo $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])). "\n";

  echo '
    <br />
    <br />
    ';
?>
<table id="<?php echo $this->action; ?>_co_group" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.name'); ?><font class="required">*</font>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('name') : Sanitize::html($co_groups[0]['CoGroup']['name'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.desc'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('description') : Sanitize::html($co_groups[0]['CoGroup']['description'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.open'); ?>
      </td>
      <td>
        <?php echo ($e ? $this->Form->input('open') : ($co_groups[0]['CoGroup']['open'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php echo _txt('fd.status'); ?>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          
          echo ($e ? $this->Form->select('status',
                                         $cm_texts[ $cm_lang ]['en.status'],
                                         (isset($co_groups) ? $co_groups[0]['CoGroup']['status'] : 'A'),
                                         array('empty' => false))
                   : _txt('en.status', null, $co_groups[0]['CoGroup']['status']));
        ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php echo _txt('fd.members'); ?>
      </td>
      <td>
        <?php
        
          if(isset($co_group_members))
          {
            if($e)
            {
              foreach($co_group_members as $c)
              {
                foreach($c['CoPersonRole']['CoGroupMember'] as $g)
                {
                  // We get all the group memberships, but are only interested in the current group
                  if($g['co_group_id'] == $co_groups[0]['CoGroup']['id'])
                  {
                    if($dok)
                      echo '<a class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(_txt('fd.group.memin', array(Sanitize::html($co_groups[0]['CoGroup']['name'])))) . '\', \'' . $html->url(array('controller' => 'co_group_members', 'action' => 'delete', $g['id'], 'copersonroleid' => $g['co_person_role_id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</a>' . "\n";
                    echo $html->link(generateCn($c['CoPersonRole']['Name']), array('controller' => 'co_group_members', 'action' => 'edit', $g['id'], 'co' => $cur_co['Co']['id']));
                    echo "<br />\n";
                    break;
                  }
                }
              }

              echo $html->link(_txt('op.add'),
                               array('controller' => 'co_group_members',
                                     'action' => 'select',
                                     'cogroup' => $co_groups[0]['CoGroup']['id'],
                                     'co' => $cur_co['Co']['id']),
                               array('class' => 'addbutton'));
            }
            else
            {
              foreach($co_group_members as $c)
                echo Sanitize::html(generateCn($c['CoPerson']['Name'])) . "<br />\n";
            }
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