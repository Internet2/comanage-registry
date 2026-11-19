<?php

/**
 * COmanage Registry SLIP Proofings Vouching View
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
    $slip_proofings[0]['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(generateCn($slip_proofings[0]['ClaimantCoPerson']['PrimaryName']), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'index',
    'spid' => $slip_proofings[0]['SlipProofing']['slip_plugin_id'],
    'coperson' => $slip_proofings[0]['ClaimantCoPerson']['id']
  );
  $this->Html->addCrumb(_txt('ct.slip_proofings.pl'), $args);

  $args = array(
    'plugin' => 'slip_plugin',
    'controller' => 'slip_proofings',
    'action' => 'view',
    $slip_proofings[0]['SlipProofing']['id']
  );
  $this->Html->addCrumb((string)$slip_proofings[0]['SlipProofing']['id'], $args);

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

  if(!empty($vv_done)) {
    // We just need the template to render so the flash result can render
    return;
  }

  $l = 1;

// XXX turn this into something usable
debug($slip_proofings[0]['SlipProofing']);
debug($slip_proofings[0]['SlipIdentityDocument']);
debug(count($slip_proofings[0]['SlipAttachment']));
?>
<ul id="<?php print $this->action; ?>_slip_vouching_requests" class="fields form-list">
  <li class="fields-submit">
    <div class="field-name">
    </div>
    <div class="field-info">
      <?php 
        print $this->Html->link(
          _txt('op.confirm'),
          array('plugin'      => 'slip_plugin',
                'controller'  => 'slip_proofings',
                'action'      => 'vouch',
                $slip_proofings[0]['SlipProofing']['id'],
                'op'          => 'confirm'),
          array('class' => 'mr-1 checkbutton')
        );

        print $this->Html->link(
          _txt('pl.slipplugin.reject'),
          array('plugin'      => 'slip_plugin',
                'controller'  => 'slip_proofings',
                'action'      => 'vouch',
                $slip_proofings[0]['SlipProofing']['id'],
                'op'          => 'reject'),
          array('class' => 'mr-1 cancelbutton')
        );
    ?>
    </div>
  </li>
</ul>
<? print $this->Form->end();
