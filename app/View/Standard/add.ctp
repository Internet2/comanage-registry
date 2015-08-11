<?php
/**
 * COmanage Registry Standard Add View
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
  
  // Since this is an add operation, we may not have any data.
  // (Currently, only controllers like CoPersonRole prepopulate fields for rendering.)

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;
  print $this->element("pageTitleAndNav", $params);

  $submit_label = _txt('op.add');
  
  print $this->Form->create(
    $req,
    array(
      'action' => 'add',
      'inputDefaults' => array(
        'label' => false,
        'div' => false
      )
    )
  );
  
  if(!empty($this->plugin)) {
    include(APP . "Plugin/" . $this->plugin . "/View/" . $model . "/fields.inc");
  } else {
    include(APP . "View/" . $model . "/fields.inc");
  }
  
  print $this->Form->end();
?>
