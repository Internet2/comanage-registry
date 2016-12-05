<?php
/**
 * COmanage Registry Organization Index View
 *
 * Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.organizations.pl'));

  // Add page title
  $params = array();
  $params['title'] = _txt('ct.organizations.pl');

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.organizations.1'))),
      array(
        'controller' => 'organizations',
        'action' => 'add'
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

?>

<table id="organizations" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort(_txt('fd.name'), 'name'); ?></th>
      <th><?php print $this->Paginator->sort(_txt('fd.domain'), 'domain'); ?></th>
      <th><?php print $this->Paginator->sort(_txt('fd.directory'), 'directory'); ?></th>
      <th><?php print _txt('fd.searchbase'); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($organizations as $o): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td><?php print $this->Html->link(
                  $o['Organization']['name'],
                  array(
                    'controller' => 'organizations',
                    'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                    $o['Organization']['id'])
                ); ?></td>
      <td><?php print filter_var($o['Organization']['domain'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
      <td><?php print filter_var($o['Organization']['directory'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
      <td><?php print filter_var($o['Organization']['search_base'],FILTER_SANITIZE_SPECIAL_CHARS); ?></td>
      <td>
        <?php
          print $this->Html->link(
            _txt('op.edit'),
            array('controller' => 'organizations', 'action' => 'edit', $o['Organization']['id']),
            array('class' => 'editbutton')
          ) . "\n";
            
          if($permissions['delete']) {
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.remove') . '\',\''    // dialog body text
              . $this->Html->url(              // dialog confirm URL
                array(
                  'controller' => 'organizations',
                  'action' => 'delete',
                  $o['Organization']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($o['Organization']['name']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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
