<!--
/**
 * COmanage Registry Org Identity Sources Buttons
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

if($this->action == "edit" && $permissions['edit']) {
  // Attach data filters
  if(!empty($vv_available_filters)) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.attach', array(_txt('ct.data_filters.pl'))),
      array(
        'controller' => 'org_identity_source_filters',
        'action'     => 'index',
        'oisid'      => $org_identity_sources[0]['OrgIdentitySource']['id']
      ),
      array('class' => 'relinkbutton')
    );
  }
}

if($permissions['query']
   && !empty($org_identity_sources[0]['OrgIdentitySource']['id'])
   && !empty($vv_plugin_group_attrs)) {
  $params['topLinks'][] = $this->Html->link(
    _txt('op.ois.conf.gr'),
    array(
      'controller' => 'co_group_ois_mappings',
      'action' => 'index',
      'org_identity_source' => $org_identity_sources[0]['OrgIdentitySource']['id']
    ),
    array('class' => 'configurebutton')
  );
}
