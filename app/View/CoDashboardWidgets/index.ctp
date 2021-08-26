<?php
/**
 * COmanage Registry CO Dashboard Widget Index View
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
 * @since         COmanage Registry v3.2.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_dashboards';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_dashboards.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_dashboards';
  $args['action'] = 'edit';
  $args[] = $vv_db_id;
  $this->Html->addCrumb($vv_db_name, $args);
  
  $this->Html->addCrumb(_txt('ct.co_dashboard_widgets.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.co_dashboard_widgets.1'))),
      array(
        'controller' => 'co_dashboard_widgets',
        'action' => 'add',
        'codashboard' => $vv_db_id
      ),
      array('class' => 'addbutton')
    );
  }

  if($permissions['order']) {
    // Reorder button
    $params['topLinks'][] = $this->Html->link(
      _txt('op.order-a', array(_txt('ct.co_dashboard_widgets.pl'))),
      array(
        'controller'  => 'co_dashboard_widgets',
        'action'      => 'order',
        'codashboard' => $vv_db_id,
        'direction'   => 'asc',
        'sort'        => 'ordr'
      ),
      array('class' => 'movebutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="co_enrollment_sources">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
        <th><?php print $this->Paginator->sort('plugin', _txt('fd.plugin')); ?></th>
        <th class="order"><?php print $this->Paginator->sort('ordr', _txt('fd.order')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_dashboard_widgets as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($c['CoDashboardWidget']['description'],
                                    array('controller' => 'co_dashboard_widgets',
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                          $c['CoDashboardWidget']['id']));
          ?>
        </td>
        <td><?php print filter_var($c['CoDashboardWidget']['plugin'],FILTER_SANITIZE_STRING); ?></td>
        <td><?php print $c['CoDashboardWidget']['ordr']; ?></td>
        <td>
          <?php
            if($permissions['edit']) {
              $plugin = Inflector::underscore($c['CoDashboardWidget']['plugin']);
              $plmodel = "Co" . $c['CoDashboardWidget']['plugin'];
              
              // Edit is probably too restrictive of a permission, but given only
              // admins can get to this button that's OK.
              print $this->Html->link(_txt('op.render'),
                array(
                  'plugin' => $plugin,
                  'controller' => 'co_' . Inflector::tableize(Inflector::underscore($c['CoDashboardWidget']['plugin'])),
                  // render is already a (private) Cake function
                  'action' => 'display',
                  $c[$plmodel]['id']
                ),
                array('class' => 'runbutton')) . "\n";
              
              print $this->Html->link(_txt('op.edit'),
                array(
                  'controller' => 'co_dashboard_widgets',
                  'action' => 'edit',
                  $c['CoDashboardWidget']['id']
                ),
                array('class' => 'editbutton')) . "\n";
              
              print $this->Html->link(_txt('op.config'),
                array(
                  'plugin' => $plugin,
                  'controller' => 'co_' . Inflector::tableize(Inflector::underscore($c['CoDashboardWidget']['plugin'])),
                  'action' => 'edit',
                  $c[$plmodel]['id']
                ),
                array('class' => 'configurebutton')) . "\n";
            }
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'co_dashboard_widgets',
                    'action' => 'delete',
                    $c['CoDashboardWidget']['id'],
                    'codashboard' => $vv_db_id
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($c['CoDashboardWidget']['description']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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