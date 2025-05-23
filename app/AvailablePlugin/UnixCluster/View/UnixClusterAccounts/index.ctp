<?php
/**
 * COmanage Registry Unix Cluster Accounts Index View
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
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'canvas';
  $args[] = $vv_co_person['CoPerson']['id'];
  $this->Html->addCrumb(generateCn($vv_co_person['PrimaryName']), $args);
  
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'clusters';
  $args['action'] = 'status';
  $args['copersonid'] = $vv_co_person['CoPerson']['id'];
  $this->Html->addCrumb(_txt('ct.clusters.pl'), $args);
  
  $this->Html->addCrumb($title_for_layout);
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.unix_cluster_accounts.1'))),
      array(
        'plugin'     => 'unix_cluster',
        'controller' => 'unix_cluster_accounts',
        'action'     => 'add',
        'ucid'       => $vv_unix_cluster['UnixCluster']['id'],
        'copersonid' => $vv_co_person['CoPerson']['id']
      ),
      array('class' => 'addbutton')
    );
  }
  
  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="unix_cluster_accounts">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
        <th><?php print $this->Paginator->sort('username', _txt('pl.unixcluster.fd.username')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($unix_cluster_accounts as $a): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($a['UnixClusterAccount']['gecos'],
                                    array(
                                      'plugin' => 'unix_cluster',
                                      'controller' => 'unix_cluster_accounts',
                                      'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                      $a['UnixClusterAccount']['id']));
          ?>
        </td>
        <td><?php print filter_var($a['UnixClusterAccount']['username'], FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
        <td><?php print _txt('en.status', null, $a['UnixClusterAccount']['status']); ?></td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(
                _txt('op.edit'),
                array(
                  'plugin' => 'unix_cluster',
                  'controller' => 'unix_cluster_accounts',
                  'action' => 'edit',
                  $a['UnixClusterAccount']['id']
                ),
                array('class' => 'editbutton')
              ) . "\n";
            }
            
            if($permissions['delete']) {
              print $this->element('buttonToConfirmDialog', array(
                  'data' => array(
                    'btnTitle' => _txt('op.delete'),
                    'action' => _txt('op.delete'),
                    'dialogTitle' => _txt('op.delete'),
                    'bodyText' => _txt('js.delete'),
                    'bodyTxtReplacementStrings' => array(
                            filter_var(_jtxt($a['UnixClusterAccount']['gecos']),FILTER_SANITIZE_STRING),
                    ),
                    'checkBoxText' => _txt('pl.unixcluster.rs.delete.groups'),
                    'confirmUrl' =>   array(
                      'plugin' => 'unix_cluster',
                      'controller' => 'unix_cluster_accounts',
                      'action' => 'delete',
                      $a['UnixClusterAccount']['id'],
                      'ucid' => $a['UnixClusterAccount']['unix_cluster_id'],
                      'copersonid' => $a['UnixClusterAccount']['co_person_id'],
                    ),
                  )
                )
              );
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