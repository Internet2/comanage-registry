<!--
/**
 * COmanage Registry CO Index View
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
 * @copyright     Copyright (C) 2010-12 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<h1 class="ui-state-default"><?php print $title_for_layout; ?></h1>

<?php
  if($permissions['add'])
    print $this->Html->link(_txt('op.add') . ' ' . _txt('ct.cos.1'),
                           array('controller' => 'cos', 'action' => 'add'),
                           array('class' => 'addbutton')) . '
    <br />
    <br />
    ';
?>

<table id="cos" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('name', _txt('fd.name')); ?></th>
      <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
      <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($cos as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link(
            $c['Co']['name'],
            array(
              'controller' => 'cos',
              'action' => (($permissions['edit'] && $c['Co']['name'] != 'COmanage')
                           ? 'edit'
                           : ($permissions['view'] ? 'view' : '')),
              $c['Co']['id']
            )
          );
        ?>
      </td>
      <td><?php print Sanitize::html($c['Co']['description']); ?></td>
      <td>
        <?php print _txt('en.status', null, $c['Co']['status']); ?>
      </td>
      <td>
        <?php
          if($permissions['edit'] && $c['Co']['name'] != 'COmanage')
            print $this->Html->link(
              _txt('op.edit'),
              array(
                'controller' => 'cos',
                'action' => 'edit',
                $c['Co']['id']
              ),
              array('class' => 'editbutton')
            ) . "\n";
            
          if($permissions['delete'] && $c['Co']['name'] != 'COmanage')
            print '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\''
                  . _jtxt(Sanitize::html($c['Co']['name'])) . '\', \''
                  . $this->Html->url(array('controller' => 'cos', 'action' => 'delete', $c['Co']['id'])) . '\')";>'
                  . _txt('op.delete') . '</button>';
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="4">
        <?php print $this->Paginator->numbers(); ?>
      </td>
    </tr>
  </tfoot>
</table>