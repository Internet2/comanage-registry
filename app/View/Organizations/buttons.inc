<!--
/**
 * COmanage Registry Organization Buttons
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
 * @since         COmanage Registry v4.4.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php

if(!empty($organizations[0]['OrganizationSourceRecord']['id'])) {
  $params['topLinks'][] = $this->Html->link(
    _txt('op.view-a', array(_txt('ct.organization_source_records.1'))),
    array(
      'controller' => 'organization_source_records',
      'action'     => 'view',
      $organizations[0]['OrganizationSourceRecord']['id']
    ),
    array('class' => 'viewbutton')
  );
}
