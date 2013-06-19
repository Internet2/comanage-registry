<!--
/**
 * COmanage Registry CO Group Index View
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
  if($this->action == 'select') {
    $params = array('title' => _txt('op.gr.memadd',
                                    array($name_for_title)
                                   )
                   );
  } else {
    $params = array('title' => _txt('ct.co_groups.pl'));
  }
  print $this->element("pageTitle", $params);

  if($permissions['add'] && $this->action != 'select') {
    print $this->Html->link(_txt('op.add'),
                            array('controller' => 'co_groups',
                                  'action' => 'add',
                                  'co' => $cur_co['Co']['id']),
                            array('class' => 'addbutton'));
    
    print $this->Html->link(_txt('op.grm.manage'),
                            array('controller' => 'co_groups',
                                  'action' => 'select',
                                  'copersonid' => $this->Session->read('Auth.User.co_person_id'),
                                  'co' => $cur_co['Co']['id']),
                            array('class' => 'linkbutton')) . '
    <br />
    <br />
    ';
  }
  
  if($permissions['select'] && $this->action == 'select') {
    // We're using slightly the wrong permission here... edit group instead of add group member
    // (though they work out the same)
    print $this->Form->create('CoGroupMember',
                              array('action' => 'update',
                                    'inputDefaults' => array('label' => false,
                                                             'div' => false))) . "\n";
    // beforeFilter needs CO ID
    print $this->Form->hidden('CoGroupMember.co_id', array('default' => $cur_co['Co']['id'])) . "\n";
    // Group ID must be global for isAuthorized
    print $this->Form->hidden('CoGroupMember.co_person_id', array('default' => $this->request->params['named']['copersonid'])) . "\n";
  }
?>

<table id="co_groups" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort('name', _txt('fd.name')); ?></th>
      <th><?php echo $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
      <th><?php echo $this->Paginator->sort('open', _txt('fd.open')); ?></th>
      <th><?php echo $this->Paginator->sort('status', _txt('fd.status')); ?></th>
      <th><?php echo _txt('fd.actions'); ?></th>
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
            echo $this->Html->link($c['CoGroup']['name'],
                                    array('controller' => 'co_groups',
                                          'action' => ($e ? 'edit' : ($v ? 'view' : '')), $c['CoGroup']['id'], 'co' => $this->request->params['named']['co']));
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
          if($this->action == 'select') {
            if($permissions['select']) {
              print $this->Form->hidden('CoGroupMember.rows.'.$i.'.co_group_id',
                                        array('default' => $c['CoGroup']['id'])) . "\n";
              
              // We toggle the disabled status of the checkbox based on a person's permissions.
              // A CO(U) Admin can edit any membership or ownership.
              // A group owner can edit any membership or ownership for that group.
              // Anyone can add or remove themself from or two an open group.
              
              $gmID = null;
              $isMember = false;
              $isOwner = false;
              
              foreach($c['CoGroupMember'] as $cgm) {
                // Walk the CoGroupMemberships for this CoGroup to find the target CO Person
                if($cgm['co_person_id'] == $this->request->params['named']['copersonid']) {
                  $gmID = $cgm['id'];
                  $isMember = $cgm['member'];
                  $isOwner = $cgm['owner'];
                  break;
                }
              }
              
              if($gmID) {
                // Populate the cross reference
                print $this->Form->hidden('CoGroupMember.rows.'.$i.'.id',
                                          array('default' => $gmID)) . "\n";
              }
              
              print $this->Form->checkbox('CoGroupMember.rows.'.$i.'.member',
                                          array('disabled' => !($permissions['selectany']
                                                                || $c['CoGroup']['open']
                                                                || $isMember
                                                                || $isOwner),
                                                'checked'    => $isMember))
                    . _txt('fd.group.mem') . "\n";
              
              print $this->Form->checkbox('CoGroupMember.rows.'.$i.'.owner',
                                          array('disabled' => !($permissions['selectany']
                                                                || $isOwner),
                                                'checked'    => $isOwner))
                    . _txt('fd.group.own') . "\n";
            }
          }
          else {
            if($e)
              echo $this->Html->link(_txt('op.edit'),
                               array('controller' => 'co_groups', 'action' => 'edit', $c['CoGroup']['id'], 'co' => $this->request->params['named']['co']),
                               array('class' => 'editbutton')) . "\n";
              
            if($d)
              echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($c['CoGroup']['name'])) . '\', \'' . $this->Html->url(array('controller' => 'co_groups', 'action' => 'delete', $c['CoGroup']['id'], 'co' => $this->params['named']['co'])) . '\')";>' . _txt('op.delete') . '</button>';
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
      </th>
    </tr>
    <tr>
      <td>
        <?php
          if($this->action == 'select') {
            print $this->Form->submit(_txt('op.save'));
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));

          }
          
          print $this->Form->end();
        ?>
      </td>
    </tr>
  </tfoot>
</table>
