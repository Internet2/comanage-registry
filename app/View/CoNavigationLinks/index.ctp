<?php
/**
 * COmanage Registry CO Navigation Link Index View
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_navigation_links.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.co_navigation_links.1'))),
      array(
        'controller' => 'co_navigation_links',
        'action'     => 'add',
        'co'         => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
  }

  if($permissions['order']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.order.link'),
      array(
        'controller' => 'co_navigation_links',
        'action'     => 'order',
        'direction'  => 'asc',
        'sort'       => 'ordr',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'movebutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

?>

<div class="table-container">
  <table id="co_navigation_links">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('title', _txt('fd.link.title')); ?></th>
        <th><?php print $this->Paginator->sort('url', _txt('fd.link.url')); ?></th>
        <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
        <th class="order"><?php print $this->Paginator->sort('ordr', _txt('fd.link.order')); ?></th>
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
        <td><?php print filter_var($c['CoNavigationLink']['url'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print filter_var($c['CoNavigationLink']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print filter_var($c['CoNavigationLink']['ordr'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(_txt('op.edit'),
                  array('controller' => 'co_navigation_links', 'action' => 'edit', $c['CoNavigationLink']['id'], 'co' => $cur_co['Co']['id']),
                  array('class' => 'editbutton')) . "\n";
            }
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'co_navigation_links',
                    'action' => 'delete',
                    $c['CoNavigationLink']['id'],
                    'co' => $cur_co['Co']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($c['CoNavigationLink']['title']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('op.delete')
                . '</button>';
            }
          ?>
          <?php ; ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
  print $this->element("pagination");
