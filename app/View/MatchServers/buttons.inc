<?php
/**
 * COmanage Registry Match Server Buttons
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
 * @package       registry
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Determine if fields are editable
$e = false;

if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
  $e = true;

// We shouldn't get here if we don't have at least read permission, but check just in case
if(!$e && !$permissions['view'])
  return(false);

if($e) {
  // Manage Match Server Attributes button
  if($this->action == "edit" && $permissions['edit']){
    $params['topLinks'][] = $this->Html->link(
      _txt('op.manage-a', array(_txt('ct.match_server_attributes.pl'))),
      array(
        'controller'  => 'match_server_attributes',
        'action'      => 'index',
        'matchserver' => $match_servers[0]['MatchServer']['id']
      ),
      array('class' => 'editbutton')
    );
  }
}