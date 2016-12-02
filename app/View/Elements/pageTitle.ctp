<?php
/*
 * COmanage Registry Page Title
 * Displayed below all pages
 *
 * Version: $Revision$
 * Date: $Date$
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 */
?>

<div id="titleNavContainer">
  <div class="pageTitle">
    <h2>
      <?php print filter_var($title,FILTER_SANITIZE_FULL_SPECIAL_CHARS); ?>
    </h2>
  </div>
</div>