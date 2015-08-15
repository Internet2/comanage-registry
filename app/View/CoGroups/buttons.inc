<!--
/**
 * COmanage Registry CO Group Fields
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
 * @copyright     Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<?php
// Determine if fields are editable or viewable
$dok = false;
$e = false;
$v = false;

if(($this->action == "add" && $permissions['add'])
  || ($this->action == "edit" && $permissions['edit'])
  || (($this->action == 'add' || $this->action == 'edit')
    && isset($co_groups[0]['CoGroup']['id'])
    && !empty($permissions['owner'])
    && in_array($co_groups[0]['CoGroup']['id'], $permissions['owner'])))
  $e = true;

if(($permissions['delete'])
  || (isset($co_groups[0]['CoGroup']['id'])
    && !empty($permissions['owner'])
    && in_array($co_groups[0]['CoGroup']['id'], $permissions['owner'])))
  $dok = true;

if(($this->action == "view" && $permissions['view'])
  || (isset($co_groups[0]['CoGroup']['id'])
    && !empty($permissions['member'])
    && in_array($co_groups[0]['CoGroup']['id'], $permissions['member']))
  || (isset($co_groups[0]['CoGroup']['open']) && $co_groups[0]['CoGroup']['open']))
  $v = true;

// We shouldn't get here if we don't have at least read permission, but check just in case
if(!$e && !$v)
  return(false);

// Add buttons
if($e && !empty($co_groups[0]['CoGroup']['id'])) {
  // Manage group memberships
  $params['topLinks'][] = $this->Html->link(
    _txt('op.manage.grm'),
    array(
      'controller' => 'co_group_members',
      'action'     => 'select',
      'cogroup'    => $co_groups[0]['CoGroup']['id'],
      'co'         => $cur_co['Co']['id']
    ),
    array('class' => 'editbutton')
  );
}

if($permissions['provision'] && !empty($co_groups[0]['CoGroup']['id'])) {
  // Provisioning status
  $params['topLinks'][] = $this->Html->link(
    _txt('op.prov.view'),
    array(
      'controller' => 'co_groups',
      'action'     => 'provision',
      $co_groups[0]['CoGroup']['id'],
      'co'         => $cur_co['Co']['id']
    ),
    array('class' => 'notebutton')
  );
}

?>
