<?php
/**
 * COmanage Registry Group Filter Rules Index View
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
 * @package       registry-plugin
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'data_filters';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.data_filters.pl'), $args);

  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'data_filters';
  $args['action'] = 'edit';
  $args[] = $vv_datafilter['id'];
  $this->Html->addCrumb($vv_datafilter['description'], $args);
  
  $this->Html->addCrumb(_txt('ct.group_filter_rules.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.group_filter_rules.1'))),
      array(
        'plugin' => 'group_filter',
        'controller' => 'group_filter_rules',
        'action' => 'add',
        'groupfilter' => $vv_groupfilter['id']
      ),
      array('class' => 'addbutton')
    );
  }
  
  if($permissions['order']) {
    // Reorder button
    $params['topLinks'][] = $this->Html->link(
      _txt('op.order-a', array(_txt('ct.group_filter_rules.pl'))),
      array(
        'plugin'     => 'group_filter',
        'controller' => 'group_filter_rules',
        'action'     => 'order',
        'groupfilter' => $vv_groupfilter['id'],
        'direction'  => 'asc',
        'sort'       => 'ordr'
      ),
      array('class' => 'movebutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="group_filter_rules">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('name_pattern', _txt('pl.groupfilter.name')); ?></th>
        <th><?php print $this->Paginator->sort('ordr', _txt('fd.order')); ?></th>
        <th><?php print $this->Paginator->sort('required', _txt('fd.required')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($group_filter_rules as $g): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($g['GroupFilterRule']['name_pattern'],
                                    array('controller' => 'group_filter_rules',
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                          $g['GroupFilterRule']['id']));
          ?>
        </td>
        <td><?php print $g['GroupFilterRule']['ordr']; ?></td>
        <td><?php print filter_var(_txt('en.required', null, $g['GroupFilterRule']['required']),FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(_txt('op.edit'),
                  array(
                    'controller' => 'group_filter_rules',
                    'action' => 'edit',
                    $g['GroupFilterRule']['id']
                  ),
                  array('class' => 'editbutton')) . "\n";
            }
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'group_filter_rules',
                    'action' => 'delete',
                    $g['GroupFilterRule']['id'],
                    'groupfilter' => $g['GroupFilterRule']['group_filter_id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($g['GroupFilterRule']['name_pattern']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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