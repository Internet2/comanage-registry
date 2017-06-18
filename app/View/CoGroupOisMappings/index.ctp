<?php
/**
 * COmanage Registry CO Group OIS Mapping Index View
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  
  $args = array(
    'controller' => 'org_identity_sources',
    'action'     => 'edit',
    $vv_ois_id
  );
  $this->Html->addCrumb(_txt('ct.org_identity_sources.1'), $args);
  
  $this->Html->addCrumb(_txt('ct.co_group_ois_mappings.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.co_group_ois_mappings.1'))),
      array(
        'controller' => 'co_group_ois_mappings',
        'action' => 'add',
        'org_identity_source' => $vv_ois_id
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
<table id="co_group_ois_mappings">
  <thead>
    <tr>
      <th><?php print $this->Paginator->sort('attribute', _txt('fd.attribute')); ?></th>
      <th><?php print $this->Paginator->sort('comparison', _txt('fd.comparison')); ?></th>
      <th><?php print $this->Paginator->sort('pattern', _txt('fd.pattern')); ?></th>
      <th><?php print $this->Paginator->sort('CoGroup.name', _txt('ct.co_groups.1')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_group_ois_mappings as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link($c['CoGroupOisMapping']['attribute'],
                                  array('controller' => 'co_group_ois_mappings',
                                        'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                        $c['CoGroupOisMapping']['id']));
        ?>
      </td>
      <td>
        <?php
          if(!empty($c['CoGroupOisMapping']['comparison'])) {
            print _txt('en.comparison', null, $c['CoGroupOisMapping']['comparison']);
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($c['CoGroupOisMapping']['pattern'])) {
            print $c['CoGroupOisMapping']['pattern'];
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($c['CoGroup']['name'])) {
            print $c['CoGroup']['name'];
          }
        ?>
      </td>
      <td>
        <?php
          if($permissions['edit']) {
            print $this->Html->link(_txt('op.edit'),
                array(
                  'controller' => 'co_group_ois_mappings',
                  'action' => 'edit',
                  $c['CoGroupOisMapping']['id']
                ),
                array('class' => 'editbutton')) . "\n";
          }
          if($permissions['delete']) {
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.remove') . '\',\''    // dialog body text
              . $this->Html->url(              // dialog confirm URL
                array(
                  'controller' => 'co_group_ois_mappings',
                  'action' => 'delete',
                  $c['CoGroupOisMapping']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($c['CoGroupOisMapping']['pattern']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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
  
<?php
  print $this->element("pagination");
