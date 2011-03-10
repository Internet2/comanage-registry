<!--
  /*
   * COmanage Gears CoGroup Index View
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
<h1 class="ui-state-default"><?php
  if($this->action == 'select')
    echo _txt('op.gr.memadd', array($this->params['named']['copersonid']));
  else
    echo _txt('ct.co_groups.pl');
?>
</h1>

<?php
  if($permissions['add'] && $this->action != 'select')
    echo $this->Html->link(_txt('op.add'),
                           array('controller' => 'co_groups', 'action' => 'add', 'co' => $this->params['named']['co']),
                           array('class' => 'addbutton')) . '
    <br />
    <br />
    ';

  if($permissions['edit'] && $this->action == 'select')
  {
    // We're using slightly the wrong permission here... edit group instead of add group member
    // (though they work out the same)
    echo $this->Form->create('CoGroupMember',
                             array('action' => 'add',
                                   'inputDefaults' => array('label' => false,
                                                            'div' => false))) . "\n";
    // beforeFilter needs CO ID
    echo $this->Form->hidden('CoGroupMember.co_id', array('default' => $cur_co['Co']['id'])) . "\n";
    // Group ID must be global for isAuthorized
    echo $this->Form->hidden('CoGroupMember.co_person_id', array('default' => $this->params['named']['copersonid'])) . "\n";
  }
?>

<table id="co_groups" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort(_txt('fd.name'), 'name'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.desc'), 'description'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.open'), 'open'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.status'), 'status'); ?></th>
      <th><?php echo _txt('fd.perms'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_groups as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          // In addition to the usual permissions, an owner can edit and a member can view
          // Anyone can view an open group
          
          $d = $permissions['delete'];
          $e = $permissions['edit'];
          $v = $permissions['view'] || $c['CoGroup']['open'];
          
          if(!empty($permissions['owner'])
             && in_array($c['CoGroup']['id'], $permissions['owner']))
          {
            $d = true;
            $e = true;
          }
          
          if(!empty($permissions['member'])
             && in_array($c['CoGroup']['id'], $permissions['member']))
            $v = true;
        
          if($e || $v)
          {
            echo $html->link($c['CoGroup']['name'],
                             array('controller' => 'co_groups',
                                   'action' => ($e ? 'edit' : ($v ? 'view' : '')), $c['CoGroup']['id'], 'co' => $this->params['named']['co']));
          }
          else
            echo Sanitize::html($c['CoGroup']['name']);
        ?>
      </td>
      <td><?php echo Sanitize::html($c['CoGroup']['description']); ?></td>
      <td><?php echo $c['CoGroup']['open'] ? _txt('fd.open') : _txt('fd.closed'); ?></td>
      <td>
        <?php
          echo _txt('en.status', null, $c['CoGroup']['status']);
        ?>
      </td>
      <td>
        <?php
          if($this->action == 'select')
          {
            if($permissions['select'])
            {
              echo $this->Form->hidden('CoGroupMember.'.$i.'.co_group_id',
                                       array('default' => $c['CoGroup']['id'])) . "\n";
              echo $this->Form->checkbox('CoGroupMember.'.$i.'.member') . _txt('fd.group.mem') . "\n";
              echo $this->Form->checkbox('CoGroupMember.'.$i.'.owner') . _txt('fd.group.own') . "\n";
            }
          }
          else
          {
            if($e)
              echo $html->link(_txt('op.edit'),
                               array('controller' => 'co_groups', 'action' => 'edit', $c['CoGroup']['id'], 'co' => $this->params['named']['co']),
                               array('class' => 'editbutton')) . "\n";
              
            if($d)
              echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . Sanitize::html($c['CoGroup']['name']) . '\', \'' . $html->url(array('controller' => 'co_groups', 'action' => 'delete', $c['CoGroup']['id'], 'co' => $this->params['named']['co'])) . '\')";>' . _txt('op.delete') . '</button>';
          }
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="5">
        <?php echo $this->Paginator->numbers(); ?>
      </td>
    </tr>
    <tr>
      <td>
        <?php
          if($this->action == 'select')
            echo $this->Form->submit(_txt('op.add'));
          
          echo $this->Form->end();
        ?>
      </td>
    </tr>
  </tfoot>
</table>