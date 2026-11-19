<?php
/**
 * COmanage Registry SLIP Bindings Index View
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

  print $this->element("pageTitleAndButtons", $params);
?>

<div class="table-container">
  <table id="slip_bindings">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('id', _txt('fd.id.seq')); ?></th>
        <th><?php print $this->Paginator->sort('authenticated_identifier', _txt('pl.slipplugin.authenticated_identifier')); ?></th>
        <th><?php print $this->Paginator->sort('status', _txt('fd.status')); ?></th>
        <th><?php print $this->Paginator->sort('verification_time', _txt('pl.slipplugin.verification_time')); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
      <?php $i = 0; ?>
      <?php foreach ($slip_bindings as $s): ?>
      <tr class="line<?php print ($i % 2)+1; ?>">
        <td>
          <?php
            if(!empty($s['SlipBinding']['id'])) {
              print $s['SlipBinding']['id'];
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($s['SlipBinding']['authenticated_identifier'])) {
              print filter_var($s['SlipBinding']['authenticated_identifier'],FILTER_SANITIZE_STRING);
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($s['SlipBinding']['status'])) {
              print _txt('pl.slipplugin.en.binding', null, $s['SlipBinding']['status']);
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($s['SlipBinding']['verification_time'])) {
              print $this->Time->format($s['SlipBinding']['verification_time'], "%c $vv_tz", false, $vv_tz);
            }
          ?>
        </td>
        <td>
          <?php
            if(!empty($s['SlipBinding']['mail'])) {
              if($permissions['request']) {
                // As a first pass, we always allow a request to be generated, even if the
                // Authenticator is already Bound. This will allow an updated Verification Time
                // to be recorded.

                print '<button type="button" class="invitebutton" title="' . _txt('op.delete')
                  . '" onclick="javascript:js_confirm_generic(\''
                  . _txt('pl.slipplugin.request.js') . '\',\''    // dialog body text
                  . $this->Html->url(              // dialog confirm URL
                    array(
                      'plugin' => 'slip_plugin',
                      'controller' => 'slip_bindings',
                      'action' => 'request',
                      'sprid' => $s['SlipBinding']['slip_proofing_id'],
                      'identifier' => cmg_urlencode($s['SlipBinding']['authenticated_identifier'])
                    )
                  ) . '\',\''
                  . _txt('pl.slipplugin.request') . '\',\''    // dialog confirm button
                  . _txt('op.cancel') . '\',\''    // dialog cancel button
                  . _txt('pl.slipplugin.request') . '\',[\''   // dialog title
                  . filter_var(_jtxt($s['SlipBinding']['authenticated_identifier']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                  . '\']);">'
                  . _txt('pl.slipplugin.request')
                  . '</button>';
              }
            } else {
              print _txt('er.slipplugin.mail');
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
