<!--
/**
 * COmanage Registry CO Navigation Link Index View
 *
 * Copyright (C) 2013-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */-->
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.co_navigation_links.pl'));

  // Add buttons to sidebar
  $sidebarButtons = $this->get('sidebarButtons');
  
  // Add button
  if($permissions['add']) {
    $sidebarButtons[] = array(
      'icon'    => 'circle-plus',
      'title'   => _txt('op.add-a',array(_txt('ct.co_navigation_links.1'))),
      'url'     => array(
        'controller' => 'co_navigation_links', 
        'action'     => 'add',
        'co'         => $cur_co['Co']['id']
      )
    );
  }

  if($permissions['order']) {
    // Reorder button
    $sidebarButtons[] = array(
      'icon'    => 'pencil',
      'title'   => _txt('op.order.link'),
      'url'     => array(
        'controller' => 'co_navigation_links',
        'action'     => 'order',
        'direction'  => 'asc',
        'sort'       => 'ordr',
        'co' => $cur_co['Co']['id']
      )
    );
  }

  $this->set('sidebarButtons', $sidebarButtons);
?>

<table id="co_navigation_links" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('title', _txt('fd.link.title')); ?></th>
      <th><?php print $this->Paginator->sort('url', _txt('fd.link.url')); ?></th>
      <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
      <th><?php print $this->Paginator->sort('ordr', _txt('fd.link.order')); ?></th>

      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_navigation_links as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link($c['CoNavigationLink']['title'],
                                  array('controller' => 'co_navigation_links',
                                        'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $c['CoNavigationLink']['id'], 'co' => $cur_co['Co']['id']));
        ?>
      </td>
      <td><?php print Sanitize::html($c['CoNavigationLink']['url']); ?></td>
      <td><?php print Sanitize::html($c['CoNavigationLink']['description']); ?></td>
      <td><?php print Sanitize::html($c['CoNavigationLink']['ordr']); ?></td>
      <td>
        <?php
          if($permissions['edit'])
            print $this->Html->link(_txt('op.edit'),
                                    array('controller' => 'co_navigation_links', 'action' => 'edit', $c['CoNavigationLink']['id'], 'co' => $cur_co['Co']['id']),
                                    array('class' => 'editbutton')) . "\n";
            
          if($permissions['delete'])
            print '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($c['CoNavigationLink']['title'])) . '\', \'' . $this->Html->url(array('controller' => 'co_navigation_links', 'action' => 'delete', $c['CoNavigationLink']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . '</button>';
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
        <?php print $this->Paginator->numbers(); ?>
      </th>
    </tr>
  </tfoot>
</table>
