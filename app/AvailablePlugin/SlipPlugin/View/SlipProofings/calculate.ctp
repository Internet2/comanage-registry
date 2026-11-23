<?php

/**
 * COmanage Registry SLIP Proofing Calculation Result View
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

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);

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
    $vv_claimant_co_person['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(generateCn($vv_claimant_co_person['PrimaryName']), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'index',
    'spid' => $vv_slip_plugin['SlipPlugin']['id'],
    'coperson' => $vv_claimant_co_person['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(_txt('ct.slip_proofings.pl'), $args);

  $this->Html->addCrumb($title_for_layout);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  print $this->element("pageTitleAndButtons", $params);

  if(!empty($this->plugin)) {
    if(file_exists(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc")) {
      include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc");
    } elseif(file_exists(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc")) {
      include(LOCAL . "Plugin/" . $this->plugin . "/View/" . $model . "/tabs.inc");
    }
  } else {
    if(file_exists(APP . "View/" . $model . "/tabs.inc")) {
      include(APP . "View/" . $model . "/tabs.inc");
    }
  }

  $l = 1;
?>
<div class="table-container">
  <table id="slip_proofing" class="slip-table">
    <tr>
      <th><?php print _txt('pl.slipplugin.result'); ?></th>
      <td><?php print $vv_status['result'] ? _txt('pl.slipplugin.completed') : _txt('er.slipplugin.calculate'); ?></td>
    </tr>

    <tr>
      <th><?php print _txt('pl.slipplugin.result.status'); ?></th>
      <td><?php print $vv_status['request']['comment']; ?></td>
    </tr>

    <tr>
      <th><?php print _txt('ct.slip_identity_documents.pl'); ?></th>
      <td><?php print $vv_status['documents']['comment']; ?></td>
    </tr>

    <tr>
      <th><?php print _txt('ct.slip_bindings.pl'); ?></th>
      <td><?php print $vv_status['bindings']['comment']; ?></td>
    </tr>

    <tr>
      <th><?php print _txt('pl.slipplugin.vouch.info'); ?></th>
      <td><?php print $vv_status['vouching']['comment']; ?></td>
    </tr>

    <tr>
      <th><?php print _txt('ct.slip_proofings.1'); ?></th>
      <td><?php print $vv_status['slip_proofing']['id']; ?></td>
    </tr>
  </table>
</div>