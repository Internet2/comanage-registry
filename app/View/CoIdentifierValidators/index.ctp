<?php
/**
 * COmanage Registry CO Identifier Validators Index View
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
  $this->Html->addCrumb(_txt('ct.co_identifier_validators.pl'));

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if(!empty($vv_plugins) && $permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.co_identifier_validators.1'))),
      array(
        'controller' => 'co_identifier_validators',
        'action' => 'add',
        'co' => $cur_co['Co']['id']
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);
?>
<?php if(empty($vv_plugins)): ?>
  <div class="co-info-topbox">
    <em class="material-icons">info</em>
    <div class="co-info-topbox-text">
      <?php print _txt('in.idval.plugins'); ?>
    </div>
  </div>
<?php else: // vv_plugins ?>
  <div class="table-container">
    <table id="co_identifier_validators">
      <thead>
        <tr>
          <th><?php print $this->Paginator->sort('description', _txt('fd.desc')); ?></th>
          <th><?php print $this->Paginator->sort('plugin', _txt('fd.plugin')); ?></th>
          <th><?php print $this->Paginator->sort('CoExtendedType.display_name', _txt('fd.attribute')); ?></th>
          <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
          <th><?php print _txt('fd.actions'); ?></th>
        </tr>
      </thead>

      <tbody>
        <?php $i = 0; ?>
        <?php foreach ($co_identifier_validators as $c): ?>
        <tr class="line<?php print ($i % 2)+1; ?>">
          <td>
            <?php
              print $this->Html->link($c['CoIdentifierValidator']['description'],
                                      array('controller' => 'co_identifier_validators',
                                            'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')),
                                            $c['CoIdentifierValidator']['id']));
            ?>
          </td>
          <td>
            <?php
              if(!empty($c['CoIdentifierValidator']['plugin'])) {
                print $c['CoIdentifierValidator']['plugin'];
              }
            ?>
          </td>
          <td>
            <?php
              if(!empty($c['CoExtendedType']['display_name'])) {
                print $c['CoExtendedType']['display_name'] . " (" . $c['CoExtendedType']['attribute'] . ")";
              }
            ?>
          </td>
          <td>
            <?php
              if(!empty($c['CoIdentifierValidator']['status'])) {
                print _txt('en.status', null, $c['CoIdentifierValidator']['status']);
              }
            ?>
          </td>
          <td>
            <?php
              if($permissions['edit']) {
                print $this->Html->link(_txt('op.edit'),
                    array(
                      'controller' => 'co_identifier_validators',
                      'action' => 'edit',
                      $c['CoIdentifierValidator']['id']
                    ),
                    array('class' => 'editbutton')) . "\n";

                // Create a direct link to configuration if this plugin is instantiated
                $plugin = filter_var($c['CoIdentifierValidator']['plugin'],FILTER_SANITIZE_SPECIAL_CHARS);
                $pl = Inflector::underscore($plugin);
                $plm = Inflector::tableize($plugin);

                if($vv_inst_plugins[$plugin]) {
                  print $this->Html->link(_txt('op.config'),
                    array(
                      'plugin' => $pl,
                      'controller' => $plm,
                      'action' => 'edit',
                      $c[$plugin]['id'],
                      'ivid' => $c['CoIdentifierValidator']['id']
                    ),
                    array('class' => 'editbutton')) . "\n";
                }
              }

              if($permissions['delete']) {
                print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                  . '" onclick="javascript:js_confirm_generic(\''
                  . _txt('js.remove') . '\',\''    // dialog body text
                  . $this->Html->url(              // dialog confirm URL
                    array(
                      'controller' => 'co_identifier_validators',
                      'action' => 'delete',
                      $c['CoIdentifierValidator']['id']
                    )
                  ) . '\',\''
                  . _txt('op.remove') . '\',\''    // dialog confirm button
                  . _txt('op.cancel') . '\',\''    // dialog cancel button
                  . _txt('op.remove') . '\',[\''   // dialog title
                  . filter_var(_jtxt($c['CoIdentifierValidator']['description']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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

  <?php print $this->element("pagination"); ?>
<?php endif; // vv_plugins