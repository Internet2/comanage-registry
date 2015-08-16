<!--
/**
 * COmanage Registry CO Enrollment Configuration Fields
 *
 * Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
// Add buttons
if($this->action != "add") {
  // History button
  $params['topLinks'][] = $this->Html->link(
    _txt('op.history'),
    array(
      'controller'    => 'history_records',
      'action'        => 'index',
      'orgidentityid' => $org_identities[0]['OrgIdentity']['id'],
      'co'            => ($pool_org_identities ? false : $cur_co['Co']['id'])
    ),
    array('class' => 'notebutton')
  );

  // Petitions
  $params['topLinks'][] = $this->Html->link(
    _txt('ct.co_petitions.pl'),
    array(
      'controller' => 'co_petitions',
      'action' => 'index',
      // Since this is an index view, we still need the CO ID. paginationConditions
      // will restrict searching to eligible enrollments.
      'co' => ($pool_org_identities ? false : $cur_co['Co']['id']),
      'sort' => 'modified',
      'search.orgidentityid' => $org_identities[0]['OrgIdentity']['id']
    ),
    array('class' => 'petitionbutton')
  );

}