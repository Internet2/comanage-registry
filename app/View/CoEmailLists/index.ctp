<?php
/**
 * COmanage Registry CO Email Lists Index View
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  $e = $permissions['edit'];
  $v = $permissions['view'];
  $d = $permissions['delete'];

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.co_email_lists.pl'));

  // Add page title
  $params = array();
  $params['title'] =  _txt('ct.co_email_lists.pl');

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.co_email_lists.1'))),
      array(
        'controller' => 'co_email_lists',
        'action' => 'add',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);
?>
<table id="co_email_lists" class="ui-widget">
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
    <?php foreach ($co_email_lists as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          if($e || $v) {
            print $this->Html->link($c['CoEmailList']['name'],
                                    array('controller' => 'co_email_lists',
                                          'action' => ($e ? 'edit' : ($v ? 'view' : '')),
                                          $c['CoEmailList']['id']));
          } else {
            print filter_var($c['CoEmailList']['name'],FILTER_SANITIZE_SPECIAL_CHARS);
          }
        ?>
      </td>
      <td><?php print filter_var($c['CoEmailList']['description'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
      <td>
        <?php
          print _txt('en.status', null, $c['CoEmailList']['status']);
        ?>
      </td>
      <td class="actions">
        <?php
          if($e) {
            print $this->Html->link(_txt('op.edit'),
                                    array('controller' => 'co_email_lists',
                                          'action' => 'edit',
                                          $c['CoEmailList']['id']),
                                    array('class' => 'editbutton'))
            . "\n";
          }
          elseif($v) {
            print $this->Html->link(_txt('op.view'),
                                    array('controller' => 'co_email_lists',
                                          'action'     => 'view',
                                          $c['CoEmailList']['id']),
                                    array('class'      => 'viewbutton'))
            . "\n";
          }
          
          if($d) {
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.remove') . '\',\''    // dialog body text
              . $this->Html->url(              // dialog confirm URL
                array(
                  'controller' => 'co_email_lists',
                  'action' => 'delete',
                  $c['CoEmailList']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($c['CoEmailList']['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="4">
        <?php print $this->element("pagination"); ?>
      </th>
    </tr>
    <tr>
      <td colspan="4"></td>
    </tr>
  </tfoot>
</table>
