<!--
/**
 * COmanage Registry SQL Provisioner Buttons
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
 * @link          https://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
  if($permissions['reapply']) {
    // Reapply the target database schema
    
    $params['topLinks'][] = $this->Html->link(
      _txt('pl.sqlprovisioner.reapply'),
      array(
        'plugin'     => 'sql_provisioner',
        'controller' => 'co_sql_provisioner_targets',
        'action'     => 'reapply',
        $co_sql_provisioner_targets[0]['CoSqlProvisionerTarget']['id']
      ),
      array(
        'class' => 'runbutton'
      )
    );
    
    // Resync the reference data
    
    $params['topLinks'][] = $this->Html->link(
      _txt('pl.sqlprovisioner.resync'),
      array(
        'plugin'     => 'sql_provisioner',
        'controller' => 'co_sql_provisioner_targets',
        'action'     => 'resync',
        $co_sql_provisioner_targets[0]['CoSqlProvisionerTarget']['id']
      ),
      array(
        'class' => 'reconcilebutton'
      )
    );
  }
