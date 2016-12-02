<?php
/*
 * COmanage Registry Link List
 * Displayed above all pages when logged in
 *
 * Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-13 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
?>

<div id="links">
  <ul>
    <?php
      // Emit dynamically configured (via Navigation Links) links
      
      if(isset($vv_NavLinks)) {
        foreach($vv_NavLinks as $l){
          print '<li><a href="' . $l['NavigationLink']['url'] . '">' . filter_var($l['NavigationLink']['title'],FILTER_SANITIZE_FULL_SPECIAL_CHARS) . '</a>';
        }
      }
      
      if(isset($vv_CoNavLinks)) {
        foreach($vv_CoNavLinks as $l){
          print '<li><a href="' . $l['CoNavigationLink']['url'] . '">' . filter_var($l['CoNavigationLink']['title'],FILTER_SANITIZE_FULL_SPECIAL_CHARS) . '</a>';
        }
      }
    ?>
  </ul>
</div>

