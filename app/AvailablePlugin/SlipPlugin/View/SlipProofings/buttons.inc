<?php
/**
 * COmanage Registry SLIP Proofings Buttons
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
 * @package       registry-plugin
 * @since         COmanage Registry v4.7.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Add buttons

if($permissions['attach']) {
  // Manage Identity Documents
  $buttonUrl = array(
    'plugin'        => 'slip_plugin',
    'controller'    => 'slip_identity_documents',
    'action'        => 'index',
    'sprid'         => $slip_proofings[0]['SlipProofing']['id']
  );

  $params['topLinks'][] = $this->Html->link(
    _txt('pl.slipplugin.attach'),
    $buttonUrl,
    array(
      'class' => 'addbutton'
    )
  );
}

if($permissions['bind']) {
  // Manage Authenticator Bindings
  $buttonUrl = array(
    'plugin'        => 'slip_plugin',
    'controller'    => 'slip_bindings',
    'action'        => 'index',
    'sprid'         => $slip_proofings[0]['SlipProofing']['id']
  );

  $params['topLinks'][] = $this->Html->link(
    _txt('pl.slipplugin.bind'),
    $buttonUrl,
    array(
      'class' => 'addbutton'
    )
  );
}

if($permissions['upload']) {
  // Manage Attachments
  $buttonUrl = array(
    'plugin'        => 'slip_plugin',
    'controller'    => 'slip_attachments',
    'action'        => 'index',
    'sprid'         => $slip_proofings[0]['SlipProofing']['id']
  );

  $params['topLinks'][] = $this->Html->link(
    _txt('pl.slipplugin.upload'),
    $buttonUrl,
    array(
      'class' => 'addbutton'
    )
  );
}

if($permissions['request']
   && in_array($slip_proofings[0]['SlipProofing']['status'],
               array(
                 SlipProofingStatusEnum::Assigned,
                 SlipProofingStatusEnum::Scheduled,
                 SlipProofingStatusEnum::PendingVouching
               ))) {
  // Request vouching
  $buttonUrl = array(
    'plugin'        => 'slip_plugin',
    'controller'    => 'slip_proofings',
    'action'        => 'request',
    $slip_proofings[0]['SlipProofing']['id']
  );

  $params['topLinks'][] = $this->Html->link(
    _txt('pl.slipplugin.vouch'),
    $buttonUrl,
    array(
      'class' => 'forwardbutton'
    )
  );
}

if($permissions['schedule']
   && $slip_proofings[0]['SlipProofing']['status'] == SlipProofingStatusEnum::Assigned) {
  // An assigned Proofing can be scheduled
  $buttonUrl = array(
    'plugin'        => 'slip_plugin',
    'controller'    => 'slip_proofings',
    'action'        => 'schedule',
    $slip_proofings[0]['SlipProofing']['id']
  );

  $params['topLinks'][] = $this->Html->link(
    _txt('pl.slipplugin.schedule'),
    $buttonUrl,
    array(
      'class' => 'schedulebutton'
    )
  );
}

if($permissions['accept']
   && !empty($slip_proofings[0]['SlipProofing']['status'])
   && !in_array($slip_proofings[0]['SlipProofing']['status'],
                array(
                  SlipProofingStatusEnum::Canceled,
                  SlipProofingStatusEnum::Completed
                ))) {
  // An in-progress Proofing can be reassigned
  $buttonUrl = array(
    'plugin'        => 'slip_plugin',
    'controller'    => 'slip_proofings',
    'action'        => 'accept',
    $slip_proofings[0]['SlipProofing']['id']
  );

  $params['topLinks'][] = $this->Html->link(
    _txt('pl.slipplugin.accept'),
    $buttonUrl,
    array(
      'class' => 'forwardbutton'
    )
  );
}

if($permissions['cancel']
   && !empty($slip_proofings[0]['SlipProofing']['status'])
   && !in_array($slip_proofings[0]['SlipProofing']['status'],
                array(
                  SlipProofingStatusEnum::Canceled,
                  SlipProofingStatusEnum::Completed
                ))) {
  // An in-progress Proofing can be canceled
  $buttonUrl = array(
    'plugin'        => 'slip_plugin',
    'controller'    => 'slip_proofings',
    'action'        => 'cancel',
    $slip_proofings[0]['SlipProofing']['id']
  );

  // Construct jQuery popup
  $onclickAction =  "javascript:js_confirm_generic('" .
                    _txt('pl.slipplugin.cancel.confirm') .
                    "', '" . Router::url($buttonUrl) . "', '" .
                    _txt('fd.yes'). "', '" .
                    _txt('fd.no') . "');
                    return false;";

  $params['topLinks'][] = $this->Html->link(
    _txt('op.cancel'),
    $buttonUrl,
    array(
      'class' => 'cancelbutton',
      'onclick' => $onclickAction
    )
  );
}
