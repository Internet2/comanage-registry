<?php
/**
 * COmanage Registry Unix Cluster Groups Index View
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
  $args['controller'] = 'clusters';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.clusters.pl'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'clusters';
  $args['action'] = 'edit';
  $args[] = $vv_unix_cluster['Cluster']['id'];
  $this->Html->addCrumb($vv_unix_cluster['Cluster']['description'], $args);
  
  $args = array();
  $args['plugin'] = 'unix_cluster';
  $args['controller'] = 'unix_clusters';
  $args['action'] = 'edit';
  $args[] = $vv_unix_cluster['UnixCluster']['id'];
  $this->Html->addCrumb(_txt('op.config'), $args);
  
  $crumbTxt = _txt('ct.unix_cluster_groups.pl');
  $this->Html->addCrumb($crumbTxt);
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.unix_cluster_groups.1'))),
      array(
        'plugin'     => 'unix_cluster',
        'controller' => 'unix_cluster_groups',
        'action'     => 'add',
        'ucid'       => $vv_unix_cluster['UnixCluster']['id']
      ),
      array('class' => 'addbutton')
    );
  }
  
  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="unix_cluster_groups">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($unix_cluster_groups as $g): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($g['CoGroup']['name'],
                                    array(
                                      'plugin' => '',
                                      'controller' => 'co_groups',
                                      'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                      $g['CoGroup']['id']));
          ?>
        </td>
        <td>
          <?php
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'plugin' => 'unix_cluster',
                    'controller' => 'unix_cluster_groups',
                    'action' => 'delete',
                    $g['UnixClusterGroup']['id'],
                    'ucid' => $g['UnixClusterGroup']['unix_cluster_id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($g['CoGroup']['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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