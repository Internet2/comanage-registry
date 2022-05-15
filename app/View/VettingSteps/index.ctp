<?php
/**
 * COmanage Registry Vetting Steps Index View
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v4.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.vetting_steps.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.vetting_steps.1'))),
      array(
        'controller' => 'vetting_steps',
        'action' => 'add',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
  }

  if($permissions['order']) {
    // Reorder button
    $params['topLinks'][] = $this->Html->link(
      _txt('op.reorder-a', array(_txt('ct.vetting_steps.pl'))),
      array(
        'controller' => 'vetting_steps',
        'action'     => 'order',
        'co' => $cur_co['Co']['id'],
        'direction'  => 'asc',
        'sort'       => 'ordr'
      ),
      array('class' => 'movebutton')
    );
  }
  
  print $this->element("pageTitleAndButtons", $params);
?>
<div class="table-container">
  <table id="vetting_steps">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
        <th><?php print $this->Paginator->sort('plugin', _txt('fd.plugin')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print $this->Paginator->sort('ordr', _txt('fd.order')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($vetting_steps as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            $plugin = filter_var($c['VettingStep']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            $pl = Inflector::underscore($plugin);
            $plmodel = $plugin;

            print $this->Html->link(
              $c['VettingStep']['description'],
              array(
                'controller' => 'vetting_steps',
                'action' => (($permissions['edit'])
                             ? 'edit'
                             : ($permissions['view'] ? 'view' : '')),
                $c['VettingStep']['id'],
                'co' => $cur_co['Co']['id']
              )
            );
          ?>
        </td>
        <td><?php print $plugin; ?></td>
        <td>
          <?php print _txt('en.status', null, $c['VettingStep']['status']); ?>
        </td>
        <td><?php print $c['VettingStep']['ordr']; ?></td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(
                _txt('op.edit'),
                array(
                  'controller' => 'vetting_steps',
                  'action' => 'edit',
                  $c['VettingStep']['id']
                ),
                array('class' => 'editbutton')
              ) . "\n";

              print $this->Html->link(
                _txt('op.config'),
                array(
                  'plugin' => $pl,
                  'controller' => Inflector::pluralize($pl),
                  'action' => 'edit',
                  $c[$plmodel]['id']
                ),
                array('class' => 'configurebutton')
              ) . "\n";
            }

            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'vetting_steps',
                    'action' => 'delete',
                    $c['VettingStep']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($c['VettingStep']['description']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                . '\']);">'
                . _txt('op.delete')
                . '</button>';
            }
          ?>
        </td>
      </tr>
      <?php $i++; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php print $this->element("pagination");