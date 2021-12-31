<?php
/**
 * COmanage Registry Data Filters Index View
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
 * @since         COmanage Registry v3.3.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.data_filters.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.data_filters.1'))),
      array(
        'controller' => 'data_filters',
        'action' => 'add',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="data_filters">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print $this->Paginator->sort('context', _txt('fd.df.context')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($data_filters as $d): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            $plugin = filter_var($d['DataFilter']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
            $pl = Inflector::underscore($plugin);
            $plmodel = $plugin;
            $plm = Inflector::tableize($plmodel);

            print $this->Html->link(
              $d['DataFilter']['description'],
              array(
                'controller' => 'data_filters',
                'action' => (($permissions['edit'])
                             ? 'edit'
                             : ($permissions['view'] ? 'view' : '')),
                $d['DataFilter']['id']
              )
            );
          ?>
        </td>
        <td>
          <?php print _txt('en.status.susp', null, $d['DataFilter']['status']); ?>
        </td>
        <td>
          <?php print _txt('en.df.context', null, $d['DataFilter']['context']); ?>
        </td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(
                _txt('op.edit'),
                array(
                  'controller' => 'data_filters',
                  'action' => 'edit',
                  $d['DataFilter']['id']
                ),
                array('class' => 'editbutton')
              ) . "\n";

              print $this->Html->link(
                _txt('op.config'),
                array(
                  'plugin' => $pl,
                  'controller' => $plm,
                  'action' => 'edit',
                  $d[$plmodel]['id']
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
                    'controller' => 'data_filters',
                    'action' => 'delete',
                    $d['DataFilter']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($d['DataFilter']['description']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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

<?php
  print $this->element("pagination");
