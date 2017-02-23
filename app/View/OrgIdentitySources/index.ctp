<?php
/**
 * COmanage Registry Organizational Identity Sources Index View
 *
 * Copyright (C) 2015-16 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2015-16 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v1.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Add breadcrumbs
  print $this->element("coCrumb");
  $this->Html->addCrumb(_txt('ct.org_identity_sources.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.org_identity_sources.1'))),
      array(
        'controller' => 'org_identity_sources',
        'action' => 'add',
        'co' => ($pool_org_identities ? false : $cur_co['Co']['id'])
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

?>

<table id="org_identity_sources" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
      <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
      <th><?php print $this->Paginator->sort('sync_mode', _txt('fd.ois.sync.mode')); ?></th>
      <th><?php print $this->Paginator->sort('co_pipeline_id', _txt('fd.pipeline')); ?></th>
      <th><?php print _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($org_identity_sources as $o): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          $plugin = filter_var($o['OrgIdentitySource']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
          $pl = Inflector::underscore($plugin);
          $plmodel = $plugin;
          $plm = Inflector::tableize($plmodel);
          
          print $this->Html->link(
            $o['OrgIdentitySource']['description'],
            array(
              'controller' => 'org_identity_sources',
              'action' => (($permissions['edit'])
                           ? 'edit'
                           : ($permissions['view'] ? 'view' : '')),
              $o['OrgIdentitySource']['id']
            )
          );
        ?>
      </td>
      <td>
        <?php print _txt('en.status.susp', null, $o['OrgIdentitySource']['status']); ?>
      </td>
      <td>
        <?php print _txt('en.sync.mode', null, $o['OrgIdentitySource']['sync_mode']); ?>
      </td>
      <td>
        <?php
          if(!empty($o['OrgIdentitySource']['co_pipeline_id'])) {
            print $vv_co_pipelines[ $o['OrgIdentitySource']['co_pipeline_id'] ];
          }
        ?>
      </td>
      <td>
        <?php
          if($o['OrgIdentitySource']['status'] == SuspendableStatusEnum::Active
             && $permissions['query']) {
            print $this->Html->link(
              _txt('op.search'),
              array(
                'controller' => 'org_identity_sources',
                'action' => 'query',
                $o['OrgIdentitySource']['id']
              ),
              array('class' => 'searchbutton')
            ) . "\n";
          }
          
          if($permissions['edit']) {
            print $this->Html->link(
              _txt('op.edit'),
              array(
                'controller' => 'org_identity_sources',
                'action' => 'edit',
                $o['OrgIdentitySource']['id']
              ),
              array('class' => 'editbutton')
            ) . "\n";
            
            print $this->Html->link(
              _txt('op.config'),
              array(
                'plugin' => $pl,
                'controller' => $plm,
                'action' => 'edit',
                $o[$plmodel]['id']
              ),
              array('class' => 'configurebutton')
            ) . "\n";
            
            if(!empty($vv_plugin_group_attrs[$plmodel])) {
              print $this->Html->link(_txt('op.ois.conf.gr'),
                                      array(
                                        'controller' => 'co_group_ois_mappings',
                                        'action' => 'index',
                                        'org_identity_source' => $o['OrgIdentitySource']['id']
                                      ),
                                      array('class' => 'configurebutton')) . "\n";
            }
          }
          
          if($permissions['inventory']) {
            print '<button type="button" class="viewbutton" title="' . _txt('op.inventory.view')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.ois.inventory') . '\',\''    // dialog body text
              . $this->Html->url(                     // dialog confirm URL
                array(
                  'controller' => 'org_identity_sources',
                  'action' => 'inventory',
                  $o['OrgIdentitySource']['id']
                )
              ) . '\',\''
              . _txt('op.view') . '\',\''      // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.inventory.view') . '\',[\''   // dialog title
              . filter_var(_jtxt($o['OrgIdentitySource']['description']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
              . '\']);">'
              . _txt('op.inventory.view')
              . '</button>';
          }
          
          if($permissions['delete']) {
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.remove') . '\',\''    // dialog body text
              . $this->Html->url(              // dialog confirm URL
                array(
                  'controller' => 'org_identity_sources',
                  'action' => 'delete',
                  $o['OrgIdentitySource']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($o['OrgIdentitySource']['description']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="5">
        <?php print $this->element("pagination"); ?>
      </th>
    </tr>
  </tfoot>
</table>
