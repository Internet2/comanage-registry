<?php
/**
 * COmanage Registry CO Extended Attribute Index View
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_extended_attributes.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.co_extended_attributes.1'))),
      array(
        'controller' => 'co_extended_attributes',
        'action' => 'add',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

?>

<table id="cos" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('name', _txt('fd.name')); ?></th>
      <th><?php print $this->Paginator->sort('display_name', _txt('fd.name.d')); ?></th>
      <th><?php print $this->Paginator->sort('type', _txt('fd.type')); ?></th>
      <th><?php print $this->Paginator->sort('indx', _txt('fd.index')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_extended_attributes as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link($c['CoExtendedAttribute']['name'],
                                  array('controller' => 'co_extended_attributes',
                                        'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                        $c['CoExtendedAttribute']['id'],
                                        'co' => $cur_co['Co']['id']));
        ?>
      </td>
      <td><?php print filter_var($c['CoExtendedAttribute']['display_name'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
      <td><?php print filter_var($c['CoExtendedAttribute']['type'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
      <td>
        <?php print $c['CoExtendedAttribute']['indx'] ? _txt('fd.yes') : _txt('fd.no'); ?>
      </td>
      <td>
        <?php
          if($permissions['edit']) {
            print $this->Html->link(_txt('op.edit'),
                array(
                  'controller' => 'co_extended_attributes',
                  'action' => 'edit',
                  $c['CoExtendedAttribute']['id'],
                  'co' => $cur_co['Co']['id']
                ),
                array('class' => 'editbutton')) . "\n";
          }
          if($permissions['delete']) {
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.remove') . '\',\''    // dialog body text
              . $this->Html->url(              // dialog confirm URL
                array(
                  'controller' => 'co_extended_attributes',
                  'action' => 'delete',
                  $c['CoExtendedAttribute']['id'],
                  'co' => $cur_co['Co']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($c['CoExtendedAttribute']['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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
      <th colspan="5">
        <?php print $this->element("pagination"); ?>
      </th>
    </tr>
  </tfoot>
</table>
