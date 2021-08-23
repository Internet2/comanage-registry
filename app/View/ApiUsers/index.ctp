<?php
/**
 * COmanage Registry API Users Index View
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
 * @since         COmanage Registry v0.8.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.api_users.pl'));
  
  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.api_users.1'))),
      array(
        'controller' => 'api_users',
        'action' => 'add',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);
?>
<?php if($cur_co['Co']['name'] == DEF_COMANAGE_CO_NAME): ?>
<div class="co-info-topbox">
  <em class="material-icons">info</em>
  <?php print _txt('in.api.cmp'); ?>
</div>
<?php endif; // co name == DEF_COMANAGE_CO_NAME ?>

<div class="table-container">
  <table id="api_users">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('username', _txt('fd.api.username')); ?></th>
        <th><?php print _txt('fd.status'); ?></th>
        <th><?php print _txt('fd.api.key'); ?></th>
        <th><?php print _txt('fd.valid_through'); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($api_users as $a): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            print $this->Html->link($a['ApiUser']['username'],
                                    array('controller' => 'api_users',
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                          $a['ApiUser']['id']));
          ?>
        </td>
        <td><?php print _txt('en.status.susp', null, $a['ApiUser']['status']); ?></td>
        <td><?php print (!empty($a['ApiUser']['password']) ? _txt('fd.set') : _txt('fd.set.not')); ?></td>
        <td><?php if(!empty($a['ApiUser']['valid_through'])) { print $this->Time->format($a['ApiUser']['valid_through'], "%F", false, $vv_tz); } ?></td>
        <td>
          <?php
            if($permissions['edit']) {
              print $this->Html->link(_txt('op.edit'),
                  array(
                    'controller' => 'api_users',
                    'action' => 'edit',
                    $a['ApiUser']['id']
                  ),
                  array('class' => 'editbutton')) . "\n";
            }
            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.remove') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                    array(
                      'controller' => 'api_users',
                      'action' => 'delete',
                      $a['ApiUser']['id']
                    )
                  ) . '\',\''
                . _txt('op.remove') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.remove') . '\',[\''   // dialog title
                . filter_var(_jtxt($a['ApiUser']['username']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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