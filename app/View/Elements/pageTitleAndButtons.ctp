<?php
/*
 * COmanage Registry Page Title and In-Page Navigation
 * Displayed below all pages
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
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v?
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
?>

<div class="titleNavContainer">
  <div class="pageTitle">
    <h1>
      <?php print filter_var($title,FILTER_SANITIZE_SPECIAL_CHARS); ?>
    </h1>
  </div>

  <?php if(!empty($topLinks)): ?>
    <ul id="topLinks">
      <?php foreach ($topLinks as $t): ?>
        <li><?php print $t?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif ?>
</div>