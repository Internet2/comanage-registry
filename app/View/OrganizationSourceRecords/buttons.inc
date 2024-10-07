<?php
/**
 * COmanage Registry Organization Source Record buttons
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

// Add buttons
if($permissions['view']) {
  if(!empty($organization_source_records[0]['Organization']['id'])) {
    // View Organization button
    $params['topLinks'][] = $this->Html->link(
      _txt('op.view-a', array(_txt('ct.organizations.1'))),
      array(
        'controller' => 'organizations',
        'action'     => 'view',
        $organization_source_records[0]['Organization']['id']
      ),
      array('class' => 'viewbutton')
    );
    
    // View Organization Source button
    $params['topLinks'][] = $this->Html->link(
      _txt('op.view-a', array(_txt('ct.organization_sources.1'))),
      array(
        'controller' => 'organization_sources',
        'action'     => 'retrieve',
        $organization_source_records[0]['OrganizationSource']['id'],
        'key'        =>  cmg_urlencode($organization_source_records[0]['OrganizationSourceRecord']['source_key'])
      ),
      array('class' => 'viewbutton')
    );
  }
}
