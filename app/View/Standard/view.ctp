<?php
/**
 * COmanage Registry Standard View View
 *
 * Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
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

  // If user has edit permission, offer an edit button in the sidebar
  if(!empty($permissions['edit']) && $permissions['edit']) {

    // special case co_people
    $editAction = 'edit';
    if ($modelpl == 'co_people') {
      $editAction = 'canvas';
    }

    $a = array('controller' => $modelpl, 'action' => $editAction, $d[0][$req]['id']);

    if(isset($this->params['named']['co'])) {
      $a['co'] = $this->params['named']['co'];
    }

    // Add edit button to the top links
    $params['topLinks'][] = $this->Html->link(
      _txt('op.edit'),
      $a,
      array('class' => 'editbutton')
    );

  }

  // Add locally configured page buttons
  if(!empty($this->plugin)) {
    if (file_exists("Plugin/" . $this->plugin . "/View/" . $model . "/buttons.inc")) {
      include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/buttons.inc");
    }
  } else {
    if (file_exists(APP . "View/" . $model . "/buttons.inc")) {
      include(APP . "View/" . $model . "/buttons.inc");
    }
  }

  print $this->element("pageTitleAndButtons", $params);

  // Output the fields
  print '<div class="innerContent">';
  if(!empty($this->plugin)) {
    include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/fields.inc");
  } else {
    include(APP . "View/" . $model . "/fields.inc");
  }
  print '</div>';

?>
