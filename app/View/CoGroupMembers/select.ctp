<!--
/**
 * COmanage Registry CO Group Member Select View
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
  $params = array('title' => _txt('op.grm.add', array($cur_co['Co']['name'], $co_group['CoGroup']['name'])));
  print $this->element("pageTitle", $params);

  echo $this->Html->link(_txt('op.cancel'),
                         array('controller' => 'co_groups',
                               'action' => 'edit',
                               $co_group['CoGroup']['id'],
                               'co' => $cur_co['Co']['id']),
                         array('class' => 'cancelbutton')) . '
  <br />
  <br />
  ';
?>

<table id="co_people" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort(_txt('fd.name'), 'Name.family'); ?></th>
      <th>Permissions</th>
    </tr>
    <?php
      echo $this->Form->create('CoGroupMember',
                               array('action' => 'add',
                                     'inputDefaults' => array('label' => false,
                                                              'div' => false))) . "\n";
      // beforeFilter needs CO ID
      echo $this->Form->hidden('CoGroupMember.co_id', array('default' => $cur_co['Co']['id'])) . "\n";
      // Group ID must be global for isAuthorized
      echo $this->Form->hidden('CoGroupMember.co_group_id', array('default' => $co_group['CoGroup']['id'])) . "\n";
    ?>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_people as $p): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php echo Sanitize::html(generateCn($p['Name'])); ?></td>
      <td>
        <?php
          echo $this->Form->hidden('CoGroupMember.'.$i.'.co_person_id',
                                   array('default' => $p['CoPerson']['id'])) . "\n";
          echo $this->Form->checkbox('CoGroupMember.'.$i.'.member') . _txt('fd.group.mem') . "\n";
          echo $this->Form->checkbox('CoGroupMember.'.$i.'.owner') . _txt('fd.group.own') . "\n";
        ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="8">
        <?php echo $this->Paginator->numbers(); ?>
      </th>
    </tr>
    <tr>
      <td>
        <?php
          echo $this->Form->submit(_txt('op.add'));
          echo $this->Form->end();
        ?>
      </td>
    </tr>
  </tfoot>
</table>
