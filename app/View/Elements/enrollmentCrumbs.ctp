<?php
/**
 * COmanage Registry CO Petition Enrollment Step Bread Crumbs
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

// XXX can we drop "Home"?
$this->Html->addCrumb($title_for_layout);

foreach(array_keys($vv_configured_steps) as $step) {
  if($vv_configured_steps[$step]['enabled'] != RequiredEnum::NotPermitted
     // We specifically don't want "deny" to render, so we'll skip it here
     && $step != 'deny') {
    // XXX figure out some other way to styleize
    if($step == $vv_current_step) {
      $this->Html->addCrumb("<b>" . $vv_configured_steps[$step]['label'] . "</b>");
    } else {
      $this->Html->addCrumb($vv_configured_steps[$step]['label']);
    }
  }
}
?>
<!-- interim spacing -->
<br />