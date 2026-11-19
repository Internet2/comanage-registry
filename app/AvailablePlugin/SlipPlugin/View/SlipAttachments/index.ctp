<?php
/**
 * COmanage Registry SLIP Attachments Index View
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
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Add breadcrumbs
  print $this->element("coCrumb");

  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'index',
    'co' => $cur_co['Co']['id']
  );
  $this->Html->addCrumb(_txt('me.population'), $args);

  $args = array(
    'plugin' => null,
    'controller' => 'co_people',
    'action' => 'canvas',
    $vv_slip_proofing['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(generateCn($vv_slip_proofing['ClaimantCoPerson']['PrimaryName']), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'index',
    'spid' => $vv_slip_proofing['SlipProofing']['slip_plugin_id'],
    'coperson' => $vv_slip_proofing['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(_txt('ct.slip_proofings.pl'), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'view',
    $vv_slip_proofing['SlipProofing']['id']
  );
  $this->Html->addCrumb((string)$vv_slip_proofing['SlipProofing']['id'], $args);

  $this->Html->addCrumb($title_for_layout);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a',array(_txt('ct.slip_attachments.1'))),
      array(
        'plugin' => 'slip_plugin',
        'controller' => 'slip_attachments',
        'action' => 'add',
        'sprid' => $this->request->params['named']['sprid']
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="slip_attachments">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('id', _txt('fd.id.seq')); ?></th>
        <th><?php print $this->Paginator->sort('description', _txt('fd.description')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($slip_attachments as $s): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            if(!empty($s['SlipAttachment']['id'])) {
              print $s['SlipAttachment']['id'];
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($s['SlipAttachment']['description'])) {
              print filter_var($s['SlipAttachment']['description'],FILTER_SANITIZE_STRING);
            }
          ?>
        </td>
        <td>
          <?php
            if($permissions['view']) {
              print $this->Html->link(_txt('op.view'),
                                      array(
                                        'plugin' => 'slip_plugin',
                                        'controller' => 'slip_attachments',
                                        'action' => 'view',
                                        $s['SlipAttachment']['id']),
                                      array('class' => 'viewbutton')) . "\n";
            }

            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.delete') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'plugin' => 'slip_plugin',
                    'controller' => 'slip_attachments',
                    'action' => 'delete',
                    $s['SlipAttachment']['id']
                  )
                ) . '\',\''
                . _txt('op.delete') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.delete') . '\',[\''   // dialog title
                . filter_var(_jtxt($s['SlipAttachment']['id']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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