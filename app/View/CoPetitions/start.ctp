<?php
/**
 * COmanage Registry CO Petition Start View
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
 * @since         COmanage Registry v0.9.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

// Add breadcrumbs
print $this->element("coCrumb");
$this->Html->addCrumb($title_for_layout);

// Add page title
$params = array('title' => $title_for_layout);
print $this->element("pageTitleAndButtons", $params);

print '<div id="enrollmentFlowIntro">';
print $vv_intro_text;
print '</div>';

print $this->Html->Link(
  _txt('op.begin') . ' <i class="material-icons">forward</i>',
  $vv_on_finish_url,
  array(
    'class' => 'co-button enrollmentFlowStartButton mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect',
    'escape' => false
  )
);

