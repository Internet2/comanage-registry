<?php
/**
 * COmanage Registry CO Self Service Permissions Index View
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
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.co_self_service_permissions.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a',array(_txt('ct.co_self_service_permissions.1'))),
      array(
        'controller' => 'co_self_service_permissions',
        'action' => 'add',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

?>

<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <div class="co-info-topbox-text">
    <?php print _txt('fd.ssp.default'); ?>
  </div>
</div>

<div class="table-container">
  <table id="co_self_service_permissions">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('model', _txt('fd.model')); ?></th>
        <th><?php print $this->Paginator->sort('type', _txt('fd.type')); ?></th>
        <th><?php print $this->Paginator->sort('permission', _txt('fd.perm')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($co_self_service_permissions as $c): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link(_txt('ct.'.Inflector::tableize($c['CoSelfServicePermission']['model']).'.1'),
                                    array('controller' => 'co_self_service_permissions',
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                          $c['CoSelfServicePermission']['id']));
          ?>
        </td>
        <td>
          <?php
            if(!empty($c['CoSelfServicePermission']['type'])) {
              print $types[ $c['CoSelfServicePermission']['model'] ][ $c['CoSelfServicePermission']['type'] ];
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($c['CoSelfServicePermission']['permission'])) {
              print _txt('en.permission', null, $c['CoSelfServicePermission']['permission']);
            }
          ?>
        </td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(_txt('op.edit'),
                  array('controller' => 'co_self_service_permissions',
                    'action' => 'edit',
                    $c['CoSelfServicePermission']['id']),
                  array('class' => 'editbutton')) . "\n";
            }

            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'controller' => 'co_self_service_permissions',
                    'action' => 'delete',
                    $c['CoSelfServicePermission']['id']
                  )
                ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($c['CoSelfServicePermission']['model']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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
