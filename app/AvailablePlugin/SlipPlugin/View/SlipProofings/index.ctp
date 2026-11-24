<?php
/**
 * COmanage Registry SLIP Proofings Index View
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

  if(!empty($vv_co_person['ClaimantCoPerson'])) {
    $args = array(
      'plugin' => null,
      'controller' => 'co_people',
      'action' => 'canvas',
      $vv_co_person['ClaimantCoPerson']['id']
    );
    $this->Html->addCrumb(generateCn($vv_co_person['PrimaryName']), $args);
  }

  $this->Html->addCrumb($title_for_layout);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['calculate'] && !empty($this->params['named']['coperson'])) {
    $params['topLinks'][] = $this->Html->link(
      _txt('pl.slipplugin.calculate'),
      array(
        'plugin' => 'slip_plugin',
        'controller' => 'slip_proofings',
        'action' => 'calculate',
        'coperson' => $this->params['named']['coperson']
      ),
      array('class' => 'checkbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

  // Search Block
  if(!empty($vv_search_fields)) {
    print $this->element('search', array('vv_search_fields' => $vv_search_fields));
  }
?>

<div class="table-container">
  <table id="slip_proofings">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('id', _txt('fd.id.seq')); ?></th>
        <th><?php print $this->Paginator->sort('co_person_id', _txt('pl.slipplugin.co_person_id')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print $this->Paginator->sort('registrar_co_person_id', _txt('pl.slipplugin.registrar_co_person_id')); ?></th>
        <th><?php print $this->Paginator->sort('modified', _txt('fd.modified')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($slip_proofings as $s): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php print $s['SlipProofing']['id']; ?>
        </td>
        <td>
          <?php
            if(!empty($s['ClaimantCoPerson']['id'])) {
              print $this->Html->link(
                generateCn($s['ClaimantCoPerson']['PrimaryName']),
                array(
                  'plugin'     => null,
                  'controller' => 'co_people',
                  'action'     => 'canvas',
                  $s['ClaimantCoPerson']['id']
              ));
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($s['SlipProofing']['status'])) {
              print _txt('pl.slipplugin.en.status', null, $s['SlipProofing']['status']);
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($s['RegistrarCoPerson']['id'])) {
              print $this->Html->link(
                generateCn($s['RegistrarCoPerson']['PrimaryName']),
                array(
                  'plugin'     => null,
                  'controller' => 'co_people',
                  'action'     => 'canvas',
                  $s['RegistrarCoPerson']['id']
              ));
            }
          ?>
        </td>
        <td>
            <?php print $this->Time->niceShort($s['SlipProofing']['modified'], $vv_tz); ?>
        </td>
        <td>
          <?php
            if($permissions['view']) {
              print $this->Html->link(_txt('op.view'),
                                      array(
                                        'plugin' => 'slip_plugin',
                                        'controller' => 'slip_proofings',
                                        'action' => 'view',
                                        $s['SlipProofing']['id']),
                                      array('class' => 'viewbutton')) . "\n";
            }

            if($permissions['delete']) {
              print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt('js.delete') . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                  array(
                    'plugin' => 'slip_plugin',
                    'controller' => 'slip_proofings',
                    'action' => 'delete',
                    $s['SlipProofing']['id']
                  )
                ) . '\',\''
                . _txt('op.delete') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('op.delete') . '\',[\''   // dialog title
                . filter_var(_jtxt($s['SlipProofing']['id']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
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