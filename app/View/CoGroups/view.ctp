<?php
/**
 * COmanage Registry CoGroups View View
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
 * @copyright     Copyright (C) 2015 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

// Get a pointer to our model
$model = $this->name;
$req = Inflector::singularize($model);
$modelpl = Inflector::tableize($req);

// Get a pointer to our data
$d = $$modelpl;

// Add page title
$params = array();
$params['title'] = $title_for_layout;

// Add top links
$params['topLinks'] = array();

// If user has edit permission, offer an edit button in the top links
// unless this is a members group.
$groupName = $co_groups[0]['CoGroup']['name'];
$isMembersGroup = ($groupName == 'members' || strncmp($groupName, 'members:', 8) == 0);

if(!empty($permissions['edit']) && $permissions['edit'] && !$isMembersGroup) {
  $a = array('controller' => $modelpl, 'action' => 'edit', $d[0][$req]['id']);

  if(isset($this->params['named']['co'])) {
    $a['co'] = $this->params['named']['co'];
  }

  $params['topLinks'][] = $this->Html->link(
    _txt('op.edit', array(_txt('ct.co_groups.1'))),
    $a,
    array('class' => 'editbutton')
  );

}

print $this->element("pageTitleAndNav", $params);

print '<div>';
include(APP . "View/CoGroups/fields.inc");
print '</div>';

?>
