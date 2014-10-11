<!--
/**
 * COmanage Registry CO Provisioning Target Index View
 *
 * Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
  $params = array('title' => $title_for_layout);
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.co_provisioning_targets.pl'));

  if($permissions['add']) {
    print $this->Html->link(_txt('op.add') . ' ' . _txt('ct.co_provisioning_targets.1'),
                           array('controller' => 'co_provisioning_targets',
                                 'action' => 'add',
                                 'co' => $cur_co['Co']['id']),
                           array('class' => 'addbutton')) . '
    <br />
    <br />
    ';
  }
?>

<table id="cos" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
      <th><?php print $this->Paginator->sort('plugin', _txt('fd.plugin')); ?></th>
      <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_provisioning_targets as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          $plugin = Sanitize::html($c['CoProvisioningTarget']['plugin']);
          $pl = Inflector::underscore($plugin);
          $plmodel = "Co" . $plugin . "Target";
          
          print $this->Html->link(
            $c['CoProvisioningTarget']['description'],
            array(
              'controller' => 'co_provisioning_targets',
              'action' => (($permissions['edit'])
                           ? 'edit'
                           : ($permissions['view'] ? 'view' : '')),
              $c['CoProvisioningTarget']['id'],
              'co' => $cur_co['Co']['id']
            )
          );
        ?>
      </td>
      <td><?php print $plugin; ?></td>
      <td>
        <?php print _txt('en.status.prov', null, $c['CoProvisioningTarget']['status']); ?>
      </td>
      <td>
        <?php
          if($permissions['edit']) {
            print $this->Html->link(
              _txt('op.edit'),
              array(
                'controller' => 'co_provisioning_targets',
                'action' => 'edit',
                $c['CoProvisioningTarget']['id'],
                'co' => $cur_co['Co']['id']
              ),
              array('class' => 'editbutton')
            ) . "\n";
            
            print $this->Html->link(
              _txt('op.config'),
              array(
                'plugin' => $pl,
                'controller' => 'co_' . $pl . '_targets',
                'action' => 'edit',
                $c[$plmodel]['id']
              ),
              array('class' => 'configurebutton')
            ) . "\n";
          }
            
          if($permissions['delete'])
            print '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\''
                  . _jtxt(Sanitize::html($c['CoProvisioningTarget']['description'])) . '\', \''
                  . $this->Html->url(array('controller' => 'co_provisioning_targets', 'action' => 'delete', $c['CoProvisioningTarget']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>'
                  . _txt('op.delete') . '</button>';
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
        <?php print $this->Paginator->numbers(); ?>
      </th>
    </tr>
  </tfoot>
</table>
