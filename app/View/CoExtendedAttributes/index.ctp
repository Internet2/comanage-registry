<!--
/**
 * COmanage Registry CO Extended Attribute Index View
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.co_extended_attributes.pl'));

  // Add buttons to sidebar
  $sidebarButtons = $this->get('sidebarButtons');

  if($permissions['add']){
    $sidebarButtons[] = array(
      'icon'    => 'circle-plus',
      'title'   => _txt('op.add-a',array(_txt('ct.co_extended_attributes.1'))),
      'url'     => array(
        'controller' => 'co_extended_attributes',
        'action' => 'add',
        'co' => $cur_co['Co']['id']
      )
    );
  }

  $this->set('sidebarButtons', $sidebarButtons);
?>

<table id="cos" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort('name', _txt('fd.name')); ?></th>
      <th><?php echo $this->Paginator->sort('display_name', _txt('fd.name.d')); ?></th>
      <th><?php echo $this->Paginator->sort('type', _txt('fd.type')); ?></th>
      <th><?php echo $this->Paginator->sort('indx', _txt('fd.index')); ?></th>
      <th><?php echo _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_extended_attributes as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          echo $this->Html->link($c['CoExtendedAttribute']['name'],
                                  array('controller' => 'co_extended_attributes',
                                        'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                        $c['CoExtendedAttribute']['id'],
                                        'co' => $cur_co['Co']['id']));
        ?>
      </td>
      <td><?php echo Sanitize::html($c['CoExtendedAttribute']['display_name']); ?></td>
      <td><?php echo Sanitize::html($c['CoExtendedAttribute']['type']); ?></td>
      <td>
        <?php echo $c['CoExtendedAttribute']['indx'] ? _txt('fd.yes') : _txt('fd.no'); ?>
      </td>
      <td>
        <?php
          if($permissions['edit'])
            echo $this->Html->link(_txt('op.edit'),
                                    array('controller' => 'co_extended_attributes',
                                          'action' => 'edit',
                                          $c['CoExtendedAttribute']['id'],
                                          'co' => $cur_co['Co']['id']),
                                    array('class' => 'editbutton')) . "\n";
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($c['CoExtendedAttribute']['name'])) . '\', \'' . $this->Html->url(array('controller' => 'co_extended_attributes', 'action' => 'delete', $c['CoExtendedAttribute']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</button>';
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
  </tfoot>
</table>
