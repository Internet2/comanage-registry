<?php
/**
 * COmanage Registry Standard Edit View
 *
 * Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Get a pointer to our model
  $model = $this->name;
  $req = Inflector::singularize($model);

  // Add page title & page buttons
  $params = array();
  $params['title'] = $title_for_layout;
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

  $submit_label = _txt('op.save');
  echo $this->Form->create($req,
                           array('action' => 'edit',
                                 'inputDefaults' => array('label' => false, 'div' => false)));
  if(!empty($this->plugin)) {
    include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/fields.inc");
  } else {
    include(APP . "View/" . $model . "/fields.inc");
  }
  echo $this->Form->end();
?>
