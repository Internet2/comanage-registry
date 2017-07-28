<!--
/**
 * COmanage Registry CO Email List Buttons
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php

// Add buttons

if($permissions['provision'] && !empty($co_email_lists[0]['CoEmailList']['id'])) {
  // Provisioning status
  $params['topLinks'][] = $this->Html->link(
    _txt('op.prov.view'),
    array(
      'controller' => 'co_email_lists',
      'action'     => 'provision',
      $co_email_lists[0]['CoEmailList']['id']
    ),
    array('class' => 'runbutton')
  );
}

if($permissions['history'] && !empty($co_email_lists[0]['CoEmailList']['id'])) {
  // View history
  $params['topLinks'][] = $this->Html->link(
    _txt('op.history'),
    array(
      'controller'     => 'history_records',
      'action'         => 'index',
      'coemaillistid'  => $co_email_lists[0]['CoEmailList']['id']
    ),
    array('class' => 'notebutton')
  );
}
